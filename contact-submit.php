<?php
/* Contact form endpoint: validates, stores the lead, emails the team. JSON for fetch(), redirect without JS. */
require __DIR__ . '/inc/bootstrap.php';

$ajax = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

function done(bool $ok, string $code = ''): never
{
    global $ajax;
    if ($ajax) {
        http_response_code($ok ? 200 : 422);
        header('Content-Type: application/json');
        echo json_encode(['ok' => $ok, 'error' => FORM_ERRORS[$code] ?? '']);
    } else {
        header('Location: contact.php?' . ($ok ? 'sent=1' : 'error=' . $code) . '#contact-form');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: contact.php'); exit; }

// Bots fill the hidden field; pretend it worked.
if (trim((string)($_POST['website'] ?? '')) !== '') done(true);

$f = fn(string $k, int $max) => mb_substr(trim(preg_replace('/[\x00-\x1F\x7F]/u', ' ', (string)($_POST[$k] ?? ''))), 0, $max);
$lead = [
    'name' => $f('name', 120),
    'company' => $f('company', 160),
    'email' => $f('email', 160),
    'phone' => $f('phone', 40),
    'services' => implode(', ', array_slice(array_map(fn($s) => mb_substr(trim((string)$s), 0, 60), (array)($_POST['services'] ?? [])), 0, 12)),
    'industry' => $f('industry', 80),
    'budget' => $f('budget', 80),
    'message' => mb_substr(trim((string)($_POST['message'] ?? '')), 0, 5000),
];

if ($lead['name'] === '' || $lead['company'] === '' || $lead['message'] === '') done(false, 'required');
if (!filter_var($lead['email'], FILTER_VALIDATE_EMAIL)) done(false, 'email');

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$recent = db()->prepare("SELECT COUNT(*) FROM leads WHERE ip = ? AND created_at > ?");
$recent->execute([$ip, date('Y-m-d H:i:s', time() - 3600)]);
if ($recent->fetchColumn() >= 5) done(false, 'rate');

$lead += ['created_at' => date('Y-m-d H:i:s'), 'ip' => $ip, 'user_agent' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)];
insert_row(db(), 'leads', $lead);

$to = setting('notify_email');
if ($to && filter_var($to, FILTER_VALIDATE_EMAIL)) {
    $host = preg_replace('/[^\w.-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    $body = "New enquiry from the website\n\n";
    foreach (['name' => 'Name', 'company' => 'Company', 'email' => 'Email', 'phone' => 'Phone', 'services' => 'Needs',
              'industry' => 'Industry', 'budget' => 'Budget'] as $k => $label) {
        if ($lead[$k] !== '') $body .= str_pad($label . ':', 10) . $lead[$k] . "\n";
    }
    $body .= "\n" . $lead['message'] . "\n\n— Sent " . $lead['created_at'] . " from " . $host;
    $headers = "From: " . setting('site_name', 'Website') . " <no-reply@" . preg_replace('/^www\./', '', $host) . ">\r\n"
        . "Reply-To: " . $lead['email'] . "\r\nContent-Type: text/plain; charset=UTF-8";
    @mail($to, '=?UTF-8?B?' . base64_encode('New enquiry: ' . $lead['name'] . ' — ' . $lead['company']) . '?=', $body, $headers);
}

done(true);
