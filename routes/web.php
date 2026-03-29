<?php

use App\Http\Controllers\Api\Auth\ForgotPasswordUserController;
use App\Http\Controllers\PreventivoController;
use App\Http\Controllers\RequestWpController;
use App\Models\Preventive;
use Illuminate\Support\Facades\Route;
use Spatie\Browsershot\Browsershot;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification as FilamentNotification;
use App\Notifications\PersonalNotification;
use App\Models\User;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Storage;
use LivewireFilemanager\Filemanager\Http\Controllers\Files\FileController;

Route::get('/download-file/{path}', function ($path) {
    return Storage::disk('public')->download($path);
})
->where('path', '.*')
->name('download.file');

Route::get('/download/{path}', function ($path) {
    abort_unless(Storage::disk('public')->exists($path), 404);

    return response()->download(
        Storage::disk('public')->path($path)
    );
})->where('path', '.*')->name('download.file');

Route::get('/view-file/{path}', function ($path) {

    abort_unless(Storage::disk('public')->exists($path), 404);

    return Storage::disk('public')->response($path);
})
->where('path', '.*')
->name('view.file');


Route::get('reset-password/{token}/{email}', [ForgotPasswordUserController::class, 'showResetPasswordForm'])->name('res.password.get');
Route::post('reset-password', [ForgotPasswordUserController::class, 'submitResetPassword'])
    ->name('reset.password');

Route::get('/preventivi/{cod_alfa}', [PreventivoController::class, 'show'])->name('preventivo.show');

Route::get('/polizza/{cod_alfa}', [PreventivoController::class, 'showPolizza'])->name('polizza.show');


Route::get('/preventivi/{cod_alfa}/risposta', [PreventivoController::class, 'risposta'])->name('preventivo.risposta');

Route::post('/preventivi/{cod_alfa}/risposta', [PreventivoController::class, 'salvaRisposta'])->name('preventivo.risposta.salva');

Route::get('/preventivi/{preventivo}/pdf', function (Preventive $preventivo) {
ini_set('memory_limit', '512M');   // oppure '-1' per illimitato
set_time_limit(120);   
    $pdf = Pdf::loadView('preventivo_pdf.file', [
        'preventivo' => $preventivo->toPdfArray(),
    ]);


    // Puoi scaricare direttamente il file
    return $pdf->download("preventivo_{$preventivo->numero}.pdf");


})->name('preventivi.pdf');

Route::get('/preventivi/{id}/view', [PreventiveController::class, 'show'])
     ->name('preventivi.show');

Route::get('/preventivi/{id}/edit', [PreventiveController::class, 'edit'])
     ->name('preventivi.edit');

/* Route::get('/example',function() {


    return view("preventivo_pdf.example");


}); */




/* 
Route::get('/test-notification/{id}', function ($id) {
    // 1️ Recupera l'utente per ID
    $user = User::find($id);
    if (! $user) {
        return "Nessun utente trovato con ID {$id}.";
    }

    // 2️ Dati della notifica
    $title = 'Richiesta di preventivo';
    $body  = 'Hai ricevuto una nuova richiesta di preventivo!';

    // 3️Invia la notifica Laravel classica
    NotificationFacade::send(
        [$user],
        new PersonalNotification(
            title: $title,
            body: $body,
            type: 'quote_request',
        )
    );

    // 4️ Crea anche la notifica Filament (campanella 🔔)
    FilamentNotification::make()
    ->title($title)
    ->body($body)
    ->success()
    ->sendToDatabase($user) // salva nel DB (campanella)
    ->send();

    return " Notifiche inviate con successo all'utente ID {$id}!";
}); */
Route::get('/preventivo/{cod_alfa}/allegato', [PreventivoController::class, 'showAllegato'])->name('preventivo.show.allegato');
Route::get('/preventivo/{cod_alfa}/allegato/download', [PreventivoController::class, 'downloadAllegato'])
    ->name('preventivo.download.allegato');
   
Route::get('/{preventivo}/pdf', function (Preventive $preventivo) {
    $pdf = Pdf::loadView('preventivo_pdf.file', [
        'preventivo' => $preventivo->toPdfArray(),
    ]);

    return $pdf->stream("preventivo_{$preventivo->code_alfa}.pdf");
}); 
Route::get('{path}', [FileController::class, 'show'])->where('path', '.*')->name('assets.show');
/*
Browsershot
Route::get('/preventivi/{preventivo}/pdf', function (Preventive $preventivo) {
    $html = view('preventivo_pdf.preventivo', [
    'preventivo' => $preventivo->toPdfArray(),
])->render();


return Pdf::html($html)
    ->format('A4')
    ->withBrowsershot(function (Browsershot $browsershot) {
        $browsershot->timeout(180)->waitUntilNetworkIdle()->noSandbox();
    })
    ->download("preventivo_{$preventivo->id}.pdf");

})->name('preventivi.pdf'); */










