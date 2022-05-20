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
      $this->assertEquals($success, $code->isSuccess());
      $this->assertEquals($tempFail, $code->isTempFail());
      $this->assertEquals($permFail, $code->isPermFail());
      $this->assertEquals($permFail || $tempFail, $code->isFail());
    }
  }

  public function testGenericReply()
  {
    $code = new Code(220);
    $messages = ['Ok'];
    $reply = new GenericReply($code, $messages);
    $this->assertEquals($code, $reply->getCode());
    $this->assertEquals($messages, $reply->messages);
  }
}


