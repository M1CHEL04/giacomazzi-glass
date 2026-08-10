<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Santiago Michel',
            'email' => 'santymichel016@gmail.com',
            'password' => Hash::make('santi123'),
            'cambio_contraseña' => true,
        ]);

        User::create([
            'name' => 'Azul',
            'email' => '@gmail.com',
            'password' => Hash::make('azul123'),
            'cambio_contraseña' => false,
        ]);

        User::create([
            'name' => 'Javier',
            'email' => ' javier@aberturasgiacomazzi.com.ar',
            'password' => Hash::make('admin123'),
            'cambio_contraseña' => false,
        ]);

        User::create([
            'name' => 'Cristian',
            'email' => 'cristian@aberturasgiacomazzi.com.ar',
            'password' => Hash::make('admin123'),
            'cambio_contraseña' => false,
        ]);

        User::create([
            'name' => 'Sergio',
            'email' => 'sergio@aberturasgiacomazzi.com.ar',
            'password' => Hash::make('admin123'),
            'cambio_contraseña' => false,
        ]);

        User::create([
            'name' => 'Damian',
            'email' => 'damian@aberturasgiacomazzi.com.ar',
            'password' => Hash::make('admin123'),
            'cambio_contraseña' => false,
        ]);

        User::create([
            'name' => 'Ventas',
            'email' => 'ventas@aberturasgiacomazzi.com.ar',
            'password' => Hash::make('admin123'),
            'cambio_contraseña' => false,
        ]);

        User::create([
            'name' => 'Presupuestos',
            'email' => 'presupuestos@aberturasgiacomazzi.com.ar',
            'password' => Hash::make('admin123'),
            'cambio_contraseña' => false,
        ]);

        User::create([
            'name' => 'Administracion',
            'email' => 'administracion@aberturasgiacomazzi.com.ar',
            'password' => Hash::make('admin123'),
            'cambio_contraseña' => false,
        ]);
    }
}
