<?php

namespace Tap\Smtp\Test;

use PHPUnit\Framework\TestCase;
use Tap\Smtp\Element\Reply\Code;
use Tap\Smtp\Element\Reply\GenericReply;

class ReplyTest extends TestCase
{
  public function testCode()
  {
    $codes = [
      ['220', true, 0, 0],
      ['421', 0, true, 0],
      ['511', 0, 0, true],
    ];
    foreach ($codes as [$value, $success, $tempFail, $permFail]) {
      $code = new Code($value);
      $this->assertEquals($success, $code->isPositive());
      $this->assertEquals($tempFail, $code->isTransient());
      $this->assertEquals($permFail, $code->isPermanant());
      $this->assertEquals($permFail || $tempFail, $code->isNegative());
    }
  }

  public function testGenericReply()
  {
    $code = new Code('220');
    $messages = ['Ok'];
    $reply = new GenericReply($code, $messages);
    $this->assertSame($code, $reply->getCode());
    $this->assertSame($messages, $reply->messages);
  }
}


