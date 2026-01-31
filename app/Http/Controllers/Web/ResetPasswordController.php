<?php

namespace App\Http\Controllers\Web;

use App\Domain\User\Actions\Auth\ResetPasswordAction;
use App\Domain\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ResetPasswordController
{
    public function show(Request $request, string $token): View
    {
        $email = $request->query('email');

        if (! $this->tokenIsValid($token, $email)) {
            return view('auth.reset-password-invalid');
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    public function update(Request $request, string $token, ResetPasswordAction $action): View
    {
        $email = $request->input('email');

        if (! $this->tokenIsValid($token, $email)) {
            return view('auth.reset-password-invalid');
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return view('auth.reset-password-invalid');
        }

        $action->execute($user, $validated['password']);

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return view('auth.reset-password-success');
    }

    private function tokenIsValid(string $token, ?string $email): bool
    {
        if (! $email) {
            return false;
        }

        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (! $record) {
            return false;
        }

        if (! hash_equals($record->token, hash('sha256', $token))) {
            return false;
        }

        $expiresAt = now()->subMinutes(60);

        return $record->created_at >= $expiresAt;
    }
}
