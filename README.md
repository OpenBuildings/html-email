# HTML Email

Send emails from Kohana like a boss.

## Usage

``` php
<?php
Email::factory("Welcome to Emailandia!")
	// Skip the layout
	->layout(FALSE)
	->plain('emails/plain-text-email-view', array('foo' => $bar))
	->html('emails/html-email-view', array('foo' => $bar))
	->send('username@example.com');
```
