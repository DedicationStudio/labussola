<?php

use App\Http\Controllers\Api\AgentAvailability\AgentAvailabilityController;
use App\Http\Controllers\Api\Agents\AgentsController;
use App\Http\Controllers\Api\Auth\ChangePasswordUserController;
use App\Http\Controllers\Api\Auth\DeleteUserController;
use App\Http\Controllers\Api\Auth\ForgotPasswordUserController;
use App\Http\Controllers\Api\Auth\LoginUserController;
use App\Http\Controllers\Api\Auth\UserController;
use App\Http\Controllers\Api\Customer\CustomerController;
use App\Http\Controllers\Api\Notification\NotificationController;
use App\Http\Controllers\Api\NotificationMute\NotificationMuteController;
use App\Http\Controllers\Api\QuoteRequest\QuoteRequestController;
use App\Http\Controllers\Api\TypeRequest\TypeRequestController;
use App\Http\Controllers\RequestWpController;
use App\Models\NotificationMute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*Route::middleware('api')->get('/ping', function () {
    return response()->json(['message' => 'API is working']);
});
 */
Route::post('/request-wp', [RequestWpController::class, 'store']);


Route::middleware('guest')->group(function () {
    Route::post('/login', [LoginUserController::class, 'store']);
    Route::get('/forget-password', [ForgotPasswordUserController::class, 'showForgetPasswordForm'])->name('forget.password.get');
    Route::post('/forget-password', [ForgotPasswordUserController::class, 'submitForgetPassword'])->name('forget.password.post');
    Route::get('/reset-password/{token}/{email}', [ForgotPasswordUserController::class, 'showResetPasswordForm'])->name('reset.password.get');
    Route::post('/reset-password', [ForgotPasswordUserController::class, 'submitResetPassword'])->name('reset.password');
});



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::put('/save-tokenFCM', [LoginUserController::class, 'saveToken']);
    Route::post('/change-password', [ChangePasswordUserController::class, 'update']);
    Route::post('/logout', [LoginUserController::class, 'destroy']);
    Route::delete('/destroy/{id}', [DeleteUserController::class, 'destroy']);

    //update_n_preventivi
    Route::put('/user/n-preventivi', [UserController::class, 'updatePreventivi']);

    //update-profilo
    Route::put('/update-user', [UserController::class, 'update']);


    //richieste
    Route::get('/richieste-admin', [QuoteRequestController::class, 'index']);
    Route::get('/richieste-archiviate', [QuoteRequestController::class, 'archiviate']);
    Route::get('/richieste/{id}', [QuoteRequestController::class, 'show']);
    Route::get('/richiesta/{id}', [QuoteRequestController::class, 'edit']);
    Route::post('/crea-richiesta', [QuoteRequestController::class, 'store']);
    Route::post('/assegna-richiesta', [QuoteRequestController::class, 'storeWithAgents']);
    Route::put('/modifica-richiesta/{id}', [QuoteRequestController::class, 'update']);
    Route::delete('/rimuovi-richiesta/{id}', [QuoteRequestController::class, 'delete']);
    Route::put('/notifica-richiesta/{id}', [QuoteRequestController::class, 'notificaassegnazione']);


    //tipo richieste
    Route::get('/tipo-richieste', [TypeRequestController::class, 'index']);
    Route::post('/crea-tipologia', [TypeRequestController::class, 'store']);



    //clienti
    Route::get('/clienti', [CustomerController::class, 'index']);
    Route::post('/crea-cliente', [CustomerController::class, 'store']);


    //disponibilità agente
    Route::get('/show-availability/{id}', [AgentAvailabilityController::class, 'show']);
    Route::get('/index-availability', [AgentAvailabilityController::class, 'index']);
    Route::post('/store/agent-availability', [AgentAvailabilityController::class, 'store']);
    Route::put('/availability/{id}', [AgentAvailabilityController::class, 'update']);
    Route::delete('/delete-availability/{id}', [AgentAvailabilityController::class, 'destroy']);
    Route::put('/calendar/availability/{id}', [AgentAvailabilityController::class, 'updateFromCalendar']);
    Route::post('/calendar/agent-availability', [AgentAvailabilityController::class, 'storeFromCalendar']);




    //lista-agenti-per-admin
    Route::get('/agents-all', [AgentsController::class, 'index']);


    //notifiche
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/delete-notifications', [NotificationController::class, 'destroy']);

    //notifiche silenziate
    Route::post('/notifiche-disattivate', [NotificationMuteController::class, 'mute']);
    Route::post('/notifiche-attivate', [NotificationMuteController::class, 'unmute']);
    Route::get('/stato-notifiche', [NotificationMuteController::class, 'status']);



    Route::get('/request-wps', [RequestWpController::class, 'index']);
    Route::delete('/request-wps/{id}', [RequestWpController::class, 'destroy']);
});
