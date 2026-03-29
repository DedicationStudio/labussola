<?php

namespace App\Http\Controllers;

use App\Models\Preventive;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PreventivoController extends Controller
{
    public function risposta($cod_alfa)
    {
        $preventivo = Preventive::where('cod_alfa', $cod_alfa)->first();

        return view('emails.preventivi.risposta', ['preventivo' => $preventivo]);
    }


    public function salvaRisposta(Request $request, $cod_alfa)
    {
        $preventivo = Preventive::where('cod_alfa', $cod_alfa)->first();

        $validated = $request->validate([
            'stato' => 'required|string',  //name input in html
            'stato_altro_testo' => 'nullable|string|max:1000' //name input in html
        ]);

        $preventivo->stato = $validated['stato'];

        if ($validated['stato'] === 'altro') {
            $preventivo->stato_altro_testo = $validated['stato_altro_testo'];
        } else {
            $preventivo->stato_altro_testo = '';
        }

        $preventivo->save();

        return redirect()->back()->with('success', 'La Sua risposta è stata inviata con successo.');
    }

    public function show($cod_alfa)
    {
        $preventive = Preventive::where('cod_alfa', $cod_alfa)->first();

        return view('preventivo_pdf.preventivo', [
            'preventivo' => $preventive->toPdfArray(),
        ]);
    }

   public function showPolizza($cod_alfa)
{
    $preventive = Preventive::where('cod_alfa', $cod_alfa)->firstOrFail();

    // Trova il primo extra service di tipo "Polizza"
    $polizza = $preventive->extra_services()
        ->whereHas('extra_service', function ($q) {
            $q->where('tipo', 'Polizza');
        })
        ->with('extra_service')
        ->first();

    if (!$polizza || empty($polizza->extra_service->allegati)) {
        abort(404, 'Nessuna polizza trovata per questo preventivo.');
    }

    $file = $polizza->extra_service->allegati[0];

    // Se il file è nel disco "public"
    if (Storage::disk('public')->exists($file)) {
        return response()->file(Storage::disk('public')->path($file));
    }

    abort(404, 'File non trovato.');
}

public function showAllegato($cod_alfa)
{
    $preventive = Preventive::where('cod_alfa', $cod_alfa)->firstOrFail();

   

    if ($preventive->allego_file) {
       

        $file_preventivo = $preventive->file_preventivo ?? null;
        if ($file_preventivo) {
            $allegato = storage_path('app/public/' . $file_preventivo);

            if (file_exists($allegato)) {
                $ext = strtolower(pathinfo($allegato, PATHINFO_EXTENSION));
                $mime = match($ext) {
                    'pdf' => 'application/pdf',
                    'doc' => 'application/msword',
                    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    default => 'application/octet-stream'
                };

                return response()->file($allegato, [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="' . basename($allegato) . '"',
                ]);
            }
        }
    }

    abort(404, 'File non trovato.');
}

public function downloadAllegato($cod_alfa)
{
    $preventive = Preventive::where('cod_alfa', $cod_alfa)->firstOrFail();
    

   if($preventive->allego_file) {
 $file_preventivo = $preventive->file_preventivo ?? null;
        if ($file_preventivo) {
 $fullPath = storage_path('app/public/' . $file_preventivo);
        }
   }
 
    
   
    
    if (!file_exists($fullPath)) {
        abort(404, 'File non trovato');
    }
    
    return response()->download($fullPath);
}
}
