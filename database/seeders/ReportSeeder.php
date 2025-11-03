<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Models\ReportHistory;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        // Report 1 - Jalan Lubang (Dalam Proses)
        $report1 = Report::create([
            'user_id' => 3, // Kentangtintung
            'title' => 'Jalan Lubang',
            'complaint_description' => 'Jalan berlubang dan berbahaya untuk pengendara',
            'location_description' => 'Didekat Pos Satpam',
            'report_date' => now()->subDays(5),
            'report_time' => '07:00:00',
            'status' => 'on_hold',
            'admin_notes' => 'Sedang menunggu koordinasi dengan Dinas PU',
        ]);

        ReportHistory::create([
            'report_id' => $report1->id,
            'status' => 'pending',
            'notes' => 'Laporan dibuat',
            'changed_at' => now()->subDays(5),
            'changed_by' => 3,
        ]);

        ReportHistory::create([
            'report_id' => $report1->id,
            'status' => 'on_hold',
            'notes' => 'Menunggu koordinasi dengan Dinas PU',
            'changed_at' => now()->subDays(4),
            'changed_by' => 1,
        ]);

        // Report 2 - Lampu Mati (Terkirim)
        $report2 = Report::create([
            'user_id' => 4, // Budi
            'title' => 'Lampu Mati',
            'complaint_description' => 'Lampu jalan mati sejak 3 hari yang lalu',
            'location_description' => 'Disamping Balai RT',
            'report_date' => now()->subDays(3),
            'report_time' => '18:00:00',
            'status' => 'in_progress',
            'admin_notes' => 'Petugas sedang melakukan perbaikan',
        ]);

        ReportHistory::create([
            'report_id' => $report2->id,
            'status' => 'pending',
            'notes' => 'Laporan dibuat',
            'changed_at' => now()->subDays(3),
            'changed_by' => 4,
        ]);

        ReportHistory::create([
            'report_id' => $report2->id,
            'status' => 'in_progress',
            'notes' => 'Petugas Pak Udin ditugaskan untuk perbaikan',
            'changed_at' => now()->subDays(2),
            'changed_by' => 1,
        ]);

        // Report 3 - Jalan Lampu Mati (Selesai)
        $report3 = Report::create([
            'user_id' => 3, // Kentangtintung
            'title' => 'Jalan Lampu Mati',
            'complaint_description' => 'Lampu jalan di depan rumah mati',
            'location_description' => 'Didekan Blok A-2',
            'report_date' => now()->subDays(10),
            'report_time' => '07:00:00',
            'status' => 'done',
            'admin_notes' => 'Lampu sudah diperbaiki',
        ]);

        ReportHistory::create([
            'report_id' => $report3->id,
            'status' => 'pending',
            'notes' => 'Laporan dibuat',
            'changed_at' => now()->subDays(10),
            'changed_by' => 3,
        ]);

        ReportHistory::create([
            'report_id' => $report3->id,
            'status' => 'in_progress',
            'notes' => 'Sedang dalam perbaikan',
            'changed_at' => now()->subDays(8),
            'changed_by' => 2,
        ]);

        ReportHistory::create([
            'report_id' => $report3->id,
            'status' => 'done',
            'notes' => 'Perbaikan selesai, lampu sudah menyala kembali',
            'changed_at' => now()->subDays(7),
            'changed_by' => 2,
        ]);

        // Report 4 - Sampah Menumpuk
        $report4 = Report::create([
            'user_id' => 5, // Siti
            'title' => 'Sampah Menumpuk',
            'complaint_description' => 'Sampah tidak diangkut selama seminggu',
            'location_description' => 'TPS Blok C',
            'report_date' => now()->subDays(2),
            'report_time' => '06:00:00',
            'status' => 'pending',
        ]);

        ReportHistory::create([
            'report_id' => $report4->id,
            'status' => 'pending',
            'notes' => 'Laporan dibuat',
            'changed_at' => now()->subDays(2),
            'changed_by' => 5,
        ]);

        // Report 5 - Gorong-gorong Tersumbat
        $report5 = Report::create([
            'user_id' => 4, // Budi
            'title' => 'Gorong-gorong Tersumbat',
            'complaint_description' => 'Saluran air tersumbat menyebabkan banjir saat hujan',
            'location_description' => 'Jl. Melati No. 78',
            'report_date' => now()->subDay(),
            'report_time' => '15:30:00',
            'status' => 'pending',
        ]);

        ReportHistory::create([
            'report_id' => $report5->id,
            'status' => 'pending',
            'notes' => 'Laporan dibuat',
            'changed_at' => now()->subDay(),
            'changed_by' => 4,
        ]);
    }
}