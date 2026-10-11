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

$mail = new PHPMailer();
$mail->isMail();
$mail->CharSet = 'UTF-8';

// Replace this placeholder with the official cPanel mailbox after hosting is configured.
$toemail = 'dummy@example.com';
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
$mail->Subject = $subject;
$mail->MsgHTML($body);

if ($mail->Send()) {
    $response['message'] = 'Your message was received. We will get back to you as soon as possible.';
    $response['status'] = 'true';
} else {
    $response['message'] = 'Your message could not be sent. Please try again later.';
}

echo json_encode($response);
?>
