<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class ScheduledBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecutar respaldo programado de base de datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando respaldo programado...');

        try {
            // Verificar si los respaldos programados están habilitados
            if (!config('backup.schedule.enabled', false)) {
                $this->warn('Los respaldos programados están deshabilitados');
                return 0;
            }

            // Verificar si es el momento correcto para ejecutar el respaldo
            if (!$this->shouldRunBackup()) {
                $this->info('No es el momento programado para ejecutar el respaldo');
                return 0;
            }

            // Obtener configuración
            $compress = config('backup.defaults.compress', true);
            $keep = config('backup.defaults.keep_backups', 7);

            // Ejecutar comando de respaldo
            $command = "backup:database";
            $params = [
                '--compress' => $compress,
                '--keep' => $keep
            ];

            $exitCode = Artisan::call($command, $params);

            if ($exitCode === 0) {
                $output = Artisan::output();

                // Registrar éxito
                Log::info('Respaldo programado ejecutado exitosamente', [
                    'compress' => $compress,
                    'keep' => $keep,
                    'output' => $output
                ]);

                // Enviar notificaciones si están habilitadas
                $this->sendNotifications(true, 'Respaldo programado completado exitosamente');

                $this->info('✅ Respaldo programado completado exitosamente');
                return 0;
            } else {
                throw new \Exception('Error al ejecutar el respaldo programado');
            }

        } catch (\Exception $e) {
            $errorMessage = 'Error en respaldo programado: ' . $e->getMessage();

            Log::error($errorMessage);

            // Enviar notificaciones de error si están habilitadas
            $this->sendNotifications(false, $errorMessage);

            $this->error($errorMessage);
            return 1;
        }
    }

    /**
     * Verificar si debe ejecutar el respaldo según la programación
     */
    private function shouldRunBackup()
    {
        $frequency = config('backup.schedule.frequency', 'daily');
        $time = config('backup.schedule.time', '02:00');
        $dayOfWeek = config('backup.schedule.day_of_week', 1);
        $dayOfMonth = config('backup.schedule.day_of_month', 1);

        $now = Carbon::now();
        $scheduledTime = Carbon::createFromFormat('H:i', $time);

        switch ($frequency) {
            case 'daily':
                // Ejecutar todos los días a la hora programada
                return $now->format('H:i') === $scheduledTime->format('H:i');

            case 'weekly':
                // Ejecutar una vez por semana en el día y hora programados
                return $now->dayOfWeek === $dayOfWeek &&
                       $now->format('H:i') === $scheduledTime->format('H:i');

            case 'monthly':
                // Ejecutar una vez por mes en el día y hora programados
                return $now->day === $dayOfMonth &&
                       $now->format('H:i') === $scheduledTime->format('H:i');

            default:
                return false;
        }
    }

    /**
     * Enviar notificaciones
     */
    private function sendNotifications($success, $message)
    {
        if (!config('backup.notifications.enabled', false)) {
            return;
        }

        // Notificaciones por email
        if (config('backup.notifications.email.enabled', false)) {
            $this->sendEmailNotification($success, $message);
        }

        // Notificaciones por Slack
        if (config('backup.notifications.slack.enabled', false)) {
            $this->sendSlackNotification($success, $message);
        }
    }

    /**
     * Enviar notificación por email
     */
    private function sendEmailNotification($success, $message)
    {
        try {
            $recipients = config('backup.notifications.email.recipients', []);

            if (empty($recipients)) {
                return;
            }

            $subject = $success ? '✅ Respaldo Completado' : '❌ Error en Respaldo';
            $body = $message . "\n\nFecha: " . Carbon::now()->format('Y-m-d H:i:s');

            // Aquí puedes implementar el envío de email
            // Por ejemplo, usando Mail::send() o una notificación personalizada
            Log::info('Notificación de respaldo por email enviada', [
                'recipients' => $recipients,
                'subject' => $subject,
                'success' => $success
            ]);

        } catch (\Exception $e) {
            Log::error('Error al enviar notificación por email: ' . $e->getMessage());
        }
    }

    /**
     * Enviar notificación por Slack
     */
    private function sendSlackNotification($success, $message)
    {
        try {
            $webhookUrl = config('backup.notifications.slack.webhook_url');

            if (empty($webhookUrl)) {
                return;
            }

            $color = $success ? '#36a64f' : '#ff0000';
            $emoji = $success ? ':white_check_mark:' : ':x:';

            $payload = [
                'attachments' => [
                    [
                        'color' => $color,
                        'title' => $success ? 'Respaldo Completado' : 'Error en Respaldo',
                        'text' => $message,
                        'fields' => [
                            [
                                'title' => 'Fecha',
                                'value' => Carbon::now()->format('Y-m-d H:i:s'),
                                'short' => true
                            ],
                            [
                                'title' => 'Estado',
                                'value' => $success ? 'Exitoso' : 'Fallido',
                                'short' => true
                            ]
                        ],
                        'footer' => 'Sistema de Respaldos - ' . config('app.name')
                    ]
                ]
            ];

            // Enviar a Slack usando cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $webhookUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                Log::info('Notificación de respaldo por Slack enviada', [
                    'success' => $success,
                    'response' => $response
                ]);
            } else {
                Log::error('Error al enviar notificación por Slack', [
                    'http_code' => $httpCode,
                    'response' => $response
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error al enviar notificación por Slack: ' . $e->getMessage());
        }
    }
}
