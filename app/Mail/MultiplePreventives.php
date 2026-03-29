<?php

namespace App\Mail;

use App\Models\Email;
use App\Models\Preventive;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\BrevoWhatsAppService;

class MultiplePreventives extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */

    public Email $email;

    public function __construct(Email $email)
    {
        $this->email = $email;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Offerta Preventivi',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $customer = $this->email->customer;

        if ($customer) {
            switch ($customer->tipo_cliente) {
                case 'privato':
                    if ($customer->genere === 'donna') {
                        $titolo = 'Gent.ma';
                    } elseif ($customer->genere === 'uomo') {
                        $titolo = 'Gent.mo';
                    } else {
                        $titolo = 'Gentile';
                    }
                    $cliente = trim(($customer->nome ?? '') . ' ' . ($customer->cognome ?? ''));
                    break;
                case 'azienda':
                    $titolo = 'Spett.le';
                    $cliente = $customer->ragione_sociale ?? 'Cliente';
                    break;
                case 'scuola':
                    $titolo = 'Spett.le';
                    $cliente = $customer->nome ?? 'Cliente';
                    break;
                default:
                    $cliente = 'Cliente';
                    break;
            }
        } else {
            $cliente = 'Cliente';
        }


        return new Content(
            view: 'emails.preventivi.multiplo',
            with: [
                'titolo' => $titolo,
                'cliente' => $cliente,
                'corpo_email' => $this->email->corpo_email,
                'preventivi' => $this->email->preventives,
                'allegati' => $this->email->allegati ?? [],
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
        $attachments = [];


        // ciclo sui preventivi legati all'email
        foreach ($this->email->preventives as $preventivo) {

            if ($preventivo->allego_file === false || $preventivo->allego_file === 0) {

                $preventivo = Preventive::with([
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
                ])->find($preventivo->id);
                try {
                    // PDF ottimizzato
                    $pdf = Pdf::loadView('preventivo_pdf.file', [
                        'preventivo' => $preventivo->toPdfArray(),
                    ])
                        ->setPaper('A4')
                        ->setOption('dpi', 96);

                    $pdfContent = $pdf->output();

                    // Verifica dimensione PDF
                    if (strlen($pdfContent) < 10 * 1024 * 1024) { // < 10MB
                        $attachments[] = Attachment::fromData(
                            fn() => $pdfContent,
                            "preventivo_{$preventivo->cod_alfa}.pdf"
                        )->withMime('application/pdf');
                    } else {
                        \Log::error("PDF troppo grande: " . strlen($pdfContent) . " bytes");
                    }

                } catch (\Exception $e) {
                    \Log::error("Errore generazione PDF: " . $e->getMessage());
                }



                foreach ($this->email->preventives as $preventivo) {

                    foreach ($preventivo->extra_services as $service) {
                        if (strtolower($service->extra_service?->tipo) === 'polizza') {
                            $allegati = $service->extra_service?->allegati;

                            if (is_array($allegati)) {
                                foreach ($allegati as $file) {
                                    $path = storage_path('app/public/' . $file);
                                    if (file_exists($path)) {
                                        $attachments[] = Attachment::fromPath($path)
                                            ->as('File_Polizza_' . basename($file));
                                    }
                                }
                            } elseif (is_string($allegati)) {
                                $path = storage_path('app/public/' . $allegati);
                                if (file_exists($path)) {
                                    $attachments[] = Attachment::fromPath($path)
                                        ->as('File_Polizza_' . basename($allegati));
                                }
                            }
                        }
                    }
                }

                if (!empty($this->email->allegati)) {
                    foreach ($this->email->allegati as $allegato) {
                        $attachments[] = Attachment::fromPath(
                            storage_path('app/public/' . $allegato)
                        );
                    }
                }
            } else {
                if ($preventivo->file_preventivo) {
                    $attachments[] = Attachment::fromPath(
                            storage_path('app/public/' . $preventivo->file_preventivo)
                        );
                   
                }
                if (is_iterable($this->email?->allegati)) {
                    foreach ($this->email->allegati as $allegato) {
                        $attachments[] = Attachment::fromPath(
                            storage_path('app/public/' . $allegato)
                        );
                    }
                }
            }
        }



        return $attachments;
    }
    public function build()
    {
        $this->subject('Nuovi Preventivi');

        try {
            $service = new BrevoWhatsAppService();

            foreach ($this->email->preventives as $preventivo) {

                $customer = $preventivo->customer;

                $to = $customer->telefono;

                $templateId = 133;

                $params = [
                    'nome' => trim(($customer->nome ?? '') . ' ' . ($customer->cognome ?? '')),
                    'destinazione' => $preventivo->meta_viaggio ?? 'Destinazione',
                    'preventivo' => url('/preventivi/' . $preventivo->cod_alfa),
                    'scadenza' => optional($preventivo->date_expiration)->format('d/m/Y') ?? '-',
                    'riscontro' => url('/preventivi/' . $preventivo->cod_alfa . '/risposta'),
                ];

                // INVIA il WhatsApp per questo preventivo
                $service->sendTemplate($to, $templateId, $params);
            }

        } catch (\Throwable $e) {
            \Log::error('Errore invio WhatsApp multiplo: ' . $e->getMessage());
        }

        return $this;
    }


}
