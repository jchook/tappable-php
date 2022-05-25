<?php

namespace Tap\Smtp\Support;

use RuntimeException;
use Tap\Smtp\Element\Origin\Domain;

class System
{
	public static $gethostname = 'gethostname';
	public static function getHostDomain(): Domain
	{
		$gethostname = self::$gethostname;
		$hostname = $gethostname();

		if ($hostname === false) {
			throw new RuntimeException('Unable to retrieve system hostname');
		}

		return new Domain($hostname);
	}
}
