<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use App\Listeners\ApiResponseWasMorphedListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'Dingo\Api\Event\ResponseWasMorphed' => [
            ApiResponseWasMorphedListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
