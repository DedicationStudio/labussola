<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ForgetPasswordApi;
use App\Mail\ResetPasswordApi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Illuminate\Support\Str;


class ForgotPasswordUserController extends Controller
{
    public function submitForgetPassword(Request $request)
    {
        // 1) Valida input
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        // 2) Verifica utente
        $user = User::where('email', $validated['email'])->first();
        if (! $user) {
            return response()->json([
                'message' => 'We can’t find a user with that email address.'
            ], 404);
        }

        // 3) Genera/salva token (uno per email)
        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $validated['email']],
            ['token' => $token, 'created_at' => Carbon::now()]
        );


        $resetUrl = route('res.password.get', [
            'token' => $token,
            'email' => $validated['email'],
        ]);

        Mail::to($validated['email'])->send(new ResetPasswordApi(
            email: $validated['email'],
            resetUrl: $resetUrl
        ));

        return response()->json([
            'message' => 'We have e-mailed your password reset link!'
        ]);
    }




    public function showForgetPasswordForm(): View
    {
        return view('api.forgetPassword');
    }


    public function showResetPasswordForm($token, $email): View
    {
        $data = [
            'email' => $email,
            'token' => $token,
        ];
        return view('api.form_reset_password', ['data' => $data]);
    }


    public function submitResetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $updatePassword = DB::table('password_reset_tokens')
            ->where([
                'email' => $request->email,
                'token' => $request->token
            ])
            ->first();

        if (!$updatePassword) {
            return "<h1>Invalid token</h1>";
        }
        $user = User::where('email', $request->email)
            ->update(['password' => Hash::make($request->password)]);

        DB::table('password_reset_tokens')->where(['email' => $request->email])->delete();
        return "<h1>Password cambiata con successo</h1>";
    }
}
