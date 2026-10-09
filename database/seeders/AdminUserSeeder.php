<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * The premade admin account. Login details come from .env (ADMIN_EMAIL / ADMIN_PASSWORD),
     * so the password never ends up in git. Running it again updates the same account.
     */
    public function run(): void
    {
        $email = config('services.admin.email');
        $password = config('services.admin.password');

        if (! $email || ! $password) {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD are not set in .env, no admin created.');

            return;
        }

        $admin = User::firstOrNew(['email' => $email]);
        $admin->forceFill([
            'name' => $admin->name ?? 'Administrator',
            'password' => $password,
            'language' => $admin->language ?? 'lv',
            'is_admin' => true,
            'email_verified_at' => $admin->email_verified_at ?? now(),
        ])->save();

        $this->command?->info("Admin account: {$email}");
    }
}
