use App\Models\User;
use App\Models\Rol;
use Illuminate\Support\Facades\Hash;

// Buscar el rol Super Admin
$rol = Rol::where('nombre', 'Super Admin')->first();

if (!$rol) {
    echo "Error: Rol 'Super Admin' no encontrado\n";
    exit;
}

// Crear usuario de prueba
$user = User::create([
    'usuario' => 'admin',
    'email' => 'admin@unitepc.edu.bo',
    'password' => Hash::make('admin123'),
    'rol_id' => $rol->id,
    'personal_id' => null,
    'estado' => true,
    'password_changed_at' => now(), // Ya cambió contraseña
]);

echo "Usuario creado exitosamente!\n";
echo "Usuario: admin\n";
echo "Contraseña: admin123\n";
echo "Email: admin@unitepc.edu.bo\n";
echo "Rol: " . $rol->nombre . "\n";
echo "ID: " . $user->id . "\n";
