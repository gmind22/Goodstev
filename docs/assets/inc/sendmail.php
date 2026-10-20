<?php

require_once('phpmailer/class.phpmailer.php');
require_once('phpmailer/class.smtp.php');

header('Content-Type: application/json; charset=utf-8');

$response = array(
    'message' => 'An unexpected error occurred. Please try again later.',
    'status' => 'false'
);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode($response);
    exit;
}

$name = isset($_POST['form_name']) ? trim($_POST['form_name']) : '';
$email = isset($_POST['form_email']) ? trim($_POST['form_email']) : '';
$phone = isset($_POST['form_phone']) ? trim($_POST['form_phone']) : '';
$subject = isset($_POST['form_subject']) ? trim($_POST['form_subject']) : '';
$formMessage = isset($_POST['form_message']) ? trim($_POST['form_message']) : '';
$formType = isset($_POST['form_type']) ? trim($_POST['form_type']) : 'General Enquiry';
$botcheck = isset($_POST['form_botcheck']) ? trim($_POST['form_botcheck']) : '';

if ($botcheck !== '') {
    $response['message'] = 'Your submission could not be accepted.';
    echo json_encode($response);
    exit;
}

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $formMessage === '') {
    $response['message'] = 'Please provide your name, a valid email address, and a message.';
    echo json_encode($response);
    exit;
}

if ($subject === '') {
    $subject = $formType;
}

// Keep each valid submission in a server-side folder protected from web access.
$backupDirectory = __DIR__ . '/form-backups';
$backupSaved = is_dir($backupDirectory) || @mkdir($backupDirectory, 0700, true);
if ($backupSaved) {
    $backupRecord = array(
        'received_at' => gmdate('c'),
        'form_type' => $formType,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'subject' => $subject,
        'message' => $formMessage,
        'source' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''
    );
    $backupJson = json_encode($backupRecord, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $backupSaved = $backupJson !== false
        && @file_put_contents($backupDirectory . '/submissions.jsonl', $backupJson . PHP_EOL, FILE_APPEND | LOCK_EX) !== false;
}

$mail = new PHPMailer();
$mail->CharSet = 'UTF-8';

$smtpHost = trim((string) getenv('GOODSTEV_SMTP_HOST'));
$smtpUsername = trim((string) getenv('GOODSTEV_SMTP_USERNAME'));
$smtpPassword = (string) getenv('GOODSTEV_SMTP_PASSWORD');
if ($smtpHost !== '' && $smtpUsername !== '' && $smtpPassword !== '') {
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUsername;
    $mail->Password = $smtpPassword;
    $mail->SMTPSecure = trim((string) getenv('GOODSTEV_SMTP_SECURE')) ?: 'ssl';
    $mail->Port = (int) (getenv('GOODSTEV_SMTP_PORT') ?: 465);
} else {
    $mail->isMail();
}

$toemail = trim((string) (getenv('GOODSTEV_CONTACT_RECIPIENT') ?: 'info@goodstev.com'));
if (!filter_var($toemail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(503);
    $response['message'] = 'The contact form is not configured yet. Please call us instead.';
    echo json_encode($response);
    exit;
}

$toname = 'Goodstev Website';

$safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$safePhone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
$safeFormType = htmlspecialchars($formType, ENT_QUOTES, 'UTF-8');
$safeMessage = nl2br(htmlspecialchars($formMessage, ENT_QUOTES, 'UTF-8'));
$referrer = isset($_SERVER['HTTP_REFERER'])
    ? htmlspecialchars($_SERVER['HTTP_REFERER'], ENT_QUOTES, 'UTF-8')
    : '';

$body = 'Form type: ' . $safeFormType . '<br><br>'
    . 'Name: ' . $safeName . '<br><br>'
    . 'Email: ' . $safeEmail . '<br><br>'
    . 'Phone: ' . $safePhone . '<br><br>'
    . 'Subject: ' . $safeSubject . '<br><br>'
    . 'Message: ' . $safeMessage;

if ($referrer !== '') {
    $body .= '<br><br>Submitted from: ' . $referrer;
}

$mail->SetFrom($toemail, $toname);
$mail->AddReplyTo($email, $name);
$mail->AddAddress($toemail, $toname);
$mail->Subject = str_replace(array("\r", "\n"), ' ', $subject);
$mail->MsgHTML($body);

$emailSent = $mail->Send();
if ($emailSent && $backupSaved) {
    $response['message'] = 'Your message was received. We will get back to you as soon as possible.';
    $response['status'] = 'true';
} elseif ($emailSent) {
    $response['message'] = 'Your message was emailed, but the server backup could not be saved. Please contact us by phone if you need confirmation.';
    $response['status'] = 'true';
} elseif ($backupSaved) {
    $response['message'] = 'Your message was saved, but the email could not be sent. Please try again later or call us.';
} else {
    $response['message'] = 'Your message could not be sent. Please try again later.';
}

echo json_encode($response);
?>
