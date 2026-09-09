<?php

namespace App\Mail;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Notifikasi email saat check-in/check-out absensi, dikirim kalau user mengaktifkan "Notifikasi Email" di profil. */
class AttendanceCheckMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 30;

    /** @param 'check_in'|'check_in_2'|'check_out'|'check_out_2' $event */
    public function __construct(
        public User $user,
        public Attendance $attendance,
        public string $event,
        public ?string $note = null,
    ) {}

    public function build()
    {
        $labels = [
            'check_in'    => 'Check-in',
            'check_in_2'  => 'Check-in Sesi 2',
            'check_out'   => 'Check-out',
            'check_out_2' => 'Check-out Sesi 2',
        ];

        $timeFields = [
            'check_in'    => $this->attendance->check_in,
            'check_in_2'  => $this->attendance->check_in_2,
            'check_out'   => $this->attendance->check_out,
            'check_out_2' => $this->attendance->check_out_2,
        ];

        $eventLabel = $labels[$this->event] ?? ucfirst($this->event);

        return $this->subject("[Flovig] {$eventLabel} tercatat — " . substr((string) $timeFields[$this->event], 0, 5))
            ->view('emails.attendance-check')
            ->with([
                'name'       => $this->user->name,
                'eventLabel' => $eventLabel,
                'time'       => substr((string) $timeFields[$this->event], 0, 5),
                'date'       => $this->attendance->date->locale('id')->isoFormat('dddd, D MMMM YYYY'),
                'note'       => $this->note,
            ]);
    }
}
