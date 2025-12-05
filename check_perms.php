<?php

use App\Models\User;
use App\Models\Rol;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = User::where('usuario', 'juanjo')->get();

if ($users->isEmpty()) {
    echo "User 'juanjo' not found.\n";
    exit;
}

foreach ($users as $user) {
    echo "ID: " . $user->id . "\n";
    echo "User: " . $user->usuario . "\n";
    echo "Role ID: " . $user->rol_id . "\n";
    echo "Role Name: " . ($user->rol ? $user->rol->nombre : 'None') . "\n";
    echo "------------------------\n";
}
// exit; // Stop here for now


if ($user->rol) {
    echo "Permissions count: " . $user->rol->permisos->count() . "\n";
    foreach ($user->rol->permisos as $permiso) {
        echo " - " . $permiso->clave . "\n";
    }
} else {
    echo "No role assigned.\n";
}

echo "\nChecking Role 3 (Super Admin):\n";
$role3 = Rol::find(3);
if ($role3) {
    echo "Role: " . $role3->nombre . "\n";
    echo "Permissions count: " . $role3->permisos->count() . "\n";
} else {
    echo "Role 3 not found.\n";
}

echo "\nChecking Role 4 (ADMINPRUEBA):\n";
$role4 = Rol::find(4);
if ($role4) {
    echo "Role: " . $role4->nombre . "\n";
    echo "Permissions count: " . $role4->permisos->count() . "\n";
    foreach ($role4->permisos as $permiso) {
        echo " - " . $permiso->clave . "\n";
    }
} else {
    echo "Role 4 not found.\n";
}


