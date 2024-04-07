<?php

namespace Kristian\Apps\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/autoload.php';

/**
 * Handles sending emails using PHPMailer.
 */
class Mailer
{
    /**
     * @var PHPMailer The mailer instance.
     */
    private $mail;

    /**
     * Constructor initializes the PHPMailer object with configuration from environment variables.
     */
    public function __construct()
    {
        $this->mail = new PHPMailer(true);

        //Server settings
        $this->mail->isSMTP();                                            // Send using SMTP
        $this->mail->Host = EMAIL_SMTP_ADDR;                        // Set the SMTP server to send through
        $this->mail->SMTPAuth = EMAIL_SMTP_AUTH;                        // Enable SMTP authentication
        $this->mail->Username = EMAIL_USER;                             // SMTP username
        $this->mail->Password = EMAIL_PASS;                             // SMTP password
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
        $this->mail->Port = 587;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
    }

    /**
     * Sends an email.
     * 
     * @param string $to Recipient's email address.
     * @param string $name Recipient's name.
     * @param string $subject The subject of the email.
     * @param string $body The HTML body of the email.
     * @param string $altBody The plain text alternative body of the email.
     * @return bool Returns true on success, or false on failure.
     */
    public function send($to, $name, $subject, $body, $altBody = ''): bool
    {
        try {
            //Recipients
            $this->mail->setFrom(EMAIL_USER, 'Mailer');
            $this->mail->addAddress($to, $name);

            // Content
            $this->mail->isHTML(true);                                  
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->AltBody = $altBody;

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
