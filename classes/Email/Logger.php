<?php

/**
 * Dummy email logger.
 *
 * @package    Email
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  2014 OpenBuildings, Inc.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */

class Email_Logger implements Swift_Plugins_Logger
{
	public function add($entry)
	{
		Log::instance()->add(Log::DEBUG, $entry);
	}

	public function clear()
	{

	}

	public function dump()
	{
		return false;
	}
}
