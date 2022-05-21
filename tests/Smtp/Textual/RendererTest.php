<?php

namespace Tap\Smtp\Test;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tap\Smtp\Element\Command\Data;
use Tap\Smtp\Element\Command\Ehlo;
use Tap\Smtp\Element\Command\Expn;
use Tap\Smtp\Element\Command\Helo;
use Tap\Smtp\Element\Command\Help;
use Tap\Smtp\Element\Command\MailFrom;
use Tap\Smtp\Element\Command\Noop;
use Tap\Smtp\Element\Command\Quit;
use Tap\Smtp\Element\Command\RcptTo;
use Tap\Smtp\Element\Command\Rset;
use Tap\Smtp\Element\Command\Unknown;
use Tap\Smtp\Element\Command\Vrfy;
use Tap\Smtp\Element\ForwardPath;
use Tap\Smtp\Element\Mailbox;
use Tap\Smtp\Element\Origin\Origin;
use Tap\Smtp\Element\Origin\AddressLiteral;
use Tap\Smtp\Element\Origin\Domain;
use Tap\Smtp\Element\Param;
use Tap\Smtp\Element\Path;
use Tap\Smtp\Element\Reply\Code;
use Tap\Smtp\Element\Reply\GenericReply;
use Tap\Smtp\Element\Reply\Greeting;
use Tap\Smtp\Element\Reply\Reply;
use Tap\Smtp\Element\ReversePath;
use Tap\Smtp\Textual\Lexeme;
use Tap\Smtp\Textual\Renderer;

class RendererTest extends TestCase
{
  public function testRenderAddressLiteral()
  {
    $ip4 ='127.0.0.1';
    $ip6 = 'ff00::1';
    $o4 = new AddressLiteral($ip4);
    $o6 = new AddressLiteral($ip6);
    $renderer = new Renderer(smtputf8: true);
    $this->assertSame(
      "[$ip4]",
      $renderer->renderOrigin($o4),
    );
    $this->assertSame(
      "[$ip6]",
      $renderer->renderOrigin($o6),
    );
  }

  public function testRenderDomain()
  {
    $domainA = 'regular.domain';
    $domainU = '🦆.ducks.gov';
    $domainUA = idn_to_ascii($domainU);
    $oA = new Domain($domainA);
    $oU = new Domain($domainU);
    $rA = new Renderer(smtputf8: false);
    $rU = new Renderer(smtputf8: true);
    $this->assertSame(
      $domainA,
      $rA->renderOrigin($oA),
    );
    $this->assertSame(
      $domainU,
      $rU->renderOrigin($oU),
    );
    $this->assertSame(
      $domainUA,
      $rA->renderOrigin($oU),
      'Automatic IDN conversion for non-smtputf8',
    );
  }

  public function testRenderHelo()
  {
    $ip ='127.0.0.1';
    $ipOrigin = new AddressLiteral($ip);
    $domain ='🦆.ducks.gov';
    $domainAscii = idn_to_ascii($domain);
    $domainOrigin = new Domain($domain);
    $helo = new Helo($domainOrigin);
    $ehlo = new Ehlo($ipOrigin);
    $renderer = new Renderer(smtputf8: false);
    $this->assertSame(
      "HELO $domainAscii\r\n",
      $renderer->renderCommand($helo)
    );
    $renderer = new Renderer(smtputf8: true);
    $this->assertSame(
      "HELO $domain\r\n",
      $renderer->renderCommand($helo)
    );
    $this->assertSame(
      "EHLO [$ip]\r\n",
      $renderer->renderCommand($ehlo)
    );
  }

  public function testRenderMailRctp()
  {
    // Some background: According to RFC 6530 and related documents, an
    // internationalized domain name can appear in two forms: the UTF-8 form,
    // and the ASCII (xn--mumble) form. An internationalized address localpart
    // must be encoded in UTF-8; the RFCs do not define an ASCII alternative
    // form.
    $localPart = 'mister🦆';
    $domain = '🦆.ducks.gov';
    $domainA = idn_to_ascii($domain);
    $path = "<\"{$localPart}\"@{$domain}>";
    $pathA = "<\"{$localPart}\"@{$domainA}>";
    $origin = new Domain($domain);
    $mailbox = new Mailbox($localPart, $origin);
    $mailFrom = new MailFrom(new ReversePath($mailbox));
    $rcptTo = new RcptTo(new ForwardPath($mailbox));
    $renderer = new Renderer(smtputf8: true);
    $this->assertSame(
      "MAIL FROM:$path\r\n",
      $renderer->renderCommand($mailFrom)
    );
    $this->assertSame(
      "RCPT TO:$path\r\n",
      $renderer->renderCommand($rcptTo)
    );
    $this->assertSame(
      "MAIL FROM:$path\r\n",
      $renderer->renderCommand($mailFrom)
    );
    $renderer = new Renderer(smtputf8: false);
    $this->assertSame(
      "MAIL FROM:$pathA\r\n",
      $renderer->renderCommand($mailFrom)
    );
    $this->assertSame(
      "RCPT TO:$pathA\r\n",
      $renderer->renderCommand($rcptTo)
    );

    $mailFrom = new MailFrom(new ReversePath(null), [
      new Param('PARAM1'),
      new Param('PARAM2', 'VALUE2'),
      new Param('PARAM3', '🌵cactus🌵cactus🌵cactus🌵')
    ]);
    $this->assertSame(
      implode(' ', [
        'MAIL FROM:<>',
        'PARAM1',
        'PARAM2=VALUE2',
        'PARAM3=+F0+9F+8C+B5cactus+F0+9F+8C+B5cactus+F0+9F+8C+B5cactus+F0+9F+8C+B5'
      ]) . Lexeme::CRLF,
      $renderer->renderCommand($mailFrom)
    );
  }

  public function testMailFromWithNormalDomain()
  {
    $renderer = new Renderer(smtputf8: false);
    $mailFrom = new MailFrom(new ReversePath(
      new Mailbox('normal', new Domain('mailbox.com'))
    ));
    $this->assertSame(
      'MAIL FROM:<normal@mailbox.com>' . Lexeme::CRLF,
      $renderer->renderCommand($mailFrom)
    );
  }

  public function testRenderForeignReply()
  {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/^Unrecognized/');
    $renderer = new Renderer();
    $renderer->renderReply(new MyForeignReply());
  }

  public function testRenderForeignOrigin()
  {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/^Unrecognized/');
    $renderer = new Renderer();
    $renderer->renderOrigin(new MyForeignOrigin());
  }

  public function testRenderForeignPath()
  {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessageMatches('/^Unrecognized/');
    $renderer = new Renderer();
    $renderer->renderPath(new MyForeignPath());
  }

  public function testRenderBasicCommands()
  {
    $commands = [
      new Data(),
      new Rset(),
      new Quit(),
    ];
    $r = new Renderer();
    foreach ($commands as $command) {
      $this->assertSame(
        $command->getVerb() . "\r\n",
        $r->renderCommand($command)
      );
    }
  }

  public function testRenderStringCommands()
  {
    $commandClasses = [
			Expn::class,
			Help::class,
			Noop::class,
			Vrfy::class,
    ];
    $r = new Renderer();
    foreach ($commandClasses as $commandClass) {
      $reflect = new ReflectionClass($commandClass);
      $optional = $reflect->getConstructor()?->getParameters()[0]?->isOptional();
      if ($optional) {
        $command = $reflect->newInstance();
        $this->assertSame(
          $command->getVerb() . "\r\n",
          $r->renderCommand($command)
        );
      }
      $string = 'test string';
      $command = $reflect->newInstance($string);
      $this->assertSame(
        $command->getVerb() . " $string\r\n",
        $r->renderCommand($command)
      );
    }
  }

  public function testRenderUnknownCommand()
  {
    $verb = 'UNKN';
    $string = 'I like turtles';
    $cmd = new Unknown($verb, $string);
    $renderer = new Renderer();
    $this->assertSame(
      "$verb $string\r\n",
      $renderer->renderCommand($cmd),
    );
  }

  public function testRenderReply()
  {
    $domain = 'normal.domain';
    $origin = new Domain($domain);
    $messages = [];
    $greeting = new Greeting($origin, $messages);
    $r = new Renderer();
    $this->assertSame(
      '220 ' . $domain . "\r\n",
      $r->renderGreeting($greeting)
    );
    $messages = ['first message', 'second message', '🌵 message'];
    $greeting = new Greeting($origin, $messages);
    $this->assertSame(
      implode(Lexeme::CRLF, [
        '220-' . $domain . ' first message',
        '220-second message',
        '220 🌵 message',
        '',
      ]),
      $r->renderReply($greeting)
    );
    $reply = new GenericReply(new Code('510'), [
      'first message',
      'second message',
      '🌵 message',
    ]);
    $this->assertSame(
      implode(Lexeme::CRLF, [
        '510-first message',
        '510-second message',
        '510 🌵 message',
        '',
      ]),
      $r->renderReply($reply)
    );
  }

  public function testRenderParam()
  {
    $renderer = new Renderer();
    $param = new Param('MUSHROOM', 'THIS->🍄');
    $this->assertSame(
      'MUSHROOM=THIS->+F0+9F+8D+84',
      $renderer->renderParam($param),
    );
  }
}

class MyForeignPath implements Path {
}

class MyForeignOrigin implements Origin {
}

class MyForeignReply implements Reply {
  public function getCode(): Code
  {
    return new Code('220');
  }
}
