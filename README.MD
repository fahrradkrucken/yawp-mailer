What is this for?

It's just a simple fluent-interface wrapper for the WP's built-in wp_mail function.
If you like to use this 'style' of code - just use it as a wrapper.

#### Installation

Via Composer:

```bash
composer install fahrradkrucken/yawp-mailer
```

If you don't use Composer:

```php
require '{path_to_classes}/EmailMessage.php';

use FahrradKruken\YAWP\Mailer\EmailMessage;
```

#### Quick Start

Look at the simple example below, for more information about class methods look at the code.

**IMPORTANT:** Use it ON or AFTER the 'init' event. It's not my fault, it's just
recommendation for the wp_mail usage)

```php

use FahrradKruken\YAWP\Mailer\EmailMessage;

$emailHtmlMessage = '<h4>Hello World</h4><p>Visit our new <a href="example.com">WebSite</a></p>';

$sendResult = EmailMessage::new() // create new message, equivalent of '$message = new EmailMessage();'
    ->setFrom('developer@example.com', 'Our Admin') // default: get_bloginfo('admin_email')
    ->setReplyTo('support@example.com', 'Our Support Team')
    ->addTo('johndoe@maildomain.net', 'John Doe') // You can add multiple recipients
    ->addCc('janedoe@maildomain.net', 'Jane Doe')
    ->addBcc('mr.smith@maildomain.net')
    ->setSubject('Important Message!') // default: __('New message from ' . get_bloginfo('name'))
    ->setMessage($emailHtmlMessage)
    ->addAttachment('/public/wp-content/uploads/2019/03/some-image.png') // File path, WP Attachment ID or WP Attachment object (WP_Post)
    ->asHtml() // Set content-type to text/html
    ->send(); // Send Message via wp_mail function

if ($sendResult) echo '<h3>Success!</h3>';
else echo '<h3>Fail :(</h3>';
```  