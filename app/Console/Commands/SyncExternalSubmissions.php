<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ExternalRecord;
use App\Models\RequestWp;

class SyncExternalSubmissions extends Command
{
    protected $signature = 'submissions:sync {--submission_id=}';
    protected $description = 'Sync submissions from external DB into normalized table';

    public function handle()
    {
        $submissionId = $this->option('submission_id');

        $query = ExternalRecord::select('submission_id')->distinct();

        if ($submissionId) {
            $query->where('submission_id', $submissionId);
            $this->info("🔄 Sync submission {$submissionId}");
        } else {
            $this->info('🔄 Sync ALL submissions');
        }

        // mappa tutte le chiavi note ai campi finali
        $keyMap = [
            'nome'       => 'nome',
            'info_nome'  => 'nome',
            'name'       => 'nome',

            'cognome'       => 'cognome',
            'info_cognome'  => 'cognome',
            'surname'       => 'cognome',
            'lastname'      => 'cognome',

            'email'        => 'email',
            'info_email'   => 'email',

            'telefono'       => 'telefono',
            'info_telefono'  => 'telefono',
            'phone'          => 'telefono',

            'messaggio'      => 'messaggio',
            'info_messaggio' => 'messaggio',
            'message'        => 'messaggio',

            'acc_privacy'        => 'acc_privacy',
            'info_acc_privacy'   => 'acc_privacy',
            'acc_newsletter'     => 'acc_newsletter',
            'info_acc_newsletter'=> 'acc_newsletter',
            'acc_whatsapp'       => 'acc_whatsapp',
            'info_acc_whatsapp'  => 'acc_whatsapp',

            'citta_scuola' => 'citta_scuola',
            'classe'       => 'classe',
            'grado'        => 'grado',
            'telefono_scuola' => 'telefono_scuola',
            'ruolo'        => 'ruolo',
            'scuola'       => 'scuola',

            'durata'       => 'durata',
            'trasporto'    => 'trasporto',
            'meta_viaggio' => 'meta_viaggio',
            'citta_partenza' => 'citta_partenza',
            'num_studenti' => 'num_studenti',
            'num_docenti'  => 'num_docenti',
            'disabili'     => 'disabili',
            'periodo'      => 'periodo',
            'altre_info'   => 'altre_info',
        ];

        $query->chunk(200, function ($rows) use ($keyMap) {
            foreach ($rows as $row) {
                $allFields = ExternalRecord::where('submission_id', $row->submission_id)->get();

                // campi mappati
                $fields = $allFields
                    ->filter(fn($item) => !empty($item->value) && isset($keyMap[$item->key]))
                    ->mapWithKeys(fn($item) => [$keyMap[$item->key] => $item->value])
                    ->toArray();

                

                RequestWp::updateOrCreate(
                    ['submission_id' => $row->submission_id],
                    array_merge(
                        $fields,
                        [
                            
                            'updated_at' => now(),
                        ]
                    )
                );
            }
        });

        $this->info('Sync completato');
    }
}
