<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;


class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('admin', function (User $pro, User $pacient){
            if($pro->role !== 'pro' || $pacient->role !== 'pacient'){
                return false;
            }

            $linked = $pro->patients()->where('users.id', $pacient->id)->exists();
            $consent = optional($pacient->profile)->consent_share_with_professional;

            return $linked && $consent; 
        });
    }
}
