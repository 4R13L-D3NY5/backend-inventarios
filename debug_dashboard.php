try {
    $controller = app()->make(App\Http\Controllers\DashboardController::class);
    $response = $controller->index();
    echo "SUCCESS";
} catch (\Throwable $e) {
    echo "ERROR_MSG: " . $e->getMessage() . "\n";
}
