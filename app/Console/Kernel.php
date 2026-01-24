<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\Reporte;

class Kernel extends ConsoleKernel
{

    protected function schedule(Schedule $schedule): void
    {
        // Obtener todos los reportes activos de la base de datos
        $reportes = Reporte::where('status', 'A')->get();

        foreach ($reportes as $reporte) {
            $command = $this->getCommandBasedOnName($reporte->name);

            if ($command) {
                $scheduleTask = $schedule->command($command, [
                    '--subject' => $reporte->subject,
                    '--to' => $reporte->to,
                    '--cc' => $reporte->cc,
                    '--startDate' => $reporte->startDate,
                    '--endDate' => $reporte->endDate
                ]);

                // Programar la tarea según el valor del campo `period`
                switch ($reporte->period) {
                    case 1: // Cada hora
                        $scheduleTask->everyMinute();
                        // $scheduleTask->hourlyAt((int) date('i', strtotime($reporte->time)));
                        break;
                    case 2: // Cada día
                        $scheduleTask->dailyAt($reporte->time);
                        break;
                    case 3: // Cada semana
                        $scheduleTask->weeklyOn((int) date('N', strtotime($reporte->startDate)), $reporte->time);
                        break;
                    case 4: // Cada mes
                        $scheduleTask->monthlyOn((int) date('j', strtotime($reporte->startDate)), $reporte->time);
                        break;
                    default:
                        // Si el valor de period no es válido, ignoramos esta tarea
                        break;
                }
            }
        }
    }

    /**
     * Obtener el comando basado en el campo `name`.
     *
     * @param string $name
     * @return string|null
     */
    protected function getCommandBasedOnName(string $name): ?string
    {
        $commands = [
            'ReposicionStock' => 'command:notificacionreposicionstock',
            'EstadoClientes' => 'command:notificacionestadoclientes',

            // Agrega más mapeos si tienes otros comandos
        ];

        return $commands[$name] ?? null;
    }


    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
