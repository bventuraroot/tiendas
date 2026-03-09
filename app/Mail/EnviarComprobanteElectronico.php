<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnviarComprobanteElectronico extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $numeroFactura;
    public $nombreEmpresa;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $numeroFactura = null, $nombreEmpresa = null)
    {
        $this->data = $data;
        $this->numeroFactura = $numeroFactura;
        $this->nombreEmpresa = $nombreEmpresa;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        $asunto = 'Comprobante Electrónico';

        if ($this->numeroFactura) {
            $asunto = "Comprobante Electrónico No. {$this->numeroFactura}";
        }

        if ($this->nombreEmpresa) {
            $asunto .= " - {$this->nombreEmpresa}";
        }

        return new Envelope(
            subject: $asunto,
            from: new \Illuminate\Mail\Mailables\Address(
                config('mail.from.address'),
                config('mail.from.name')
            ),
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'emails.factura-offline',
            with: [
                'data' => $this->data,
                'numeroFactura' => $this->numeroFactura,
                'nombreEmpresa' => $this->nombreEmpresa,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }

    /**
     * Build the message (método alternativo para compatibilidad)
     *
     * @return $this
     */
    public function build()
    {
        $asunto = 'Comprobante Electrónico';

        if ($this->numeroFactura) {
            $asunto = "Comprobante Electrónico No. {$this->numeroFactura}";
        }

        if ($this->nombreEmpresa) {
            $asunto .= " - {$this->nombreEmpresa}";
        }

        $message = $this->from(config('mail.from.address'), config('mail.from.name'))
                        ->subject($asunto)
                        ->view('emails.factura-offline')
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
