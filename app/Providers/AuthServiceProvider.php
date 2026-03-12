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
        Gate::define('view-patient', function (User $pro, User $patient){
            if($pro->role !== 'pro' || $patient->role !== 'patient'){
                return false;
            }
        
            $linked = $pro->patients()->where('users.id', $patient->id)->exists();
            $consent = optional($patient->profile)->consent_share_with_professional;
        
            return $linked && $consent; 
        });
    }
}
