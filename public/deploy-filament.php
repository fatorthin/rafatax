<?php
/**
 * Deployment script for shared hosting
 * WARNING: Protect this file with password or delete after use!
 */

// Set a password to protect this script
$deployPassword = 'GANTI_DENGAN_PASSWORD_YANG_KUAT';

// Check if password is provided and correct
if (!isset($_GET['password']) || $_GET['password'] !== $deployPassword) {
    die('Unauthorized access');
}

// Function to execute artisan commands
function runArtisanCommand($command) {
    $output = [];
    $return_var = 0;
    
    // Use the correct path to your artisan file
    $artisan = dirname(__DIR__) . '/artisan';
    
    exec("php $artisan $command 2>&1", $output, $return_var);
    
    return [
        'command' => $command,
        'output' => $output,
        'status' => $return_var === 0 ? 'Success' : 'Failed'
    ];
}

// Run Filament optimize command
$result = runArtisanCommand('filament:optimize');

// Also run other optimization commands
$optimize = runArtisanCommand('optimize');
$clearCompiled = runArtisanCommand('clear-compiled');
$configCache = runArtisanCommand('config:cache');
$routeCache = runArtisanCommand('route:cache');
$viewCache = runArtisanCommand('view:cache');

// Display results
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Filament Deployment Script</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        h1 { color: #3b82f6; }
        pre { background: #f1f5f9; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .success { color: #10b981; }
        .failed { color: #ef4444; }
        .command { font-weight: bold; margin-bottom: 5px; }
    </style>
</head>
<body>
    <h1>Filament Deployment Script</h1>
    <p>Optimizing Filament and Laravel for production...</p>
    
    <h2>Filament Optimize</h2>
    <div class="command">php artisan <?php echo $result['command']; ?></div>
    <div class="<?php echo $result['status'] === 'Success' ? 'success' : 'failed'; ?>">
        Status: <?php echo $result['status']; ?>
    </div>
    <pre><?php echo implode("\n", $result['output']); ?></pre>
    
    <h2>Other Optimizations</h2>
    <?php foreach ([$optimize, $clearCompiled, $configCache, $routeCache, $viewCache] as $cmd): ?>
        <div class="command">php artisan <?php echo $cmd['command']; ?></div>
        <div class="<?php echo $cmd['status'] === 'Success' ? 'success' : 'failed'; ?>">
            Status: <?php echo $cmd['status']; ?>
        </div>
        <pre><?php echo implode("\n", $cmd['output']); ?></pre>
    <?php endforeach; ?>
    
    <p><strong>IMPORTANT:</strong> Delete this file after use for security reasons!</p>
</body>
</html> 