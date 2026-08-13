<?php

namespace App\Http\Controllers\Sso;

use App\Http\Controllers\Controller;
use App\Models\SsoAuthorizationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    /**
     * Memulai proses SSO.
     */
    public function login(Request $request)
    {
        /*
         * Generate state random untuk mencegah CSRF.
         */
        $state = Str::random(64);

        /*
         * Simpan state di session ZH Picture.
         *
         * Nanti callback akan membandingkan state
         * yang diterima dari Central Login dengan state ini.
         */
        $request->session()->put('sso_state', $state);

        /*
         * URL Central Login.
         */
        $authorizeUrl = config('sso.authorize_url');

        /*
         * Redirect URI yang sudah didaftarkan
         * di Central Login.
         */
        $redirectUri = config('sso.redirect_uri');

        /*
         * Buat URL menuju Central Login.
         */
        $query = http_build_query([
            'client_id' => config('sso.client_id'),
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);

        return redirect()->away(
            $authorizeUrl . '?' . $query
        );
    }

    /**
     * Callback dari Central Login.
     */
    public function callback(Request $request)
    {
        /*
         * Kalau Central Login mengembalikan error,
         * jangan lanjutkan proses login.
         */
        if ($request->filled('error')) {
            abort(
                401,
                $request->input('error_description')
                    ?? 'SSO authentication gagal.'
            );
        }

        /*
         * Authorization code wajib ada.
         */
        $request->validate([
            'code' => ['required', 'string'],
            'state' => ['required', 'string'],
        ]);

        /*
         * Validasi state.
         */
        $sessionState = $request->session()->pull('sso_state');

        if (
            !$sessionState ||
            !hash_equals($sessionState, $request->input('state'))
        ) {
            abort(403, 'SSO state tidak valid.');
        }

        /*
         * Hash authorization code yang diterima.
         *
         * Central Login menyimpan SHA-256 hash,
         * bukan authorization code asli.
         */
        $codeHash = hash(
            'sha256',
            $request->input('code')
        );

        /*
         * Cari authorization code.
         */
        $authorizationCode = SsoAuthorizationCode::query()
            ->where('code_hash', $codeHash)
            ->where('client_id', config('sso.client_id'))
            ->where('redirect_uri', config('sso.redirect_uri'))
            ->first();

        /*
         * Code tidak ditemukan.
         */
        if (!$authorizationCode) {
            abort(401, 'Authorization code tidak valid.');
        }

        /*
         * Pastikan code belum pernah digunakan.
         */
        if ($authorizationCode->used_at !== null) {
            abort(401, 'Authorization code sudah digunakan.');
        }

        /*
         * Pastikan code belum expired.
         */
        if ($authorizationCode->expires_at->isPast()) {
            abort(401, 'Authorization code sudah kedaluwarsa.');
        }

        /*
         * Ambil user dari database yang sama.
         */
        $user = $authorizationCode->user;

        if (!$user) {
            abort(401, 'User tidak ditemukan.');
        }

        /*
         * Tandai authorization code sebagai sudah digunakan
         * sebelum melakukan login.
         */
        $authorizationCode->update([
            'used_at' => now(),
        ]);

        /*
         * Login user ke session ZH Picture.
         */
        Auth::login($user);

        /*
         * Regenerate session ID setelah authentication.
         */
        $request->session()->regenerate();

        /*
         * Redirect ke dashboard ZH Picture.
         */
        return redirect()->route('dashboard');
    }
}