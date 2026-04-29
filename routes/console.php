<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Queue worker untuk shared hosting
// Jalan setiap menit, berhenti otomatis jika tidak ada job atau setelah 55 detik
Schedule::command('queue:work database --queue=default,whatsapp --stop-when-empty --max-time=55 --tries=3 --backoff=5')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/queue-worker.log'));

// Bersihkan failed jobs lama (lebih dari 7 hari)
Schedule::command('queue:prune-failed --hours=168')
    ->daily();

// Update status invoice menjadi "overdue" jika due_date sudah lewat dan belum paid
// Jalan setiap hari jam 00:05 WIB
Schedule::command('invoices:update-overdue')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/invoice-overdue.log'));
