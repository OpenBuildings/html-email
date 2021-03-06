<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Front door to the email goodness.
 *
 * @package    Email
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  2014 OpenBuildings, Inc.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Email {

	// SwiftMailer instance
	protected static $_mailer;

	/**
	 * Creates a SwiftMailer instance.
	 *
	 * @param   string  DSN connection string
	 * @return  object  Swift object
	 */
	public static function mailer()
	{
		if (Email::$_mailer)
			return Email::$_mailer;

		// Load default configuration
		$config = Kohana::$config->load('html-email')->as_array();

		switch ($config['driver'])
		{
			case 'smtp':
				$transport = Swift_SmtpTransport::newInstance(
					Arr::path($config, 'options.hostname', 'localhost'),
					Arr::path($config, 'options.port', 25),
					Arr::path($config, 'options.encryption')
				);

				$transport->setTimeout(Arr::path($config, 'options.timeout', 5));

				$user = Arr::path($config, 'options.username');
				$pass = Arr::path($config, 'options.password');
				if ($user AND $pass)
				{
					$transport->setUsername($user);
					$transport->setPassword($pass);
				}
			break;

			case 'sendmail':
				$transport = Swift_SendmailTransport::newInstance(Arr::get($config, 'options', '/usr/sbin/sendmail -bs'));
			break;

			case 'postmark':
				$transport = Openbuildings\Postmark\Swift_PostmarkTransport::newInstance(Arr::get($config, 'options'));
			break;

			case 'null':
				$transport = Swift_NullTransport::newInstance();
			break;

			default:
				// Use the native connection
				$transport = Swift_MailTransport::newInstance();
			break;
		}

		// Create the SwiftMailer instance
		self::$_mailer = Swift_Mailer::newInstance($transport);

		if (Arr::get($config, 'inline_css'))
		{
			self::$_mailer->registerPLugin(new Openbuildings\Swiftmailer\CssInlinerPlugin());
		}

		if ($filter = Arr::get($config, 'filter'))
		{
			self::$_mailer->registerPlugin(new Openbuildings\Swiftmailer\FilterPlugin(
				Arr::get($filter, 'whitelist', array()),
				Arr::get($filter, 'blacklist', array()))
			);
		}

		if ($google_campaign = Arr::path($config, 'google_campaign.campaigns'))
		{
			self::$_mailer->registerPlugin(new Openbuildings\Swiftmailer\GoogleCampaignPlugin(
				array(),
				array(
					'share' => $google_campaign['share'],
					'abandoned_cart' => $google_campaign['abandoned_cart']
				)
			));
		}

		if ($logger = Arr::get($config, "logger"))
		{
			if ($logger === TRUE)
			{
				self::$_mailer->registerPlugin(new Swift_Plugins_Fullloggerplugin(new Email_Logger()));
			}
			else
			{
				self::$_mailer->registerPlugin(new $logger(new Email_Logger()));
			}
		}
	}

	static public function loaded()
	{
		return (bool) Email::$_mailer;
	}

	static public function factory($subject, $config = NULL)
	{
		return new Email($subject, $config);
	}

	protected $_config;

	protected $_message;

	protected $_attachments = array();

	public function __construct($subject, $config = NULL)
	{
		self::mailer();

		$this->_config = Arr::merge( (array) Kohana::$config->load('html-email'), (array) $config);

		$this->_message = Swift_Message::newInstance($subject);

		if ($from = Arr::get($this->_config, 'from'))
		{
			$this->from($from);
		}

		if ($charset = Arr::get($this->_config, 'charset'))
		{
			$this->charset($charset);
		}
	}

	public function antiFlood($messages_count = 100, $time = 5)
	{
		// Use AntiFlood to re-connect after 100 emails
		self::$_mailer->registerPlugin(new Swift_Plugins_AntiFloodPlugin($messages_count, $time));
	}

	public function layout($html, $plain = NULL)
	{
		$this->_config['layout_html'] = $html;

		if ($plain !== NULL)
		{
			$this->_config['layout_plain'] = $plain;
		}
		return $this;
	}

	public function html($body_view, $params = NULL)
	{
		$body = $this->body_view($body_view, $params, Arr::get($this->_config, 'layout_html'));
		$this->_message->addPart($body,  'text/html');
		return $this;
	}

	public function plain($body_view, $params = NULL)
	{
		$body = $this->body_view($body_view, $params, Arr::get($this->_config, 'layout_plain'));

		$this->_message->addPart($body, 'text/plain');
		return $this;
	}

	public function body_view($body_view, $params, $layout = NULL)
	{
		$params = Arr::merge((array) $params, $this->_attachments);

		if ($layout)
		{
			return View::factory($layout, array(
				'title' => $this->_message->getSubject(),
				'content' => View::factory($body_view, $params)
			))->render();
		}
		else
		{
			return View::factory($body_view, Arr::merge($params, array(
				'title' => $this->_message->getSubject()
			)))->render();
		}
	}

	public function attach($name, $file)
	{
		$this->_attachments[$name] = $this->_message->attach(Swift_Attachment::fromPath($file)->setFilename($name));
		return $this;
	}

	public function embed($name, $file)
	{
		$this->_attachments[$name] = $this->_message->embed(Swift_Attachment::fromPath($file));
		return $this;
	}

	public function from($email, $name = NULL)
	{
		$this->_message->setFrom($email, $name);
		return $this;
	}

	public function to($email, $name = NULL)
	{
		$this->_message->addTo($email, $name);
		return $this;
	}

	public function setTo($emails, $name = NULL)
	{
		$this->_message->setTo($emails, $name);
		return $this;
	}

	public function body($body)
	{
		$this->_message->setBody($body);
		return $this;
	}

	public function replyTo($email, $name = NULL)
	{
		$this->_message->addReplyTo($email, $name);
		return $this;
	}

	public function cc($email, $name = NULL)
	{
		$this->_message->addCc($email, $name);
		return $this;
	}

	public function setCc($emails, $name = NULL)
	{
		$this->_message->setCc($emails, $name);
		return $this;
	}

	public function bcc($email, $name = NULL)
	{
		$this->_message->addBcc($email, $name);
		return $this;
	}

	public function setBcc($emails, $name = NULL)
	{
		$this->_message->setBcc($emails, $name);
		return $this;
	}

	public function message()
	{
		return $this->_message;
	}

	public function charset($charset)
	{
		$this->_message->setCharset($charset);
		return $this;
	}

	public function send($to = NULL)
	{
		if ($to)
		{
			$this->to($to);
		}

		self::mailer()->send($this->_message, $failures);

		if (count($failures))
			return FALSE;

		return TRUE;
	}
} // End email
