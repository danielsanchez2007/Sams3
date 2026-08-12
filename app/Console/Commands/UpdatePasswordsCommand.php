<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdatePasswordsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passwords:update-bcrypt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all existing passwords to use Bcrypt algorithm';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating passwords to Bcrypt format...');
        
        $users = User::all();
        $updatedCount = 0;
        $skippedCount = 0;
        
        foreach ($users as $user) {
            // Check if password is already bcrypt (starts with $2y$)
            if (str_starts_with($user->password, '$2y$')) {
                $this->line("User {$user->email} - Password already in Bcrypt format");
                $skippedCount++;
                continue;
            }
            
            // If password is plain text or other format, convert to bcrypt
            if (strlen($user->password) < 60) {
                $user->password = Hash::make($user->password);
                $user->save();
                $this->info("Updated password for user: {$user->email}");
                $updatedCount++;
            } else {
                $this->line("User {$user->email} - Password already hashed");
                $skippedCount++;
            }
        }
        
        $this->info('Password update completed!');
        $this->info("Updated: {$updatedCount} users");
        $this->info("Skipped: {$skippedCount} users");
        
        return 0;
    }
}
