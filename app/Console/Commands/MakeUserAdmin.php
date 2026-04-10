<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeUserAdmin extends Command
{
    protected $signature = 'user:make-admin {email}';

    protected $description = 'Otorgar permisos de administrador a un usuario';

    public function handle()
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (!$user) {
            $this->error("No se encontro un usuario con el email: {$this->argument('email')}");
            return 1;
        }

        $user->is_admin = true;
        $user->save();
        $this->info("Usuario {$user->name} ({$user->email}) ahora es administrador.");
        return 0;
    }
}
