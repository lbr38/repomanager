<?php

namespace Controllers;

use Exception;

class Mail
{
    private $to;
    private $preview;
    private $subject;
    private $message;
    private $link;
    private $linkName = 'Click here';
    private $headers;

    public function __construct(string|array $to, string $subject, string $message, string $link = null, string $linkName = null)
    {
        if (empty($to)) {
            throw new Exception('Error: mail recipient cannot be empty');
        }
        if (empty($subject)) {
            throw new Exception('Error: mail subject cannot be empty');
        }
        if (empty($message)) {
            throw new Exception('Error: mail message cannot be empty');
        }
        if (!empty($link)) {
            $this->link = $link;
        }
        if (!empty($linkName)) {
            $this->linkName = $linkName;
        }

        if (is_array($to)) {
            $to = implode(',', $to);
        }

        $this->to = $to;
        $this->subject = $subject;
        $this->message = $message;
        $this->headers[] = 'MIME-Version: 1.0';
        $this->headers[] = 'Content-type: text/html; charset=utf8';
        $this->headers[] = "From: noreply@" . WWW_HOSTNAME;
        $this->headers[] = "X-Sender: noreply@" . WWW_HOSTNAME;
        $this->headers[] = "Reply-To: noreply@" . WWW_HOSTNAME;
    }

    /**
     *  Send the email
     */
    public function send()
    {
        ob_start();
        include_once(ROOT . '/templates/mail/mail.template.html.php');
        $template = ob_get_clean();

        if (!mail($this->to, $this->subject, $template, implode("\r\n", $this->headers))) {
            throw new Exception('Error: cannot send email');
        }
    }
}
