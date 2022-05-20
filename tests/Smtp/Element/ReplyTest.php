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
      [220, true, false, false],
    ];
    foreach ($codes as [$num, $success, $tempFail, $permFail]) {
      $code = new Code($num);
      $this->assertSame($success, $code->isSuccess());
      $this->assertSame($tempFail, $code->isTempFail());
      $this->assertSame($permFail, $code->isPermFail());
      $this->assertSame($permFail || $tempFail, $code->isFail());
    }
  }

  public function testGenericReply()
  {
    $code = new Code(220);
    $messages = ['Ok'];
    $reply = new GenericReply($code, $messages);
    $this->assertSame($code, $reply->getCode());
    $this->assertSame($messages, $reply->messages);
  }
}


