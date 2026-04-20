<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $minutes = (int) config('auth.passwords.users.expire', 60);

        ResetPassword::createUrlUsing(function ($user, string $token) {
            $base = rtrim((string) config('app.url'), '/');

            return $base.'/atsistatyti-slaptazodi?'.http_build_query([
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]);
        });

        ResetPassword::toMailUsing(function ($notifiable, string $token) use ($minutes) {
            $base = rtrim((string) config('app.url'), '/');
            $url = $base.'/atsistatyti-slaptazodi?'.http_build_query([
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);

            return (new MailMessage)
                ->subject('Slaptažodžio atkūrimas')
                ->line('Gavome prašymą atkurti jūsų paskyros slaptažodį.')
                ->action('Atkurti slaptažodį', $url)
                ->line("Ši nuoroda galioja {$minutes} min. Jei neprašėte atkūrimo, ignoruokite šį laišką.");
        });

        if (!app()->runningInConsole() && app()->environment('production')) {
            $forwardedProto = request()->header('x-forwarded-proto');
            $shouldForceHttps = $forwardedProto === 'https' || env('FORCE_HTTPS', true);

            if ($shouldForceHttps) {
                URL::forceScheme('https');
            }
        }
    }
}
