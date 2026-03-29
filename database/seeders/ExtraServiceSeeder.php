<?php

namespace Database\Seeders;

use App\Models\ExtraService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ExtraServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $basePath = public_path('icone');
        $options = [];

        foreach (File::directories($basePath) as $directory) {
            $category = basename($directory);
            $categoryLabel = ucfirst($category);

            foreach (File::files($directory) as $file) {
                if ($file->getExtension() === 'png') {
                    $slug = $file->getFilenameWithoutExtension();
                    $nome = ucfirst($slug);

                    // Salva o recupera il servizio
                    $service = ExtraService::firstOrCreate(
                        ['nome' => $nome],
                        [
                            'supplier_id' => null,
                            'allegati'    => null,
                        ]
                    );

                    // Costruisce la struttura categorie => [id => nome]
                    $options[$categoryLabel][$service->id] = $service->nome;
                }
            }
        }

        //  Se vuoi vedere l’output quando lanci il seeder
        dump($options);
    }
}
