<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Honeypot: if filled, silently succeed
if (!empty($_POST['website'])) {
    echo json_encode(['success' => true, 'message' => 'Message sent!']);
    exit;
}

$name    = isset($_POST['name'])    ? trim(strip_tags($_POST['name']))    : '';
$message = isset($_POST['message']) ? trim(strip_tags($_POST['message'])) : '';

if (empty($name) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Name and message are required']);
    exit;
}

if (strlen($message) > 2000) {
    echo json_encode(['success' => false, 'message' => 'Message too long']);
    exit;
}

// Load credentials: prefer /home/private/.env, fall back to repo .env
$env_paths = ['/home/private/.env', dirname(dirname(__FILE__)) . '/.env'];
$token = null;
$chat_id = null;

foreach ($env_paths as $path) {
    if (file_exists($path)) {
        $env = parse_ini_file($path);
        if (!empty($env['TELEGRAM_BOT_TOKEN']) && !empty($env['TELEGRAM_CHAT_ID'])) {
            $token   = $env['TELEGRAM_BOT_TOKEN'];
            $chat_id = $env['TELEGRAM_CHAT_ID'];
            break;
        }
    }
}

if (!$token || !$chat_id) {
    echo json_encode(['success' => false, 'message' => 'Server configuration missing']);
    exit;
}

$text = "Message from morganrivers.com\nName: " . $name . "\n\n" . $message;

$url  = "https://api.telegram.org/bot" . $token . "/sendMessage";
$body = http_build_query(['chat_id' => $chat_id, 'text' => $text]);
$ctx  = stream_context_create(['http' => [
    'method'  => 'POST',
    'header'  => "Content-Type: application/x-www-form-urlencoded\r\nContent-Length: " . strlen($body) . "\r\n",
    'content' => $body,
    'timeout' => 10,
]]);

$result = @file_get_contents($url, false, $ctx);

if ($result === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
} else {
    $resp = json_decode($result, true);
    if ($resp && $resp['ok']) {
        echo json_encode(['success' => true, 'message' => 'Message sent!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Telegram API error']);
    }
}
?>
