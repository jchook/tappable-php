<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Command;

use Tap\Smtp\Element\OriginDomain;

/**
 * RFC 5321 § 4.1.1.1
 *   helo = "HELO" SP Domain CRLF
 *
 * @see Ehlo
 */
class Helo implements Command
{
  public string $verb = 'HELO';
	public function __construct(
    public OriginDomain $origin
	)
	{
	}
}


