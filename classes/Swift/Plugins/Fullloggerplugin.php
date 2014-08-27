<?php

/**
 * SwiftMailer plugin to use with an email logger.
 *
 * @package Email
 * @author  Ivan Kerin <ikerin@gmail.com>
 * @license http://spdx.org/licenses/BSD-3-Clause
 */
class Swift_Plugins_Fullloggerplugin extends Swift_Plugins_LoggerPlugin implements Swift_Events_SendListener {

	private $_logger;

	/**
	 * Create a new LoggerPlugin using $logger.
	 *
	 * @param Swift_Plugins_Logger $logger
	 */
	public function __construct(Swift_Plugins_Logger $logger)
	{
		$this->_logger = $logger;
	}

	public function beforeSendPerformed(Swift_Events_SendEvent $event)
	{
		$this->_logger->add(sprintf(
			"Sending Mail:\n %s\n",
			$event->getMessage()
		));
	}

	/**
	 * Invoked immediately after the Message is sent.
	 * @param Swift_Events_SendEvent $evt
	 */
	public function sendPerformed(Swift_Events_SendEvent $event)
	{
		$this->_logger->add(sprintf(
			"Sent Emails, Failed Recipients: ",
			join(', ',$event->getFailedRecipients())
		));
	}
}
