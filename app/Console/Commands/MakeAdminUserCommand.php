<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('users:make-admin {name} {email} {password}')]
#[Description('Create a new SCAS admin user account.')]
class MakeAdminUserCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = User::create([
            'name' => $this->textArgument('name'),
            'email' => $this->textArgument('email'),
            'password' => Hash::make($this->textArgument('password')),
            'role' => UserRole::Admin,
        ]);

        $this->info("Admin user created: {$user->email}");

        return self::SUCCESS;
    }

    /**
     * A text argument; required arguments always arrive as strings.
     */
    private function textArgument(string $name): string
    {
        $value = $this->argument($name);

        return is_string($value) ? $value : '';
    }
}
