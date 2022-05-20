<?php

namespace Tap\Smtp\Test;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tap\Smtp\Element\Command\Command;
use Tap\Smtp\Element\Command\Data;
use Tap\Smtp\Element\Command\Ehlo;
use Tap\Smtp\Element\Command\EndOfData;
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
use Tap\Smtp\Element\Origin;
use Tap\Smtp\Element\OriginAddressLiteral;
use Tap\Smtp\Element\OriginDomain;
use Tap\Smtp\Element\Param;
use Tap\Smtp\Element\Path;
use Tap\Smtp\Element\Reply\Code;
use Tap\Smtp\Element\Reply\GenericReply;
use Tap\Smtp\Element\Reply\Greeting;
use Tap\Smtp\Element\Reply\Reply;
use Tap\Smtp\Element\ReversePath;
use Tap\Smtp\Textual\Parser;
use Tap\Smtp\Textual\Renderer;

class ParserTest extends TestCase
{
  public function testParseCommand()
  {
    $p = new Parser();
    $verb = 'UNKN';
    $string = '🐢🐢🐢';
    $str = "$verb $string\r\n";
    $cmd = $p->parseCommand($str);
    $this->assertEquals(
      new Unknown($verb, $string),
      $cmd
    );
  }

  public function testParseInvalidDataCommand()
  {
    $this->expectException(InvalidArgumentException::class);
    $parser = new Parser();
    $parser->parseCommand('DATA with more gusto');
  }

  public function testParseInvalidRcptToCommand()
  {
    $this->expectException(InvalidArgumentException::class);
    $parser = new Parser();
    $parser->parseCommand("RCPT FROM:<guy@tree.org>\r\n");
  }

  /**
   * @dataProvider getInvalidMailboxStrings
   */
  public function testParseInvalidMailbox(string $mailboxString)
  {
    $this->expectException(InvalidArgumentException::class);
    $parser = new Parser();
    $parser->parseMailbox($mailboxString);
  }

  public function getInvalidMailboxStrings(): array
  {
    return [
      ['"hank@test.com'],
      ['hank"@test.com'],
      ['hank"'],
      ['hank'],
    ];
  }

  public function testParseAdl()
  {
    $parser = new Parser();
    $this->assertEquals(
      new MailFrom(new ReversePath(new Mailbox('hank', new OriginDomain('propane.com')))),
      $parser->parseCommand('MAIL FROM:<@a,@b:hank@propane.com>'),
    );
  }

  /**
   * @dataProvider getParserCommandsUtf8
   */
  public function testParseRenderedUtf8(Command $cmd)
  {
    $renderer = new Renderer(smtputf8: true);
    $parser = new Parser(smtputf8: true);
    $parsed = $parser->parseCommand(
      $renderer->renderCommand($cmd)
    );
    $this->assertEquals($cmd, $parsed);
  }

  public function testParseParam()
  {
    $parser = new Parser(smtputf8: true);
    $param = new Param('MUSHROOM', '🍄');
    $this->assertEquals(
      $param,
      $parser->parseParam('MUSHROOM=+F0+9F+8D+84'),
    );
  }

  public function getParserCommandsUtf8(): array
  {
    return [
      [new Data()],
      [new Ehlo(new OriginDomain('🐢.com'))],
      [new Ehlo(new OriginAddressLiteral('127.0.0.1'))],
      [new EndOfData()],
      [new Expn('🐢turtle@🐢turtle.com')],
      [new Helo(new OriginDomain('🐢.com'))],
      [new Help()],
      [new Help('thing')],
      [new MailFrom(new ReversePath(null))],
      [new MailFrom(new ReversePath(null), [new Param('PARAM1'), new Param('PARAM2', 'MU🍄SH')])],
      [new MailFrom(new ReversePath(new Mailbox('🤠', new OriginDomain('🐢.com'))))],
      [new Noop()],
      [new Noop('🚧 test 🚧')],
      [new Quit()],
      [new RcptTo(new ForwardPath(new Mailbox('🤠', new OriginDomain('🐢.com'))))],
      [new RcptTo(new ForwardPath(new Mailbox('normal', new OriginDomain('dot.com'))))],
      [new Rset()],
      [new Unknown('UNKN', '🐢 I like turtles 🐢')],
      [new Unknown('UNKN')],
      [new Vrfy('🐢turtle@🐢turtle.com')],
    ];
  }
}

