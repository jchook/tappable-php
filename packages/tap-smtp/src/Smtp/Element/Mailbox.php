<?php declare(strict_types=1);

namespace Tap\Smtp\Element;

class Mailbox
{
	private $localPart;
	private $domain;

	public function __construct(string $localPart, string $domain)
	{
		$this->localPart = $localPart;
		$this->domain = $domain;
	}

	public function getLocalPart(): string
	{
		return $this->localPart;
	}

	public function getDomain(): string
	{
		return $this->domain;
	}
}
