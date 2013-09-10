<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @group app
 * @group app.email
 */
class Unit_EmailTest extends Testcase_Functest_Clippings {

	public function test_send_email()
	{
		$email = Email::factory('Test email');
		$email->to('user@example.com');
		$email->cc('user2@example.com');
		$email->cc('pesho@google.com');
		$email->cc('john.smith@clippings.com');
		$email->bcc('john.smith2@clippings.com');
		$email->bcc('luke@skywalker.com');
		$email->send();

		$this->assertEmailsSent(array('user@example.com', 'user2@example.com', 'john.smith@clippings.com', 'john.smith2@clippings.com'));
	}
}