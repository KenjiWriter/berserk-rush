<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\SocialLoginPasswordGenerated;
use Exception;

class SocialLoginController extends Controller
{
    // Tymczasowo wyłączone Apple Login (wymaga płatnego konta dewelopera)
    protected array $allowedProviders = ['google', 'facebook' /*, 'apple'*/];

    public function redirect($provider)
    {
        if (!in_array($provider, $this->allowedProviders)) {
            abort(404);
        }

        if ($provider === 'facebook') {
            return Socialite::driver($provider)->scopes([
                'user_gender', 'user_birthday', 'user_age_range', 'user_location', 'user_hometown', 'user_link'
            ])->redirect();
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback($provider)
    {
        if (!in_array($provider, $this->allowedProviders)) {
            abort(404);
        }

        try {
            $driver = Socialite::driver($provider)->stateless();

            if ($provider === 'facebook') {
                $driver->fields([
                    'name', 'email', 'gender', 'birthday', 'location', 'hometown', 'age_range', 'link'
                ]);
            }
            
            // Obejście problemu z certyfikatami SSL (cURL 77/60) na lokalnym serwerze (np. Windows/Laragon)
            if (app()->environment('local')) {
                $driver->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));
            }

            $socialUser = $driver->user();
        } catch (Exception $e) {
            dd('Błąd logowania Socialite:', $e->getMessage(), $e->getTraceAsString());
        }

        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            // Zaktualizuj providera jeśli jeszcze nie przypisany (lub aktualizuj dane)
            if (!$user->auth_provider) {
                $user->update([
                    'auth_provider' => $provider,
                    'auth_provider_id' => $socialUser->getId(),
                ]);
            }
        } else {
            // Utwórz nowego usera
            $generatedPassword = Str::password(32, true, true, true, false);

            $userData = [
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'Bohater' . rand(1000, 9999),
                'email' => $socialUser->getEmail(),
                'password' => Hash::make($generatedPassword),
                'auth_provider' => $provider,
                'auth_provider_id' => $socialUser->getId(),
                'game_stage' => 0, // Domyślny etap
                'is_social_setup_pending' => true,
            ];

            if ($provider === 'facebook' && isset($socialUser->user)) {
                $fbUser = $socialUser->user;
                $userData['gender'] = $fbUser['gender'] ?? null;
                
                if (isset($fbUser['birthday'])) {
                    try {
                        $userData['birthday'] = \Carbon\Carbon::createFromFormat('m/d/Y', $fbUser['birthday'])->format('Y-m-d');
                    } catch (Exception $e) {
                        // ignore parse error
                    }
                }
                
                $userData['age_range'] = isset($fbUser['age_range']) ? json_encode($fbUser['age_range']) : null;
                $userData['location'] = $fbUser['location']['name'] ?? null;
                $userData['hometown'] = $fbUser['hometown']['name'] ?? null;
                $userData['profile_url'] = $fbUser['link'] ?? null;
            }

            $user = User::create($userData);

            // Wyślij e-mail z hasłem
            Mail::to($user->email)->send(new SocialLoginPasswordGenerated($generatedPassword));
        }

        Auth::login($user, true);
        session()->regenerate();

        return redirect()->route('homepage');
    }
}
