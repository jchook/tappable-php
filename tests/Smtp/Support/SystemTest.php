<?php

namespace Tap\Smtp\Test\Role\Client;

use PHPUnit\Framework\TestCase;
use Tap\Smtp\Element\Origin\Domain;
use Tap\Smtp\Support\System;

class SystemTest extends TestCase
{
  public function testGetHostDomain()
  {
		$this->assertEquals(
			new Domain(gethostname()),
			System::getHostDomain(),
		);
  }

  public function testGetHostDomainError()
  {
		$err = null;
		$prev = System::$gethostname;
		System::$gethostname = fn() => false;
		try {
			$this->assertEquals(
				new Domain(gethostname()),
				System::getHostDomain(),
			);
		} catch (\Throwable $err) {
		} finally {
			$this->assertInstanceOf(\RuntimeException::class, $err);
			System::$gethostname = $prev;
		}
  }
}

