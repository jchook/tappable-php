<?php declare(strict_types=1);

namespace Tap\Smtp\Exception;

use Tap\Smtp\Element\Command;
use Tap\Smtp\Element\Reply;
use Throwable;

class Fail extends ClientException
{
	public function __construct(
		Command $command,
		Reply $reply,
		?Throwable $previous = null
	) {
		$this->command = $command;
		$this->reply = $reply;
		return parent::__construct(
			$reply->getLines()[0] ?? '', $reply->getCode(), $previous
		);
	}
}
