<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\QuotationMail;
use App\Helpers\EmailContactPhotoHelper;

class TestEmailWithContactPhoto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-contact-photo {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar un correo de prueba con foto de contacto configurada';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->argument('email');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Por favor, proporciona un email válido.');
            return 1;
        }

        $this->info("Enviando correo de prueba con foto de contacto a: {$email}");

        try {
            // Verificar si existe el logo
            if (!EmailContactPhotoHelper::hasValidLogo()) {
                $this->warn('No se encontró un logo válido en: ' . public_path('assets/img/logo/logo.png'));
                $this->warn('El correo se enviará sin foto de contacto.');
            } else {
                $this->info('✅ Logo encontrado y válido');
            }

            // Datos de prueba para la cotización
            $data = [
                'company_name' => 'Agroservicio Milagro de Dios',
                'quote_number' => 'COT-TEST-001',
                'nombre' => 'Cliente de Prueba',
                'quotation' => (object) [
                    'quote_date' => now(),
                    'valid_until' => now()->addDays(30),
                    'total_amount' => 1500.00,
                    'currency' => 'USD',
                    'payment_terms' => '30 días',
                    'delivery_time' => '5 días hábiles'
                ],
                'custom_message' => 'Este es un correo de prueba para verificar que la foto de contacto se muestre correctamente.'
            ];

            // Enviar el correo
            Mail::to($email)->send(new QuotationMail($data, 'Prueba de Foto de Contacto'));

            $this->info('✅ Correo enviado exitosamente');
            $this->info('Revisa tu bandeja de entrada para verificar que la foto de contacto aparezca correctamente.');

        } catch (\Exception $e) {
            $this->error('Error al enviar el correo: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
