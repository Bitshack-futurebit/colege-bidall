<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$appRoot = '/usr/home/bidalujwfq';

// Check debug log
$logFile = $appRoot . '/storage/logs/broadcast-debug.log';
if (file_exists($logFile)) {
    echo '<h3>Controller debug log:</h3><pre>' . htmlspecialchars(file_get_contents($logFile)) . '</pre><hr>';
}

// Get CSRF token
require $appRoot . '/vendor/autoload.php';
$app = require $appRoot . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());
$token = csrf_token();
?>
<h2>Raw Broadcast Test (no JS)</h2>
<form method="POST" action="/admin/broadcast">
    <input type="hidden" name="_token" value="<?= $token ?>">

    <label><input type="checkbox" name="recipients[]" value="all_users" checked> All Users</label><br><br>

    <label>Subject:</label><br>
    <input type="text" name="subject" value="Test Broadcast" style="width:400px"><br><br>

    <label>Message:</label><br>
    <textarea name="message" style="width:400px;height:100px">Hello {name}, this is a test broadcast.</textarea><br><br>

    <button type="submit" style="padding:10px 20px;background:blue;color:white;border:none;cursor:pointer">
        Send Broadcast (raw form)
    </button>
</form>
