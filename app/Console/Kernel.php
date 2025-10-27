<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\VerifierBlocageCompteJob;
use App\Jobs\DebloquerCompteJob;
use App\Jobs\ArchiveComptesToBufferJob;
use App\Jobs\RestoreFromBufferJob;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Vérifier les blocages chaque jour
    $schedule->job(new VerifierBlocageCompteJob)->daily();
    $schedule->job(new DebloquerCompteJob)->daily();
    // Transfer archived comptes to buffer once per day
    $schedule->job(new ArchiveComptesToBufferJob)->daily();
    // Attempt to restore comptes from buffer (e.g. when deblocage date arrives)
    $schedule->job(new RestoreFromBufferJob)->daily();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
