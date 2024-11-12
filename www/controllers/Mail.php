<?php

namespace Controllers;

require_once(ROOT . '/libs/PHPMailer/Exception.php');
require_once(ROOT . '/libs/PHPMailer/PHPMailer.php');
require_once(ROOT . '/libs/PHPMailer/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mail
{
    public function __construct(string $to, string $subject, string $content, string $link = null, string $linkName = 'Click here', string $attachmentFilePath = null)
    {
        if (empty($to)) {
            throw new \Exception('Cannot send email: no recipient specified.');
        }
        if (empty($subject)) {
            throw new \Exception('Cannot send email: no subject specified.');
        }
        if (empty($content)) {
            throw new \Exception('Cannot send email: no message specified.');
        }

        /**
         *  if there is a , in the $to string, it means there are multiple recipients
         */
        if (strpos($to, ',') !== false) {
            $to = explode(',', $to);
        }

        /**
         *  HTML message template
         *  Powered by MJML
         */
        ob_start();
        include(ROOT . '/templates/mail/mail.template.html.php');
        $template = ob_get_clean();

        /**
         *  PHPMailer
         */
        $mail = new PHPMailer(true);

        try {
            // Recipients
            $mail->setFrom('noreply@' . WWW_HOSTNAME, PROJECT_NAME);

            if (is_array($to)) {
                foreach ($to as $recipient) {
                    $mail->addAddress($recipient);
                }
            } else {
                $mail->addAddress($to);
            }

            $mail->addReplyTo('noreply@' . WWW_HOSTNAME, PROJECT_NAME);

            // Attachments
            if (!empty($attachmentFilePath)) {
                $mail->addAttachment($attachmentFilePath);
            }

            // Content
            $mail->isHTML(true); //Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body    = $template;

            /**
             *  Charset and encoding
             */
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';

            $mail->send();
        } catch (Exception $e) {
            throw new Exception('Error while sending email: ' . $mail->ErrorInfo);
        }
    }
}
