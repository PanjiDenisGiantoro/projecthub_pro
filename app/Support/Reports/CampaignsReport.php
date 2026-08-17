<?php

namespace App\Support\Reports;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Support\Collection;

class CampaignsReport implements ReportQuery
{
    public function filters(): array
    {
        return [
            'status' => [
                'type'    => 'select',
                'label'   => 'Status',
                'options' => [
                    'draft'     => 'Draft',
                    'active'    => 'Aktif',
                    'paused'    => 'Dijeda',
                    'completed' => 'Selesai',
                    'cancelled' => 'Dibatalkan',
                ],
            ],
            'channel' => [
                'type'    => 'select',
                'label'   => 'Channel',
                'options' => [
                    'social_media' => 'Social Media',
                    'email'        => 'Email',
                    'event'        => 'Event',
                    'ads'          => 'Ads',
                    'seo'          => 'SEO',
                    'other'        => 'Lainnya',
                ],
            ],
            'start_date' => [
                'type'  => 'date_range',
                'label' => 'Tanggal Mulai',
            ],
        ];
    }

    public function columns(): array
    {
        return [
            'id'            => 'ID',
            'name'          => 'Nama Campaign',
            'project'       => 'Proyek',
            'channel'       => 'Channel',
            'status'        => 'Status',
            'budget'        => 'Anggaran',
            'actual_spend'  => 'Realisasi',
            'leads_count'   => 'Leads',
            'start_date'    => 'Mulai',
            'end_date'      => 'Selesai',
        ];
    }

    public function rows(array $filters, User $user): Collection
    {
        $query = Campaign::query()->with('project');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }
        if (! empty($filters['start_date_from'])) {
            $query->whereDate('start_date', '>=', $filters['start_date_from']);
        }
        if (! empty($filters['start_date_to'])) {
            $query->whereDate('start_date', '<=', $filters['start_date_to']);
        }

        return $query->orderByDesc('id')->get()->map(fn (Campaign $c) => [
            'id'           => $c->id,
            'name'         => $c->name,
            'project'      => $c->project?->name ?? '-',
            'channel'      => $c->channel,
            'status'       => $c->status,
            'budget'       => $c->budget !== null ? number_format((float) $c->budget, 0, ',', '.') : '-',
            'actual_spend' => $c->actual_spend !== null ? number_format((float) $c->actual_spend, 0, ',', '.') : '-',
            'leads_count'  => $c->leads_count,
            'start_date'   => $c->start_date?->format('d/m/Y') ?? '-',
            'end_date'     => $c->end_date?->format('d/m/Y') ?? '-',
        ]);
    }
}
