<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Supplier;

class FixSupplierJson extends Command
{
    protected $signature = 'supplier:fix-json';
    protected $description = 'Corregge i campi JSON (email, telefono, sito_web, portale_web, allegati) nei fornitori esistenti';

    public function handle()
    {
        $suppliers = Supplier::all();

        foreach ($suppliers as $supplier) {
            $fields = ['email', 'telefono', 'sito_web', 'portale_web', 'allegati'];

            foreach ($fields as $field) {
                $value = $supplier->{$field};

                if (is_string($value)) {
                    $decoded = json_decode($value, true);

                    // Se non è array valido o contiene "NULL", resetto a []
                    if (!is_array($decoded) || $decoded === ["NULL"]) {
                        $supplier->{$field} = [];
                    } else {
                        $supplier->{$field} = $decoded;
                    }
                }
            }

            $supplier->save();
        }

        $this->info('Campi JSON dei fornitori corretti con successo!');
        return 0;
    }
}
