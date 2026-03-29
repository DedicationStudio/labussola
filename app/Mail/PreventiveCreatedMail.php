<?php

namespace App\Mail;

use App\Models\Email;
use App\Models\Preventive;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Spatie\Browsershot\Browsershot;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\BrevoWhatsAppService;




class PreventiveCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */

    public Preventive $preventive;
    public ?Email $email;



    public function __construct(Preventive $preventive, ?Email $email = null)
    {
        $this->preventive = $preventive;
        $this->email = $email;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuovo Preventivo ' . $this->preventive->numero . '/' . $this->preventive->anno,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.preventivi.created',
            with: [
                'preventive' => $this->preventive,
                'corpo_email' => $this->email->corpo_email,
                'referente' => $this->preventive->creator?->nome . ' ' . $this->preventive->creator?->cognome,
                'telefono_referente' => $this->preventive->creator?->telefono, // o ->phone
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
   public function attachments(): array
{
    $files = [];

    // Funzione di supporto per allegare file se esistono, altrimenti crea file segnaposto
    $attach = function (?string $path, string $nome) use (&$files) {
        if (!$path) {
            return;
        }

        $fullPath = storage_path('app/public/' . $path);

        if (file_exists($fullPath)) {
            $files[] = Attachment::fromPath($fullPath)->as(basename($path));
        } else {
            $tmp = tempnam(sys_get_temp_dir(), 'missing_');
            file_put_contents($tmp, "Il file previsto per {$nome} non è stato trovato.");
            $files[] = Attachment::fromPath($tmp)->as("MANCANTE - {$nome}.txt");
        }
    };

    if ($this->preventive->allego_file === false || $this->preventive->allego_file === 0) {
        // GENERA PDF preventivo
        try {
            $this->preventive = Preventive::with([
                'hotel_preventives.hotel',
                'hotel_preventives.rooms_paganti',
                'hotel_preventives.rooms_gratuite',
                'trasporto_andata.transport',
                'trasporto_andata.transport_company',
                'trasporto_rientro.transport',
                'trasporto_rientro.transport_company',
                'trasporto_intermedio.transport',
                'trasporto_intermedio.transport_company',
                'extra_services.extra_service',
                'creator',
                'customer',
            ])->find($this->preventive->id);

            $pdf = Pdf::loadView('preventivo_pdf.file', [
                'preventivo' => $this->preventive->toPdfArray(),
            ])
            ->setPaper('A4')
            ->setOption('dpi', 96);

            $pdfContent = $pdf->output();

            if (strlen($pdfContent) < 10 * 1024 * 1024) {
                $files[] = Attachment::fromData(
                    fn() => $pdfContent,
                    "preventivo_{$this->preventive->cod_alfa}.pdf"
                )->withMime('application/pdf');
            } else {
                \Log::error("PDF troppo grande: " . strlen($pdfContent) . " bytes");
            }

        } catch (\Exception $e) {
            \Log::error("Errore generazione PDF: " . $e->getMessage());
        }

        // Allegati extra polizza
        foreach ($this->preventive->extra_services as $service) {
            if (strtolower($service->extra_service?->tipo) === 'polizza') {
                $allegati = $service->extra_service?->allegati;

                if (is_array($allegati)) {
                    foreach ($allegati as $file) {
                        $attach($file, 'File Polizza');
                    }
                } elseif (is_string($allegati)) {
                    $attach($allegati, 'File Polizza');
                }
            }
        }

        // Allegati email
        if (is_iterable($this->email?->allegati)) {
            foreach ($this->email->allegati as $allegato) {
                $attach($allegato, 'Allegato Email');
            }
        }

    } else {
        if($this->preventive->file_preventivo) {
             $attach($this->preventive->file_preventivo, 'File Preventivo');
        }
        if (is_iterable($this->email?->allegati)) {
            foreach ($this->email->allegati as $allegato) {
                $attach($allegato, 'Allegato Email');
            }
        }
    }

    return $files;
}


    public function build()
    {
        // EMAIL

        $this->subject('Nuovo Preventivo ' . $this->preventive->numero . '/' . $this->preventive->anno);
// Determina il link in base a allego_file
        if ($this->preventive->allego_file === true || $this->preventive->allego_file === 1) {
            // Link diretto all'allegato email
            $pdfUrl = route('preventivo.show.allegato', $this->preventive->cod_alfa);
        } else {
            // Link alla pagina web del preventivo
            $pdfUrl = url('/preventivi/' . $this->preventive->cod_alfa);
        }
        try {
            $service = new BrevoWhatsAppService();

            // Recupero il numero del cliente
            $to = $this->preventive->customer->telefono; // Assicurati che sia nel formato +39xxx

            $templateId = 136; // il nome del template approvato su Brevo

            $params = [
                'nome' => isset($this->preventive->customer->nome)
                    ? $this->preventive->customer->nome
                    . (!empty($this->preventive->customer->cognome)
                        ? ' ' . $this->preventive->customer->cognome
                        : '')
                    : 'Cliente',
                'destinazione' => $this->preventive->meta_viaggio ?? 'Destinazione',
                'preventivo' => $pdfUrl,
                'scadenza' => optional($this->preventive->date_expiration)->format('d/m/Y') ?? '-',
                'riscontro' => url('/preventivi/' . $this->preventive->cod_alfa . '/risposta'),
            ];


            // Invia messaggio WhatsApp
            $service->sendTemplate($to, $templateId, $params);

        } catch (\Throwable $e) {
            \Log::error('Errore invio WhatsApp: ' . $e->getMessage());
        }

        return $this;
    }


}


