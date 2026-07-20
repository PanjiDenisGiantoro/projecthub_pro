<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckExpiringCompanies extends Command
{
    protected $signature = 'companies:check-expiring';
    protected $description = 'Notify all users of a company when its active period is about to expire';

    /** Kirim notifikasi persis di H-7, H-3, dan H-1 supaya tidak spam tiap hari. */
    private const MILESTONE_DAYS = [7, 3, 1];

    public function handle(NotificationService $notifier): void
    {
        $registrants = User::registered()
            ->whereNotNull('active_until')
            ->whereNotNull('company_id')
            ->get();

        $notified = 0;

        foreach ($registrants as $registrant) {
            $daysLeft = now()->startOfDay()->diffInDays($registrant->active_until->copy()->startOfDay(), false);

            if (! in_array($daysLeft, self::MILESTONE_DAYS, true)) {
                continue;
            }

            $notifier->notifyCompany(
                $registrant->company_id,
                'company_expiring',
                'Masa Aktif Akan Berakhir',
                "Masa aktif perusahaan Anda akan berakhir dalam {$daysLeft} hari (tanggal {$registrant->active_until->format('d M Y')}). Segera hubungi admin untuk perpanjangan.",
                ['active_until' => $registrant->active_until->toDateString(), 'days_left' => $daysLeft]
            );

            $notified++;
        }

        $this->info("Notified {$notified} company/companies about upcoming expiry.");
    }
}
