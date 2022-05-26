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
      ['220', 1, 0, 0, 0],
      ['354', 0, 1, 0, 0],
      ['421', 0, 0, 1, 0],
      ['511', 0, 0, 0, 1],
    ];
    foreach ($codes as [$value, $compl, $inter, $trans, $perm]) {
      $code = new Code($value);
      $this->assertEquals($compl, $code->isCompletion());
      $this->assertEquals($inter, $code->isIntermediate());
      $this->assertEquals($trans, $code->isTransient());
      $this->assertEquals($perm, $code->isPermanant());
      $this->assertEquals($perm || $trans, $code->isNegative());
      $this->assertEquals($compl || $inter, $code->isPositive());
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


