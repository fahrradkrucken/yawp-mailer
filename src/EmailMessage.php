<?php

namespace FahrradKruken\YAWP\Mailer;

/**
 * Class EmailMessage
 * @package FahrradKruken\YAWP\Mailer
 */
class EmailMessage
{
    const CONTENT_TYPE_PLAIN_TEXT = 'text/plain';
    const CONTENT_TYPE_HTML = 'text/html';

    private $from = '';
    private $replyTo = '';
    private $to = [];
    private $cc = [];
    private $bcc = [];
    private $contentType = '';
    private $subject = '';
    private $message = '';
    private $attachments = [];
//    private $stylesInline = [];
//    private $stylesInHead = '';
    public $stylesInline = [];
    public $stylesInHead = '';

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

        if (is_string($config['from'])) {
            $this->setFrom($config['from']);
        } elseif (is_array($config['from']) && !empty($config['from'])) {
            if (count($config['from']) === 1)
                $this->setFrom($config['from'][0]);
            else
                $this->setFrom($config['from'][0], $config['from'][1]);
        }
        $this->setSubject($config['subject']);
        $this->setMessage($config['message']);
        $this->contentType = in_array($config['contentType'], [self::CONTENT_TYPE_PLAIN_TEXT, self::CONTENT_TYPE_HTML]) ?
            $config['contentType'] :
            '';
    }

    /**
     * @param array $config
     *      Initial config of the new message. Possible parameters and their defaults:
     *      [
     *      'from' => get_bloginfo('admin_email'), // $from can be in this case: 'ema@il.com' OR ['ema@il.com', 'John
     *      Doe']
     *      'subject' => __('New message from ' . get_bloginfo('name')),
     *      'message' => '',
     *      'contentType' => '',
     *      ]
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
        $this->contentType = self::CONTENT_TYPE_PLAIN_TEXT;
        return $this;
    }

    /**
     * Set message Content type to text/html
     *
     * @return $this
     */
    public function asHtml()
    {
        $this->contentType = self::CONTENT_TYPE_HTML;
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
     * Add CSS that will be set into the $message as inline styles.
     * Note:
     * * If your styles content will contain @media queries or @font-face - they ALL will appear in <head> instead of
     * inline;
     * * If some of your selectors will contain pseudo-classes/elements - this selectors styles will appear in <head>;
     *
     * @param string $cssContent
     *
     * @return EmailMessage
     */
    public function setHtmlStylesInline($cssContent = '')
    {
        return $this->setHtmlStyles($cssContent, true, false);
    }

    /**
     * Add CSS that will be set into a $message's <head>
     *
     * @param string $cssContent
     *
     * @return EmailMessage
     */
    public function setHtmlStylesInHeader($cssContent = '')
    {
        return $this->setHtmlStyles($cssContent, false, true);
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

        if (
            $this->contentType === self::CONTENT_TYPE_HTML &&
            (!empty($this->stylesInHead) || !empty($this->stylesInline))
        ) {
            $message = $this->addStylesToHtmlMessage($this->message);
        } else {
            $message = $this->message;
        }

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

    /**
     * @param string $cssContent
     * @param bool   $insertStylesInline
     * @param bool   $insertStylesInHead
     *
     * @return $this
     */
    private function setHtmlStyles($cssContent = '', $insertStylesInline = true, $insertStylesInHead = true)
    {
        if (empty($cssContent)) return $this;
        else $this->asHtml();

        $styleString = preg_replace( // Clear styles from whitespaces & comments
            ['/\s+/', '/\/\*.*?\*\//',],
            [' ', '',],
            $cssContent
        );

        if ( // if we shouldn't (or just can't) inline this styles - we'll set it in <head>
            $insertStylesInHead ||
            strpos($styleString, '@media') !== false ||
            strpos($styleString, '@font-face') !== false
        ) {
            $this->stylesInHead = $styleString;
            return $this;
        }
        if (!$insertStylesInline) return $this;

        // now we can try to convert styles into array
        $stylesArray = [];
        preg_match_all('/(.*?){(.*?)}/', $styleString, $stylesArray, PREG_SET_ORDER);
        if (!empty($stylesArray)) {
            $this->stylesInline = [];
            foreach ($stylesArray as $style) {
                $selectors = explode(',', $style[1]);   // 1 Match (css selectors)
                $styles = $style[2];                            // 2 Match (css style properties)
                foreach ($selectors as $selector)
                    if (!empty($selector))
                        $this->stylesInline[trim($selector)] = trim($styles);
            }
        }

        return $this;
    }

    /**
     * Works on top of simple_html_dom and inserts prevously added styles into htmpl elements directly (inline) or in
     * <head> section.
     *
     * @param string $htmlMessage
     *
     * @return string - HTML with inserted styles
     */
    private function addStylesToHtmlMessage($htmlMessage = '')
    {
        if (!class_exists('simple_html_dom') && !class_exists('simple_html_dom_node'))
            require 'simple_html_dom.php';

        $htmlWithStyles = str_get_html($htmlMessage);
        if (empty($htmlWithStyles)) return $htmlMessage;

        if (!empty($this->stylesInline)) {
            $selectorsForbiddenToInline = '/:hover|:before|:after|::before|::after|:active|:focus|:first-child|:last-child/';
            foreach ($this->stylesInline as $selector => $style) { // Add inline Styles according to css selectors
                if (preg_match($selectorsForbiddenToInline, $selector) === 1) {
                    $this->stylesInHead .= $selector . '{' . $style . '}';
                    continue;
                }
                $elementsCount = count($htmlWithStyles->find($selector));
                if ($elementsCount)
                    for ($i = 0; $i < $elementsCount; $i++)
                        $htmlWithStyles->find($selector, $i)->style .= $style;
            }
        }

        if (!empty($this->stylesInHead)) { // Add styles in header
            $htmlWithStyles->find('head', 0)->innertext .=
                '<style type="text/css">' . $this->stylesInHead . '</style>';
        }

        return (string)$htmlWithStyles;
    }
}