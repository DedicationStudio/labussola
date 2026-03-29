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
use App\Services\BrevoWhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Mailables\Attachment;


class PreventiveExpirationAlert extends Mailable
{
    use Queueable, SerializesModels;

    public Preventive $preventive;
    public ?Email $email; // Rendi email opzionale

    public function __construct(Preventive $preventive, ?Email $email = null) // Costruttore opzionale per lastEmail
    {
        $this->preventive = $preventive;
        $this->email = $email;  // Se lastEmail è null, non verrà passato nulla
    }

    public function envelope(): Envelope
    {
        return new Envelope(

            subject: 'Alert Preventivo Scaduto n. ' . $this->preventive->numero . '/' . $this->preventive->anno,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.preventivi.expired-alert', // Usa una vista per il contenuto dell'email
            with: [
                'preventive' => $this->preventive,
                'referente' => $this->preventive->creator?->nome . ' ' . $this->preventive->creator?->cognome,
                'telefono_referente' => $this->preventive->creator?->telefono, // O ->phone
            ]
        );
    }
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
        $this->subject('Nuovo Preventivo ' . $this->preventive->numero . '/' . $this->preventive->anno);

        // Logica opzionale per WhatsApp
        try {
            $service = new BrevoWhatsAppService();

            $to = $this->preventive->customer->telefono; // Assicurati che sia nel formato +39xxx
            if ($this->preventive->allego_file === true || $this->preventive->allego_file === 1) {
                // Link diretto all'allegato email
                $pdfUrl = route('preventivo.show.allegato', $this->preventive->cod_alfa);
            } else {
                // Link alla pagina web del preventivo
                $pdfUrl = url('/preventivi/' . $this->preventive->cod_alfa);
            }
            $templateId = 137; // ID del template WhatsApp

            $params = [
                'nome' => $this->preventive->customer->nome . ' ' . $this->preventive->customer->cognome,
                'preventivo' => $pdfUrl,
                'destinazione' => $this->preventive->meta_viaggio ?? 'Destinazione',
                'riscontro' => url('/preventivi/' . $this->preventive->cod_alfa . '/risposta'),
            ];

            // Invia il messaggio WhatsApp
            $service->sendTemplate($to, $templateId, $params);
        } catch (\Throwable $e) {
            \Log::error('Errore invio WhatsApp (reminder): ' . $e->getMessage());
        }

        return $this;
    }
}
