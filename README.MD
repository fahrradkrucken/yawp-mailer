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

**IMPORTANT:** Use it ON or AFTER the 'init' event. It's not my fault, it's just
recommendation for the wp_mail usage)

#### Quick Start

Look at the simple example below, for more information about class methods look at the code.

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

#### Using CSS

If you want to use CSS in your html email template, but you don't want to inline styles 
manually - you can use some (very simple) helpful methods to deal with this situation.

```php
$emailHtml = file_get_contents(__DIR__ . '/email.html'); // Your HTML email template
$emailCSS = file_get_contents(__DIR__ . '/style.css'); // Your Styles to inline inside HTML
$emailCSSResponsive = file_get_contents(__DIR__ . '/style-responsive.css'); // Your styles that can't be inlined 

$sendResult = EmailMessage::new()
    ->addTo('johndoe@maildomain.net', 'John Doe')
    ->setMessage($emailHtml)
    ->setHtmlStylesInline($emailCSS) 
    ->setHtmlStylesInHeader($emailCSSResponsive) // If styles can't be inlined - use this method
    ->asHtml();
```

**LIMITATIONS:** 
* Feature works on top of `simple_html_dom` and inherits all limitations of this library;
* I don't recommend ou to use difficult selectors / styles on `setHtmlStylesInline()`;
* See other notes in docBlocks for `setHtmlStylesInline()` and `setHtmlStylesInHeader()`.