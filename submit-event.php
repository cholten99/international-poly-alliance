<?php
// Handles the "Add an Event" form: emails the submission to Dave via Brevo.
// No database -- every submission just gets reviewed by email and added to
// events.html by hand.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /add-event.html');
    exit;
}

// Honeypot: a real visitor never fills this in (hidden via CSS). Bots that
// blindly fill every field will trip it. Pretend success either way so we
// don't tip off the bot.
if (!empty($_POST['website'])) {
    header('Location: /add-event.html?submitted=1');
    exit;
}

function field($name) {
    return trim($_POST[$name] ?? '');
}

$title    = field('title');
$dates    = field('dates');
$location = field('location');
$frequency = field('frequency');
$notes    = field('notes');
$link     = field('link');
$organizer_email = field('organizer_email');

if ($title === '' || $dates === '' || $location === '') {
    header('Location: /add-event.html?error=1');
    exit;
}

if ($link !== '' && !filter_var($link, FILTER_VALIDATE_URL)) {
    header('Location: /add-event.html?error=1');
    exit;
}

if ($organizer_email !== '' && !filter_var($organizer_email, FILTER_VALIDATE_EMAIL)) {
    header('Location: /add-event.html?error=1');
    exit;
}

$allowed_frequencies = ['Annual', 'Bi-annual', 'One-time', 'Monthly', 'Other'];
if (!in_array($frequency, $allowed_frequencies, true)) {
    $frequency = 'Other';
}

$rows = [
    'Event Title' => $title,
    'Dates' => $dates,
    'Location / Venue' => $location,
    'Frequency' => $frequency,
    'Notes' => $notes,
    'Reference / Website Link' => $link,
    'Organizer Contact Email' => $organizer_email,
];

$html = '<html><body><h2>New event submitted for IPA Events page</h2><table cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse;font-family:sans-serif">';
foreach ($rows as $label => $value) {
    $html .= '<tr><td><b>' . htmlspecialchars($label) . '</b></td><td>' . nl2br(htmlspecialchars($value)) . '</td></tr>';
}
$html .= '</table></body></html>';

$api_key = trim(file_get_contents(__DIR__ . '/.brevo-api-key'));

$payload = json_encode([
    'sender' => ['name' => 'IPA Events Form', 'email' => 'dave@bowsy.co.uk'],
    'to' => [['email' => 'dave@bowsy.co.uk', 'name' => 'Dave']],
    'subject' => 'New IPA event submission: ' . $title,
    'htmlContent' => $html,
]);

$ch = curl_init('https://api.brevo.com/v3/smtp/email');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'api-key: ' . $api_key,
        'Content-Type: application/json',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
]);
$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status >= 200 && $status < 300) {
    header('Location: /add-event.html?submitted=1');
} else {
    error_log('IPA event submission email failed: HTTP ' . $status . ' ' . $response);
    header('Location: /add-event.html?error=1');
}
exit;
