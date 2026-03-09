<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $customSubject;

    /**
     * Create a new message instance.
     *
     * @param array $data
     * @param string|null $subject
     * @return void
     */
    public function __construct($data, $subject = null)
    {
        $this->data = $data;
        $this->customSubject = $subject;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        $subject = $this->customSubject ?? 'Cotización ' . ($this->data['quote_number'] ?? '');

        return new Envelope(
            subject: $subject,
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
            view: 'emails.quotation',
            with: ['data' => $this->data]
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
     * Build the message (for Laravel compatibility)
     */
    public function build()
    {
        $subject = $this->customSubject ?? 'Cotización ' . ($this->data['quote_number'] ?? '');

        $message = $this->subject($subject)
                        ->view('emails.quotation')
                        ->with('data', $this->data);

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
