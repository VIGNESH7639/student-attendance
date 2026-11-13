<?php
require_once __DIR__ . '/PHPMailer-6.8.1/src/Exception.php';
require_once __DIR__ . '/PHPMailer-6.8.1/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-6.8.1/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends an OTP to the specified email address.
 * 
 * @param string $email The recipient's email address.
 * @param string $otp The 6-digit OTP code.
 * @param string $subject Optional subject for the email.
 * @return bool True on success, false on failure.
 */
function sendOTP($email, $otp, $subject = 'Your Login OTP - Student Apt Portal')
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Set the SMTP server to send through
        $mail->SMTPAuth = true;
        $mail->Username = 'v6676512@gmail.com'; // SMTP username
        $mail->Password = 'sgqelcgecbcbxzor'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('v6676512@gmail.com', 'Student Apt Portal');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = "Your OTP for login is: $otp\n\nIt is valid for 15 minutes.";

        $mail->send();
        return true;
    }
    catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
