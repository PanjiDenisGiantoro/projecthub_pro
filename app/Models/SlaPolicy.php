<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlaPolicy extends Model
{
    /**
     * Prioritas tiket + panduan kapan dipakai + SLA default (menit). Default ini
     * dipakai kalau proyek belum mengatur SLA sendiri dan tidak ada policy sistem.
     * Satu sumber untuk form tiket, halaman pengaturan SLA, dan Runa.
     */
    public const PRIORITIES = [
        'critical' => [
            'label'      => 'Critical',
            'desc'       => 'Sistem down / tidak bisa dipakai sama sekali, kehilangan data, celah keamanan, atau berdampak ke semua user — tanpa solusi sementara.',
            'example'    => 'Aplikasi tidak bisa diakses, transaksi gagal semua, data terhapus.',
            'response'   => 30,
            'resolution' => 240,
        ],
        'high' => [
            'label'      => 'High',
            'desc'       => 'Fitur utama rusak atau banyak user terdampak, solusi sementara sulit.',
            'example'    => 'Tidak bisa checkout, laporan utama error, login gagal untuk sebagian user.',
            'response'   => 120,
            'resolution' => 1440,
        ],
        'medium' => [
            'label'      => 'Medium',
            'desc'       => 'Fitur pendukung bermasalah atau sebagian kecil user terdampak, ada solusi sementara.',
            'example'    => 'Filter pencarian tidak jalan, export lambat, tampilan rusak di satu browser.',
            'response'   => 240,
            'resolution' => 4320,
        ],
        'low' => [
            'label'      => 'Low',
            'desc'       => 'Kosmetik / minor, tidak mengganggu pekerjaan.',
            'example'    => 'Salah ketik, warna/ikon kurang pas, perbaikan kecil tampilan.',
            'response'   => 1440,
            'resolution' => 10080,
        ],
    ];

    protected $fillable = [
        'project_id', 'priority', 'response_minutes', 'resolution_minutes',
        'escalation_at_percent', 'business_hours_only', 'created_by',
    ];

    protected function casts(): array
    {
        return ['business_hours_only' => 'boolean'];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tickets()
    {
        return $this->hasMany(BugTicket::class);
    }

    /** "240" → "4 jam", "4320" → "3 hari", "90" → "1 jam 30 menit". */
    public static function humanMinutes(?int $minutes): string
    {
        if ($minutes === null) {
            return '-';
        }
        if ($minutes % 1440 === 0) {
            return ($minutes / 1440) . ' hari';
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return trim(($h ? "{$h} jam " : '') . ($m ? "{$m} menit" : ''));
    }
}
