<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnviarCorreo extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    public $data;
    public $numeroFactura;
    public $nombreEmpresa;

    public function __construct($data, $numeroFactura = null, $nombreEmpresa = null)
    {
        $this->data = $data;
        $this->numeroFactura = $numeroFactura;
        $this->nombreEmpresa = $nombreEmpresa;
    }

    public function build()
    {
        $message = $this->view('emails.factura-offline')
                        ->with([
                            'data' => $this->data,
                            'numeroFactura' => $this->numeroFactura,
                            'nombreEmpresa' => $this->nombreEmpresa,
                        ]);

        // Agregar headers personalizados para foto de contacto
        $this->addContactPhotoHeaders($message);

        return $message;
    }

    /**
     * Agregar headers personalizados para foto de contacto
     */
    private function addContactPhotoHeaders($message)
    {
        try {
            // Obtener el logo de la empresa
            $logoPath = public_path('assets/img/logo/logo.png');

            if (file_exists($logoPath)) {
                $logoData = file_get_contents($logoPath);
                if ($logoData && strlen($logoData) < 2000000) {
                    $logoBase64 = base64_encode($logoData);

                    // Headers para foto de contacto (compatibles con algunos clientes)
                    $message->getSwiftMessage()->getHeaders()->addTextHeader(
                        'X-Contact-Photo',
                        'data:image/png;base64,' . $logoBase64
                    );

                    // Header alternativo para algunos clientes
                    $message->getSwiftMessage()->getHeaders()->addTextHeader(
                        'X-Avatar',
                        'data:image/png;base64,' . $logoBase64
                    );
                }
            }
        } catch (\Exception $e) {
            // Silenciar errores para no afectar el envío del correo
        }
    }
}
