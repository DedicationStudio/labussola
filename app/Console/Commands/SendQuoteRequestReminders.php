<?php

namespace App\Console\Commands;

use App\Filament\Resources\QuoteRequestResource;
use App\Models\CustomNotification;
use App\Models\User;
use App\Notifications\PersonalNotification;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\QuoteRequest;
use Filament\Notifications\Actions\Action;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Exception\MessagingException;
use Throwable;
use Illuminate\Support\Facades\Notification as NotificationFacade;


class SendQuoteRequestReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-quote-request-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $targetDate = Carbon::now()->addDays(2)->toDateString();

        $requests = QuoteRequest::with(['agenti_gestori'])
            ->whereDate('scadenza', $targetDate)
            ->get();



        foreach ($requests as $request) {
            $usersToNotify = collect();


            if ($request->agenti_gestori->isNotEmpty()) {
                $usersToNotify = $usersToNotify->merge($request->agenti_gestori);
            }

            $admins = User::whereHas('role', function ($query) {
                $query->whereIn('nome', ['admin, superadmin']);
            })->get();

            $usersToNotify = $usersToNotify->mergge($admins);
            $usersToNotify = $usersToNotify->unique('id');

            foreach ($usersToNotify as $user) {
                if (!$user || !$user->fcm_token || $user->hasMuteNotifications()) {
                    continue;
                }

                $title = 'Promemoria scadenza richiesta';
                $body = "La richiesta #{$request->id} scade il {$request->scadenza->format('d/m/Y')}.";






                $notification = CustomNotification::create([
                    'title' => $title,
                    'body' => $body,
                ]);
                $notification->users()->syncWithoutDetaching([$user->id]);

                $user->notify(new PersonalNotification(
                    title: $title,
                    body: $body,
                ));
                NotificationFacade::send(
                    $user,
                    new PersonalNotification(
                        title: $title,
                        body: $body,
                        type: 'quote_request',
                    )
                );

                \Filament\Notifications\Notification::make()
                    ->title($title)
                    ->body($body)
                    ->success()
                    ->actions([
                        Action::make('Track Order')
                            ->label("Vedi Richiesta")
                            ->button()
                            ->url(
                                QuoteRequestResource::getUrl('view', ['record' => $request->id]),
                                shouldOpenInNewTab: true
                            ),
                    ])
                    ->sendToDatabase($user)
                    ->send();

                $credentialsPath = base_path(config('services.firebase.credentials', env('FIREBASE_CREDENTIALS')));
                $messaging = (new Factory)
                    ->withServiceAccount($credentialsPath)
                    ->createMessaging();

                $message = CloudMessage::fromArray([
                    'token' => $user->fcm_token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => [
                        'type' => 'quote_request_reminder',
                        'quote_request_id' => (string) $request->id,
                        'notification_id' => (string) $notification->id,
                    ],
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'channel_id' => 'high_importance_channel',
                        ],
                    ],
                    'apns' => [
                        'headers' => [
                            'apns-priority' => '10', // 10 = immediata
                        ],
                        'payload' => [
                            'aps' => [
                                'alert' => [
                                    'title' => $title,
                                    'body' => $body,
                                ],
                                'sound' => 'default', // Usa il suono di sistema
                            ],
                        ],
                    ],
                ]);

                try {
                    $messaging->send($message);
                    $this->info("Push inviata a {$user->email} per richiesta #{$request->id}");
                } catch (MessagingException | Throwable $e) {
                    report($e);
                    $this->error("Errore invio notifica per richiesta #{$request->id}");
                }
            }


        }
        return self::SUCCESS;
    }

}
