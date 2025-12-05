foreach (App\Models\Rol::all() as $rol) {
    echo $rol->id . ': ' . $rol->nombre . "\n";
}
