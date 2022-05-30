<?php

namespace Tap\Smtp\Test\Role\Client;

use PHPUnit\Framework\TestCase;
use Tap\Smtp\Element\Origin\Domain;
use Tap\Smtp\Support\SystemDomain;

class SystemDomainTest extends TestCase
{
  public function testGetHostDomain()
  {
		$d = new Domain(gethostname());
		$s = new SystemDomain();
		$this->assertEquals($d->domain, $s->domain);
		$this->assertInstanceOf(Domain::class, $s);
  }

  public function testGetHostDomainError()
  {
		$err = null;
		$prev = SystemDomain::$gethostname;
		SystemDomain::$gethostname = fn() => false;
		try {
			new SystemDomain();
		} catch (\Throwable $err) {
		} finally {
			SystemDomain::$gethostname = $prev;
		}
		$this->assertInstanceOf(\RuntimeException::class, $err);
  }
}

