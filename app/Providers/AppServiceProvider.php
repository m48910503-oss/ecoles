<?php

namespace App\Providers;

use App\Services\Core\ShellService;
use App\Services\Core\SchoolService;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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
        $this->removeStaleViteHotFile();

        Paginator::useTailwind();

        CarbonImmutable::setLocale(config('app.locale'));

        View::composer('layouts.app', function ($view): void {
            if (! Auth::check()) {
                return;
            }

            $view->with('shell', app(ShellService::class)->buildFor(Auth::user()));
            $view->with('schoolSwitcher', app(SchoolService::class)->switcherData(Auth::user()));
        });
    }

    private function removeStaleViteHotFile(): void
    {
        $hotFile = public_path('hot');

        if (! is_file($hotFile)) {
            return;
        }

        $url = trim((string) @file_get_contents($hotFile));

        if ($url === '') {
            @unlink($hotFile);

            return;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['host'])) {
            @unlink($hotFile);

            return;
        }

        $scheme = $parts['scheme'] ?? 'http';
        $host = $parts['host'];
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        $targetHost = str_contains($host, ':') ? "[{$host}]" : $host;

        $connection = @stream_socket_client(
            "tcp://{$targetHost}:{$port}",
            $errorCode,
            $errorMessage,
            0.2
        );

        if ($connection === false) {
            @unlink($hotFile);

            return;
        }

        fclose($connection);
    }
}
