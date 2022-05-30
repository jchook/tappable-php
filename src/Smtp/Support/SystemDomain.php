<?php

namespace Tap\Smtp\Support;

use RuntimeException;
use Tap\Smtp\Element\Origin\Domain;

class SystemDomain extends Domain
{
	public static $gethostname = 'gethostname';
	public function __construct()
	{
		$gethostname = self::$gethostname;
		$hostname = $gethostname();

		if ($hostname === false) {
			throw new RuntimeException('Unable to retrieve system hostname');
		}

		$this->domain = $hostname;
	}
}

