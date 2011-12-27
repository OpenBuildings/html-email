<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Email module
 *
 * Ported from Kohana 2.2.3 Core to Kohana 3.0 module
 * 
 * Updated to use Swiftmailer 4.0.4
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Email {

	// SwiftMailer instance
	protected static $_mailer;
	protected $_config;

	/**
	 * Creates a SwiftMailer instance.
	 *
	 * @param   string  DSN connection string
	 * @return  object  Swift object
	 */
	public static function mailer($config = NULL)
	{
		if( ! self::$_mailer )
		{
			// Load default configuration
			($config === NULL) and $config = Kohana::$config->load('html-email');

			if ( ! class_exists('Swift_Mailer', FALSE))
			{
				require Kohana::find_file('vendor/swift', 'swift_required');
			}
			
			switch ($config['driver'])
			{
				case 'smtp':

					$transport = Swift_SmtpTransport::newInstance(
						Arr::path($config, 'options.hostname', 'localhost'), 
						Arr::path($config, 'options.port', 25),
						Arr::path($config, 'options.encryption')
					);
					
					$transport->setTimeout(Arr::path($config, 'options.timeout', 5));

					if($user = Arr::path($config, 'options.username') AND $pass = $user = Arr::path($config, 'options.password'))
					{
						$transport->setUsername($user);
						$transport->setPassword($pass);
					}

					break;

				case 'sendmail':
					$transport = Swift_SendmailTransport::newInstance(Arr::get($config, 'options', '/usr/sbin/sendmail -bs'));
					break;

				default:
					// Use the native connection
					$transport = Swift_MailTransport::newInstance();
					break;
			}

			// Create the SwiftMailer instance
			self::$_mailer = Swift_Mailer::newInstance($transport);

			if(Arr::get($config, "logger"))
			{
				self::$_mailer->registerPlugin(new Swift_Plugins_FullLoggerPlugin(new Email_Logger()));
			}
		}

		return self::$_mailer;
	}

	static public function factory($subject, $config = null)
	{
		return new Email($subject, $config);
	}

	protected $_message;
	protected $_attachments = array();

	public function antiFlood($messages_count = 100, $time = 5)
	{
		//Use AntiFlood to re-connect after 100 emails
		self::$_mailer->registerPlugin(new Swift_Plugins_AntiFloodPlugin($messages_count, $time));
	}

	public function __construct($subject, $config = null)
	{
		self::mailer();

		$this->_config = Arr::merge((array) Kohana::$config->load('html-email'), (array) $config);

		$this->_message = Swift_Message::newInstance($subject);

		if($from = Arr::get($this->_config, 'from'))
		{
			$this->from($from);
		}

		if($charset = Arr::get($this->_config, 'charset'))
		{
			$this->charset($charset);
		}
	}

	public function layout($html, $plain = null)
	{
		$this->_config['layout_html'] = $html;

		if ($plain)
		{
			$this->_config['layout_plain'] = $plain;
		}
		return $this;
	}

	public function html($body_view, $params = null)
	{
		$body = $this->body_view($body_view, $params, Arr::get($this->_config, 'layout_html'));
		
		if ( Arr::get($this->_config, 'inline_css'))
		{
			if ( ! class_exists('CSSToInlineStyles', FALSE))
			{
				// Load CSSToInlineStyles
				require Kohana::find_file('vendor', 'csstoinlinestyles/css_to_inline_styles');
			}

			$body_html = new CSSToInlineStyles($body);
			$body_html->setEncoding($this->_message->getCharset());
			$body_html->setUseInlineStylesBlock();
			$body = $body_html->convert();
		}

		$this->_message->addPart($body,  "text/html");
		return $this;
	}

	public function plain($body_view, $params = NULL)
	{
		$body = $this->body_view($body_view, $params, Arr::get($this->_config, 'layout_plain'));

		$this->_message->addPart($body, "text/plain");
		return $this;
	}	

	public function body_view($body_view, $params, $layout = NULL)
	{
		$params = Arr::merge((array) $params, $this->_attachments);

		if($layout)
		{
			return View::factory($layout, array(
				'title' => $this->_message->getSubject(), 
				'content' => View::factory($body_view, $params)
			));
		}
		else
		{
			return View::factory($body_view, Arr::merge($params, array('title' => $this->_message->getSubject())));
		}
	}



	public function attach($name, $file)
	{
		$this->_attachemnts[$name] = $this->_message->attach(Swift_Attachment::fromPath($file));
		return $this;
	}

	public function embed($name, $file)
	{
		$this->_attachemnts[$name] = $this->_message->embed(Swift_Attachment::fromPath($file));
		return $this;
	}

	public function from($email, $name = null)
	{
		$this->_message->setFrom($email, $name);
		return $this;
	}

	public function to($email, $name = null)
	{
		$this->_message->addTo($email, $name);
		return $this;
	}

	public function cc($email, $name = null)
	{
		$this->_message->addCc($email, $name);
		return $this;
	}
	
	public function bcc($email, $name = null)
	{
		$this->_message->addBcc($email, $name);
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

	public function send( $to = null )
	{
		if($to)
		{
			$this->to($to);
		}

		self::mailer()->send($this->_message, $failures);

		if( count($failures) )
		{
			return false;
		}

		return true;
	}
} // End email