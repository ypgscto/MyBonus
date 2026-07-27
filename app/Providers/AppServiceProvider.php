<?php

namespace App\Providers;

use App\Models\PresenterRequest;
use App\Models\User;
use App\Policies\KeuanganPresenterRequestPolicy;
use App\Policies\VerifikatorPresenterRequestPolicy;
use Illuminate\Support\Facades\Gate;
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
        foreach (['payment_proofs', 'verifikator_transfers', 'presenter_transfers'] as $folder) {
            $path = storage_path('uploads/'.$folder);
            if (! is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }

        $verifikatorPolicy = VerifikatorPresenterRequestPolicy::class;

        Gate::define('verifikator-view-any', fn (User $user) => app($verifikatorPolicy)->viewAny($user));
        Gate::define('verifikator-view', fn (User $user, PresenterRequest $request) => app($verifikatorPolicy)->view($user, $request));
        Gate::define('verifikator-reject', fn (User $user, PresenterRequest $request) => app($verifikatorPolicy)->reject($user, $request));
        Gate::define('verifikator-approve', fn (User $user, PresenterRequest $request) => app($verifikatorPolicy)->approve($user, $request));
        Gate::define('verifikator-transfer', fn (User $user, PresenterRequest $request) => app($verifikatorPolicy)->transfer($user, $request));
        Gate::define('download-payment-proof', fn (User $user, PresenterRequest $request) => app($verifikatorPolicy)->downloadPaymentProof($user, $request));
        Gate::define('verifikator-download-transfer-proof', fn (User $user, PresenterRequest $request) => app($verifikatorPolicy)->downloadVerifikatorTransferProof($user, $request));

        $keuanganPolicy = KeuanganPresenterRequestPolicy::class;

        Gate::define('keuangan-view-any', fn (User $user) => app($keuanganPolicy)->viewAny($user));
        Gate::define('keuangan-view', fn (User $user, PresenterRequest $request) => app($keuanganPolicy)->view($user, $request));
        Gate::define('keuangan-confirm-received', fn (User $user, PresenterRequest $request) => app($keuanganPolicy)->confirmReceived($user, $request));
        Gate::define('keuangan-transfer-to-presenter', fn (User $user, PresenterRequest $request) => app($keuanganPolicy)->transferToPresenter($user, $request));
        Gate::define('keuangan-close', fn (User $user, PresenterRequest $request) => app($keuanganPolicy)->close($user, $request));
        Gate::define('keuangan-download-verifikator-proof', fn (User $user, PresenterRequest $request) => app($keuanganPolicy)->downloadVerifikatorTransferProof($user, $request));
        Gate::define('keuangan-download-presenter-proof', fn (User $user, PresenterRequest $request) => app($keuanganPolicy)->downloadPresenterTransferProof($user, $request));

        Gate::define(
            'download-presenter-transfer-proof',
            fn (User $user, PresenterRequest $request) => app(\App\Policies\PresenterRequestPolicy::class)->downloadPresenterTransferProof($user, $request)
        );

        $presenterPolicy = \App\Policies\PresenterOwnsRequestPolicy::class;

        Gate::define('presenter-view-transfer-proof', fn (User $user, PresenterRequest $request) => app($presenterPolicy)->viewTransferProof($user, $request));
    }
}
