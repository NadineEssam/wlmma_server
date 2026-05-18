<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyUsersOfActivity
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        //
        // Fetch all users
        $users = User::all();

        // Create the notification
        $notificationData = [
            'title' => 'New Activity Added!',
            'body' => "A new activity has been added: {$event->activity->name}",
            'type' => 'activity', // Optional type for categorization
        ];

        // Notify all users
        foreach ($users as $user) {
            $user->notify(new ActivityNotification($notificationData));
        }
    }
}
