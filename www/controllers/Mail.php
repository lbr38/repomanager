<?php

namespace Controllers;

// Composer autoload
require ROOT . '/libs/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mail
{
    private const FONT = "Roboto, 'Segoe UI', Helvetica, Arial, sans-serif";

    public function __construct(string $to, string $subject, string $content, string $link = '', string $linkName = 'Click here', string $attachmentFilePath = '')
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
         */
        $template = self::render('mail', [
            'subject'  => $subject,
            'content'  => $content,
            'link'     => $link,
            'linkName' => $linkName
        ]);

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

            /**
             *  Charset and encoding
             */
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';

            // Content
            $mail->isHTML(true); //Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body    = $template;

            // Plain text alternative, for clients that do not render HTML (and for spam filters)
            $text = preg_replace(['/<br\s*\/?>/i', '/<\/(p|tr|h[1-6]|div)>/i', '/<\/td>/i'], ["\n", "\n", ' '], $content);
            $text = $mail->html2text($text);
            $text = preg_replace(['/[ \t]+/', '/ *\n */', '/\n{3,}/'], [' ', "\n", "\n\n"], $text);
            if (!empty($link)) {
                $text .= "\n\n" . $linkName . ': ' . $link;
            }
            $mail->AltBody = trim($text);

            $mail->send();
        } catch (Exception $e) {
            throw new Exception('Error while sending email: ' . $mail->ErrorInfo);
        }
    }

    /**
     *  Render a mail template from templates/mail/, with the specified params as variables
     */
    public static function render(string $template, array $params = []): string
    {
        // Font stack shared by all mail templates
        $font = self::FONT;

        extract($params);
        ob_start();
        include(ROOT . '/templates/mail/' . $template . '.template.html.php');

        return ob_get_clean();
    }
}
