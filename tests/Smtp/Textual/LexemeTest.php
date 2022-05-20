<?php

namespace Tap\Smtp\Test;

use PHPUnit\Framework\TestCase;
use Tap\Smtp\Textual\Lexeme;

class LexemeTest extends TestCase
{
  /**
   * @dataProvider getDotStrings
   */
  public function testIsDotString($dotString)
  {
    $this->assertTrue(Lexeme::isDotString($dotString));
  }

  /**
   * @dataProvider getNotDotStrings
   */
  public function testIsNotDotString($dotString)
  {
    $this->assertFalse(Lexeme::isDotString($dotString));
  }

  public function getDotStrings(): array
  {
    return [
      ['test.one.two'],
    ];
  }

  public function getNotDotStrings(): array
  {
    $controlChars = [];
    for ($ii = 0; $ii < 32; $ii++) {
      $controlChars[] = [chr($ii)];
    }
    return [
      [''],
      ['"'],
      ['"test.one"'],
      ['test.one..two'],
      ['.test.one'],
      ['test.two.'],
      ...$controlChars,
    ];
  }
}

