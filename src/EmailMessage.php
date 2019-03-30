<?php

namespace FahrradKruken\YAWP\Mailer;

/**
 * Class EmailMessage
 * @package FahrradKruken\YAWP\Mailer
 */
class EmailMessage
{

    private $from = '';
    private $replyTo = '';
    private $to = [];
    private $cc = [];
    private $bcc = [];
    private $contentType = '';
    private $subject = '';
    private $message = '';
    private $attachments = [];

    /**
     * EmailMessage constructor.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        $config = wp_parse_args($config, [
            'from' => get_bloginfo('admin_email'),
            'subject' => __('New message from ' . get_bloginfo('name')),
            'message' => '',
            'contentType' => '',
        ]);

        if (is_string($config['from']))
            $this->setFrom($config['from']);
        elseif (is_array($config['from']) && !empty($config['from']))
            if (count($config['from']) === 1) $this->setFrom($config['from'][0]);
            else $this->setFrom($config['from'][0], $config['from'][1]);
        $this->setSubject($config['subject']);
        $this->setMessage($config['message']);
        $this->contentType = in_array($config['contentType'], ['text/plain', 'text/html']) ? $config['contentType'] : '';
    }

    /**
     * @param array $config
     * Initial config of the new message. Possible parameters and their defaults:
     * [
     *  'from' => get_bloginfo('admin_email'),
     *  'subject' => __('New message from ' . get_bloginfo('name')),
     *  'message' => '',
     *  'contentType' => '',
     * ]
     *
     * @return EmailMessage
     */
    public static function new($config = [])
    {
        return new EmailMessage($config);
    }

    /**
     * @param string $email
     * @param string $name
     *
     * @return $this
     */
    public function setFrom($email, $name = '')
    {
        $emailString = $this->createEmailString($email, $name);
        if (!empty($emailString)) $this->from = $emailString;
        return $this;
    }

    /**
     * @param string $email
     * @param string $name
     *
     * @return $this
     */
    public function setReplyTo($email, $name = '')
    {
        $emailString = $this->createEmailString($email, $name);
        if (!empty($emailString)) $this->replyTo = $emailString;
        return $this;
    }

    /**
     * Set message content type to text/plain
     *
     * @return $this
     */
    public function asPlainText()
    {
        $this->contentType = 'text/plain';
        return $this;
    }

    /**
     * Set message Content type to text/html
     *
     * @return $this
     */
    public function asHtml()
    {
        $this->contentType = 'text/html';
        return $this;
    }

    /**
     * @param string $subject
     *
     * @return $this
     */
    public function setSubject($subject = '')
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @param string $message
     *
     * @return $this
     */
    public function setMessage($message = '')
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @param string $email
     * @param string $name
     *
     * @return $this
     */
    public function addTo($email, $name = '')
    {
        $emailString = $this->createEmailString($email, $name);
        if (!empty($emailString)) $this->to[] = $emailString;
        return $this;
    }

    /**
     * @param string $email
     * @param string $name
     *
     * @return $this
     */
    public function addCc($email, $name = '')
    {
        $emailString = $this->createEmailString($email, $name);
        if (!empty($emailString)) $this->cc[] = $emailString;
        return $this;
    }

    /**
     * @param string $email
     * @param string $name
     *
     * @return $this
     */
    public function addBcc($email, $name = '')
    {
        $emailString = $this->createEmailString($email, $name);
        if (!empty($emailString)) $this->bcc[] = $emailString;
        return $this;
    }

    /**
     * @param int|\WP_Post|string $attachment
     * You can use attachment ID, attachment WP_Post object or traditional - file src (path)
     *
     * @return $this
     */
    public function addAttachment($attachment)
    {
        if (is_int($attachment) || $attachment instanceof \WP_Post) {
            $attachmentPath = get_attached_file($attachment);
            if (!empty($attachmentPath)) $this->attachments[] = $attachment;
        } elseif (is_string($attachment) && is_file($attachment)) {
            $this->attachments[] = $attachment;
        }
        return $this;
    }

    /**
     * Send Email through WP_MAIL
     *
     * @return bool
     */
    public function send()
    {
        $to = $this->to;
        $subject = $this->subject;
        $message = $this->message;
        $attachments = $this->attachments;
        $headers = [];

        $headers[] = 'From: ' . $this->from;
        if (!empty($this->replyTo))
            $headers[] = 'Reply-To: ' . $this->replyTo;
        if (!empty($this->contentType))
            $headers[] = 'Content-Type: ' . $this->contentType;
        if (!empty($this->cc))
            foreach ($this->cc as $recipientEmail)
                $headers[] = 'CC: ' . $recipientEmail;
        if (!empty($this->bcc))
            foreach ($this->bcc as $recipientEmail)
                $headers[] = 'BCC: ' . $recipientEmail;

        return wp_mail($to, $subject, $message, $headers, $attachments);
    }

    /**
     * Filters $email string as email and validates it through built-in wp functions
     *
     * @param string $email
     *
     * @return bool|mixed
     */
    private function filterAndValidateEmail($email)
    {
        if (!empty($email)) {
            $email = filter_var($email, FILTER_SANITIZE_EMAIL);
            if (filter_var($email, FILTER_VALIDATE_EMAIL) && is_email($email))
                return $email;
        }
        return false;
    }

    /**
     * Creates valid email string for the message from given $email & $name
     * Example:
     * * createEmailString('John Doe') -> ''
     * * createEmailString('johndoe@mail.com') -> 'johndoe@mail.com'
     * * createEmailString('johndoe@mail.com', 'John Doe') -> 'John Doe <johndoe@mail.com>'
     *
     * @param string $email
     * @param string $name
     *
     * @return bool|mixed|string
     */
    private function createEmailString($email, $name = '')
    {
        $email = $this->filterAndValidateEmail($email);
        if ($email)
            return (!empty($name) && is_string($name)) ?
                (filter_var($name, FILTER_SANITIZE_SPECIAL_CHARS) . '<' . $email . '>') :
                $email;
        return '';
    }
}