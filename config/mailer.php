<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

function getMailer(): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'monkeygoku934@gmail.com';        // ← your Gmail address
    $mail->Password   = 'jzjdpgczhdelvraj';     // ← your 16-character App Password
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;
    $mail->setFrom('monkeygoku934@gmail.com', 'ShStorage');
    return $mail;
}

function sendRequestNotification(string $toEmail, string $branchName, int $requestId, string $status, string $adminNote = ''): void {
    try {
        $mail = getMailer();
        $mail->addAddress($toEmail);
        $reqCode = str_pad($requestId, 4, '0', STR_PAD_LEFT);

        if ($status === 'submitted') {
            $mail->Subject = "New Stock Request #$reqCode from $branchName";
            $mail->Body    = "A new stock request (#$reqCode) has been submitted by $branchName.\n\nLog in to ShStorage admin to review it.";
        } elseif ($status === 'approved') {
            $mail->Subject = "Your Stock Request #$reqCode Has Been Approved ✅";
            $mail->Body    = "Good news! Your stock request #$reqCode has been approved.\n\nAdmin note: " . ($adminNote ?: 'None') . "\n\nLog in to ShStorage to view your updated branch stock.";
        } elseif ($status === 'rejected') {
            $mail->Subject = "Your Stock Request #$reqCode Has Been Rejected ❌";
            $mail->Body    = "Your stock request #$reqCode has been rejected.\n\nAdmin note: " . ($adminNote ?: 'None') . "\n\nYou may submit a new request after reviewing your needs.";
        }

        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer error: " . $e->getMessage());
    }
}