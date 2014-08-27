# HTML Email

Send emails from Kohana like a boss.

## Install

Install with [Composer](http://getcomposer.org).

```
composer require openbuildings/html-email
```

Use latest stable version like: `~0.1`.

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
