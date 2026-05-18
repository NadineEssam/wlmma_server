<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AssignUserCodes extends Command
{
    // 👇 this defines the artisan command name
    protected $signature = 'users:assign-codes';

    protected $description = 'Assign random unique codes to users without one';

    public function handle()
    {
        $users = User::whereNull('code')->get();
        $count = 0;

        foreach ($users as $user) {
            $user->code = $this->generateUniqueCode();
            $user->save();
            $count++;
        }

        $this->info("✅ Assigned codes to {$count} users.");
    }

    private function generateUniqueCode()
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::where('code', $code)->exists());

        return $code;
    }
}
