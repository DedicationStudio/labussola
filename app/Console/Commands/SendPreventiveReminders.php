<?php

namespace App\Console\Commands;

use App\Mail\PreventiveReminderMail;
use App\Models\Email;
use App\Models\Preventive;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Services\BrevoWhatsAppService;

class SendPreventiveReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-preventive-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invia email di promemoria per i preventivi in scadenza';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $target_date = Carbon::now();
            \Log::info("Comando 'send-preventive-reminders' eseguito per la data {$target_date}");

$reminder_date = Carbon::now()->addDays(2)->toDateString();

        $preventives = Preventive::where('stato', 'in attesa')
            ->whereDate('date_expiration', $reminder_date)
            ->get();
            \Log::info("Trovati " . $preventives->count() . " preventivi per la data {$target_date}");


        if ($preventives->isEmpty()) {
            $this->info('Nessun preventivo in scadenza per oggi.');
            return Command::SUCCESS;
        }

        $whatsapp = new BrevoWhatsAppService();

        foreach ($preventives as $preventive) {
            $lastEmail = $preventive->emails()->latest()->first();

            if ($lastEmail && $lastEmail->email_cliente) {
                try {
                    Mail::to($lastEmail->email_cliente)
                        ->send(new PreventiveReminderMail($preventive, $lastEmail));

                    

                    $this->info("Reminder inviato a {$lastEmail->email_cliente} per preventivo #{$preventive->id}");

                } catch (\Exception $e) {
                    \Log::error('Errore invio email di promemoria per il preventivo #' . $preventive->id . ': ' . $e->getMessage());
                }
            } else {
                \Log::warning("Nessuna email trovata per il preventivo #" . $preventive->id);
            }
        }

         return self::SUCCESS;
    }
}
