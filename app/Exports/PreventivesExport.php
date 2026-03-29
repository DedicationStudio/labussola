<?php

namespace App\Exports;

use App\Models\Preventive;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class PreventivesExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping, WithStyles
{
    public function __construct(
        public ?array $ids = null,
        public ?array $columns = null,
    ) {
    }

    public function collection(): Collection
    {
        $query = Preventive::query()
            ->with(['creator', 'customer']); // Carica le relazioni

        if ($this->ids) {
            $query->whereIn('id', $this->ids);
        }

        $cols = $this->columns ?: [
            'id',
            'numero',
            'anno',
            'tag',
            'titolo',
            'created_by',
            'customer_id',
            'email_cliente',
            'data_preventivo',
            'meta_viaggio',
            'nome_itinerario',
            'data_inizio_viaggio',
            'data_fine_viaggio',
            'numero_persone',
            'numero_gratuita',
            'prezzo_per_persona',
            'markup',
            'totale_incasso',
            'stato',
        ];

        return $query->get($cols);
    }

    public function headings(): array
    {
        $headings = $this->columns ?: [
            'id',
            'numero',
            'anno',
            'tag',
            'titolo',
            'created_by',
            'customer_id',
            'email_cliente',
            'data_preventivo',
            'meta_viaggio',
            'nome_itinerario',
            'data_inizio_viaggio',
            'data_fine_viaggio',
            'numero_persone',
            'numero_gratuita',
            'prezzo_per_persona',
            'markup',
            'totale_incasso',
            'stato',
        ];


        $translations = [
            'id' => 'ID',
            'numero' => 'NUMERO',
            'anno' => 'ANNO',
            'tag' => 'TAG',
            'titolo' => 'TITOLO',
            'created_by' => 'CREATO DA',
            'customer_id' => 'CLIENTE',
            'email_cliente' => 'EMAIL CLIENTE',
            'data_preventivo' => 'DATA PREVENTIVO',
            'meta_viaggio' => 'META VIAGGIO',
            'nome_itinerario' => 'NOME ITINERARIO',
            'data_inizio_viaggio' => 'DATA INIZIO VIAGGIO',
            'data_fine_viaggio' => 'DATA FINE VIAGGIO',
            'numero_persone' => 'NUMERO PERSONE',
            'numero_gratuita' => 'NUMERO GRATUITÀ',
            'prezzo_per_persona' => 'PREZZO PER PERSONA',
            'markup' => 'MARKUP',
            'totale_incasso' => 'TOTALE INCASSO',
            'stato' => 'STATO',
        ];

        // Converte in maiuscolo leggibile (es. TOTALE_INCASSO → TOTALE INCASSO)
        return collect($headings)
            ->map(fn($h) => $translations[$h] ?? strtoupper(str_replace('_', ' ', $h)))
            ->toArray();
    }

    public function map($preventive): array
    {
        return [
            $preventive->id,
            $preventive->numero,
            $preventive->anno,
            $preventive->tag,
            $preventive->titolo,
            $preventive->creator
            ? trim($preventive->creator->nome . ' ' . $preventive->creator->cognome)
            : $preventive->created_by,
            $preventive->customer
            ? trim($preventive->customer->nome . ' ' . ($preventive->customer->cognome ?? ''))
            : $preventive->customer_id,
            $preventive->customer->email,
            optional($preventive->data_preventivo)?->format('d/m/Y'),
            $preventive->meta_viaggio,
            $preventive->nome_itinerario,
            optional($preventive->data_inizio_viaggio)?->format('d/m/Y'),
            optional($preventive->data_fine_viaggio)?->format('d/m/Y'),
            $preventive->numero_persone,
            $preventive->numero_gratuita,
            $preventive->prezzo_per_persona,
            $preventive->markup,
            $preventive->totale_incasso,
            $preventive->stato instanceof \BackedEnum
            ? $preventive->stato->value
            : $preventive->stato,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Intestazioni in grassetto e colorate
        $sheet->getStyle('A1:Z1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000'],
                'size' => 11,
            ],
            /*  'fill' => [
                 'fillType' => 'solid',
                 'color' => ['rgb' => '1E88E5'], // sfonfo blu della riga titoli
             ], */
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
        ]);

        // Bordo sottile su tutte le celle con dati
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => 'thin',
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ]);

        // Colonne specifiche formattate (es. totale incasso come valuta)
        foreach (range('A', $highestColumn) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Colonna "E" (totale_incasso) formattata come valuta
        $sheet->getStyle('E2:E' . $highestRow)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00 €');

        return [];
    }
}
