<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;

class FirebaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('firebase', function () {
            return (new Factory)
                ->withServiceAccount(config('firebase.credentials'))
                ->withDatabaseUri('https://noti-dabd7-default-rtdb.firebaseio.com/');
        });


        // $json_path = base_path("/touring-client-firebase.json");
        // $factory = (new Factory)
        //     ->withServiceAccount($json_path)
        //     ->withDatabaseUri('https://touring-client-default-rtdb.firebaseio.com');
    }
}
