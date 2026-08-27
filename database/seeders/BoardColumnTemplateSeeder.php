<?php

namespace Database\Seeders;

use App\Models\BoardColumnTemplate;
use Illuminate\Database\Seeder;

class BoardColumnTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTemplate(
            'Kanban Dasar',
            'umum',
            'Template kolom board bawaan sistem (Todo, In Progress, Review, Done).',
            [
                ['name' => 'To Do', 'slug' => 'todo', 'color' => 'gray', 'sort_order' => 0, 'is_done' => false],
                ['name' => 'In Progress', 'slug' => 'in_progress', 'color' => 'blue', 'sort_order' => 1, 'is_done' => false],
                ['name' => 'Review', 'slug' => 'review', 'color' => 'purple', 'sort_order' => 2, 'is_done' => false],
                ['name' => 'Done', 'slug' => 'done', 'color' => 'green', 'sort_order' => 3, 'is_done' => true],
            ]
        );

        $this->seedTemplate(
            'Rekrutmen HR',
            'hr',
            'Pipeline rekrutmen kandidat dari lamaran masuk sampai diterima kerja.',
            [
                ['name' => 'Lamaran Masuk', 'slug' => 'lamaran_masuk', 'color' => 'gray', 'sort_order' => 0, 'is_done' => false],
                ['name' => 'Screening', 'slug' => 'screening', 'color' => 'blue', 'sort_order' => 1, 'is_done' => false],
                ['name' => 'Interview', 'slug' => 'interview', 'color' => 'purple', 'sort_order' => 2, 'is_done' => false],
                ['name' => 'Penawaran', 'slug' => 'penawaran', 'color' => 'amber', 'sort_order' => 3, 'is_done' => false],
                ['name' => 'Diterima', 'slug' => 'diterima', 'color' => 'green', 'sort_order' => 4, 'is_done' => true],
                ['name' => 'Ditolak', 'slug' => 'ditolak', 'color' => 'red', 'sort_order' => 5, 'is_done' => true],
            ]
        );

        $this->seedTemplate(
            'Pengembangan Materi Edukasi',
            'education',
            'Alur pembuatan materi/kursus dari ide sampai publish.',
            [
                ['name' => 'Ide', 'slug' => 'ide', 'color' => 'gray', 'sort_order' => 0, 'is_done' => false],
                ['name' => 'Draft Materi', 'slug' => 'draft_materi', 'color' => 'blue', 'sort_order' => 1, 'is_done' => false],
                ['name' => 'Review', 'slug' => 'review', 'color' => 'purple', 'sort_order' => 2, 'is_done' => false],
                ['name' => 'Revisi', 'slug' => 'revisi', 'color' => 'amber', 'sort_order' => 3, 'is_done' => false],
                ['name' => 'Publish', 'slug' => 'publish', 'color' => 'green', 'sort_order' => 4, 'is_done' => true],
            ]
        );

        $this->seedTemplate(
            'Kampanye Marketing',
            'marketing',
            'Alur produksi kampanye marketing dari ide sampai tayang.',
            [
                ['name' => 'Ide Kampanye', 'slug' => 'ide_kampanye', 'color' => 'gray', 'sort_order' => 0, 'is_done' => false],
                ['name' => 'Perencanaan', 'slug' => 'perencanaan', 'color' => 'blue', 'sort_order' => 1, 'is_done' => false],
                ['name' => 'Produksi Konten', 'slug' => 'produksi_konten', 'color' => 'indigo', 'sort_order' => 2, 'is_done' => false],
                ['name' => 'Review', 'slug' => 'review', 'color' => 'purple', 'sort_order' => 3, 'is_done' => false],
                ['name' => 'Tayang', 'slug' => 'tayang', 'color' => 'green', 'sort_order' => 4, 'is_done' => true],
            ]
        );

        $this->seedTemplate(
            'Pipeline Sales',
            'sales',
            'Pipeline penjualan/CRM dari leads masuk sampai deal closing.',
            [
                ['name' => 'Leads Baru', 'slug' => 'leads_baru', 'color' => 'gray', 'sort_order' => 0, 'is_done' => false],
                ['name' => 'Kontak Awal', 'slug' => 'kontak_awal', 'color' => 'blue', 'sort_order' => 1, 'is_done' => false],
                ['name' => 'Negosiasi', 'slug' => 'negosiasi', 'color' => 'amber', 'sort_order' => 2, 'is_done' => false],
                ['name' => 'Deal Closing', 'slug' => 'deal_closing', 'color' => 'teal', 'sort_order' => 3, 'is_done' => false],
                ['name' => 'Menang', 'slug' => 'menang', 'color' => 'green', 'sort_order' => 4, 'is_done' => true],
                ['name' => 'Kalah', 'slug' => 'kalah', 'color' => 'red', 'sort_order' => 5, 'is_done' => true],
            ]
        );

        $this->seedTemplate(
            'Approval Reimbursement',
            'finance',
            'Alur persetujuan pengajuan reimbursement, selaras dengan status modul Reimbursement (pending, approved, rejected, paid).',
            [
                ['name' => 'Diajukan', 'slug' => 'diajukan', 'color' => 'gray', 'sort_order' => 0, 'is_done' => false],
                ['name' => 'Direview', 'slug' => 'direview', 'color' => 'blue', 'sort_order' => 1, 'is_done' => false],
                ['name' => 'Disetujui', 'slug' => 'disetujui', 'color' => 'teal', 'sort_order' => 2, 'is_done' => false],
                ['name' => 'Dibayar', 'slug' => 'dibayar', 'color' => 'green', 'sort_order' => 3, 'is_done' => true],
                ['name' => 'Ditolak', 'slug' => 'ditolak', 'color' => 'red', 'sort_order' => 4, 'is_done' => true],
            ]
        );

        $this->seedTemplate(
            'Support & Bug Tracking',
            'lainnya',
            'Alur penanganan tiket support/bug dari laporan masuk sampai selesai.',
            [
                ['name' => 'Tiket Masuk', 'slug' => 'tiket_masuk', 'color' => 'gray', 'sort_order' => 0, 'is_done' => false],
                ['name' => 'Investigasi', 'slug' => 'investigasi', 'color' => 'blue', 'sort_order' => 1, 'is_done' => false],
                ['name' => 'Dikerjakan', 'slug' => 'dikerjakan', 'color' => 'indigo', 'sort_order' => 2, 'is_done' => false],
                ['name' => 'Menunggu Konfirmasi', 'slug' => 'menunggu_konfirmasi', 'color' => 'amber', 'sort_order' => 3, 'is_done' => false],
                ['name' => 'Selesai', 'slug' => 'selesai', 'color' => 'green', 'sort_order' => 4, 'is_done' => true],
            ]
        );
    }

    private function seedTemplate(string $name, string $category, string $description, array $items): void
    {
        $template = BoardColumnTemplate::firstOrCreate(
            ['company_id' => null, 'name' => $name],
            ['description' => $description, 'category' => $category]
        );

        if ($template->category !== $category) {
            $template->update(['category' => $category]);
        }

        if ($template->items()->exists()) {
            return;
        }

        foreach ($items as $item) {
            $template->items()->create($item);
        }
    }
}
