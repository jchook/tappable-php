<?php

namespace Tap\Smtp\Support;

use Tap\Smtp\Element\Command\Data;
use Tap\Smtp\Element\Command\Ehlo;
use Tap\Smtp\Element\Command\EndOfData;
use Tap\Smtp\Element\Command\Helo;
use Tap\Smtp\Element\Command\MailFrom;
use Tap\Smtp\Element\Command\Quit;
use Tap\Smtp\Element\Reply\Greeting;

/**
 */
class SessionState
{
	public function __construct(
		public ?Greeting $greeting = null,
		public ?Helo $helo = null,
		public ?Ehlo $ehlo = null,
		public ?MailFrom $mailFrom = null,
		/**
		 * Valid RCPT TO commands
		 * @var RcptTo[]
		 */
		public array $rcptTos = [],
		public ?Data $data = null,
		public ?EndOfData $endOfData = null,
		public ?Quit $quit = null,
	)
	{
	}

	public function isEsmtp()
	{
		return (bool) $this->ehlo;
	}

	public function reset()
	{
		$this->mailFrom = null;
		$this->rcptTos = [];
		$this->data = null;
		$this->endOfData = null;
	}
}

