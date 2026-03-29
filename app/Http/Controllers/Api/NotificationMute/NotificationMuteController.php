<?php

namespace App\Http\Controllers\Api\NotificationMute;

use App\Http\Controllers\Controller;
use App\Models\NotificationMute;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationMuteController extends Controller
{
    public function mute(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:3days,1week,custom',
            'custom_hours' => 'nullable|integer|min:1|max:720',
            'custom_days' => 'nullable|integer|min:1|max:365',
            'start_at' => 'nullable|date',
            'end_at'   => 'nullable|date|after:start_at',
        ]);

        $user = Auth::user();
        $start = $request->start_at ? Carbon::parse($request->start_at) : now();

        switch ($request->type) {
            case '3days':
                $end =  $start->copy()->addDays(3);
                break;
            case '1week':
                $end =  $start->copy()->addWeek();
                break;
            case 'custom':
                if ($request->end_at) {
                    $end = Carbon::parse($request->end_at);
                } else if ($request->custom_hours) {
                    $end = $start->copy()->addHours($request->custom_hours);
                } else if ($request->custom_days) {
                    $end = $start->copy()->addDays($request->custom_days);
                    /* $start = now(); // 2025-09-06 12:00

$end1 = $start->addDays(3);
// $start = 2025-09-09 12:00 (mutato)
// $end1  = 2025-09-09 12:00

$start = now(); // reset: 2025-09-06 12:00

$end2 = $start->copy()->addDays(3);
// $start = 2025-09-06 12:00 (invariato)
// $end2  = 2025-09-09 12:00 */
                }
                break;
        }

        $mute = NotificationMute::updateOrCreate(
            [
                'user_id' => $user->id,
            ],
            [
                'enabled'  => true,
                'start_at' => $start,
                'end_at'   => $end,
            ]
        );
        return response()->json([
            'muted'    => $mute->isActive(),
            'enabled'  => $mute->enabled,
            'start_at' => $mute->start_at,
            'end_at'   => $mute->end_at,
        ]);
    }

    public function status()
    {
        $mute = Auth::user()->notificationMute;

        if (!$mute) { //se non c'è nessun record definiamo le notifiche attive
            return response()->json([
                'muted' => false, //non silenziate
                'enabled' =>  false,
                'start_at' => null,
                'end_at' => null,
            ]);
        }

        return response()->json([
            'muted'    => $mute->isActive(),   // true solo se esiste e valido
            'enabled'  => $mute->enabled,
            'start_at' => $mute->start_at,
            'end_at'   => $mute->end_at,
        ]);
    }

    public function unmute()
    {
        $mute = NotificationMute::where('user_id', Auth::id())->first();

        if ($mute) {
            $mute->update([
                'enabled' => false,
                'start_at' => null,
                'end_at' => null,
            ]);
        }

        return response()->json([
            'muted' => false,
            'enabled' => false,
            'start_at' => null,
            'end_at' => null,
        ]);
    }
}
