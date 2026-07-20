<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Buat akun admin dari CLI — alternatif tinker di host yang menonaktifkan
 * shell_exec() (mis. Hostinger). Bisa lewat opsi (non-interaktif) atau prompt.
 *
 * Contoh:
 *   php artisan lms:create-admin --email=admin@stienus.ac.id --password=RAHASIA --name="Administrator"
 */
class CreateAdmin extends Command
{
    protected $signature = 'lms:create-admin
        {--name= : Nama lengkap admin}
        {--email= : Email login}
        {--password= : Kata sandi (min 6 karakter)}';

    protected $description = 'Buat akun admin baru (tanpa tinker)';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Nama admin', 'Administrator');
        $email = strtolower(trim((string) ($this->option('email') ?: $this->ask('Email login'))));
        $password = (string) ($this->option('password') ?: $this->ask('Kata sandi (min 6 karakter)'));

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:6'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $err) {
                $this->error($err);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password, // di-hash otomatis oleh cast 'hashed'
            'role' => User::ROLE_ADMIN,
        ]);

        $this->info("Akun admin dibuat: {$user->email} (id {$user->id}). Silakan login.");

        return self::SUCCESS;
    }
}
