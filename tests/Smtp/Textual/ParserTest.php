<?php

namespace Tap\Smtp\Test;

use PHPUnit\Framework\TestCase;
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
use Tap\Smtp\Element\Origin\AddressLiteral;
use Tap\Smtp\Element\Origin\Domain;
use Tap\Smtp\Element\Param;
use Tap\Smtp\Element\Reply\Code;
use Tap\Smtp\Element\Reply\EhloKeywordBase;
use Tap\Smtp\Element\Reply\EhloParamBase;
use Tap\Smtp\Element\Reply\EhloReply;
use Tap\Smtp\Element\Reply\GenericReply;
use Tap\Smtp\Element\Reply\Greeting;
use Tap\Smtp\Element\Reply\Reply;
use Tap\Smtp\Element\ReversePath;
use Tap\Smtp\Textual\Exception\IncompleteReply;
use Tap\Smtp\Textual\Exception\MultipleReplies;
use Tap\Smtp\Textual\Exception\TextualException;
use Tap\Smtp\Textual\Parser;
use Tap\Smtp\Textual\Renderer;

class ParserTest extends TestCase
{
  public function testIsPartialReply()
  {
    $this->assertTrue(
      Parser::isPartialReply("220-ducks.gov honk\r\n")
    );
    $this->assertFalse(
      Parser::isPartialReply("220 ducks.gov honk\r\n")
    );
    $this->assertFalse(
      Parser::isPartialReply("\r\n")
    );
  }

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

  public function testParseEhloReply()
  {
    $parser = new Parser();
    $domain = 'normal.domain';
    $origin = new Domain($domain);
    $keywords = [
      new EhloKeywordBase('KEYWORD', [
        new EhloParamBase('SIZE=9001'),
        new EhloParamBase('ID'),
      ]),
      new EhloKeywordBase('DUCK', [
        new EhloParamBase('FLY=YES'),
        new EhloParamBase('ID'),
      ]),
    ];
    $greet = "Ello cap'n!";
    $reply = new EhloReply(Code::ehloOk(), $origin, $greet, $keywords);
    $replyStr = implode("\r\n", [
      "220-$domain $greet",
      "220-KEYWORD SIZE=9001 ID",
      "220 DUCK FLY=YES ID",
      "",
    ]);
    $this->assertEquals(
      $reply,
      $parser->parseEhloReply($replyStr),
    );
  }

  public function testParseInvalidDataCommand()
  {
    $this->expectException(TextualException::class);
    $parser = new Parser();
    $parser->parseCommand('DATA with more gusto');
  }

  public function testParseInvalidRcptToCommand()
  {
    $this->expectException(TextualException::class);
    $parser = new Parser();
    $parser->parseCommand("RCPT FROM:<guy@tree.org>\r\n");
  }

  /**
   * @dataProvider getInvalidMailboxStrings
   */
  public function testParseInvalidMailbox(string $mailboxString)
  {
    $this->expectException(TextualException::class);
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
      new MailFrom(new ReversePath(new Mailbox('hank', new Domain('propane.com')))),
      $parser->parseCommand('MAIL FROM:<@a,@b:hank@propane.com>'),
    );
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

  public function testParseGreetingOrReply()
  {
    $parser = new Parser();
    $originStr = 'ducks.gov';
    $origin = new Domain($originStr);
    $messages = ['test one', 'test two'];
    $greeting = new Greeting($origin, $messages);
    $greetingStr = implode("\r\n", [
      "220-$originStr {$messages[0]}",
      "220 {$messages[1]}",
      "",
    ]);
    $this->assertEquals(
      $greeting,
      $parser->parseGreetingOrReply($greetingStr),
    );
    $this->assertEquals(
      $greeting,
      $parser->parseGreeting($greetingStr),
    );
    $error = new GenericReply(new Code('554'), $messages);
    $errorStr = implode("\r\n", [
      "554-{$messages[0]}",
      "554 {$messages[1]}",
      "",
    ]);
    $this->assertEquals(
      $error,
      $parser->parseGreetingOrReply($errorStr),
    );
  }

  public function testParseReplies()
  {
    $repliesStr = implode("\r\n", [
      '220-ducks.gov Howdy cowboy',
      '220-KEYWORD DUCKS ARE COOL',
      '220 AMIRITE',
      '250-Ok',
      '250 No really, it\'s okay',
      '250 Ok',
      '',
    ]);
    $parser = new Parser();
    $replies = [
      new EhloReply(Code::ehloOk(), new Domain('ducks.gov'), 'Howdy cowboy', [
        new EhloKeywordBase('KEYWORD', [
          new EhloParamBase('DUCKS'),
          new EhloParamBase('ARE'),
          new EhloParamBase('COOL'),
        ]),
        new EhloKeywordBase('AMIRITE'),
      ]),
      new GenericReply(Code::ok(), ['Ok', 'No really, it\'s okay']),
      new GenericReply(Code::ok(), ['Ok']),
    ];
    $this->assertEquals(
      $replies,
      $parser->parseReplies($repliesStr),
    );
  }

  public function testParseRepliesWithIncompleteReply()
  {
    $this->expectException(IncompleteReply::class);
    $repliesStr = implode("\r\n", [
      '250 Ok',
      '250-Ok',
      '',
    ]);
    $parser = new Parser();
    $parser->parseReplies($repliesStr);
  }


  public function testParseReplyWithNonTerminatedLine()
  {
    $this->expectException(TextualException::class);
    $this->expectErrorMessageMatches('/non-terminated/');
    $parser = new Parser(smtputf8: true);
    $parser->parseReply(implode("\r\n", [
      '220-ducks.gov honk',
      '220 ANIMAL DUCK=YES',
    ]));
  }

  public function testParseReplyWithMultipleReplies()
  {
    $this->expectException(MultipleReplies::class);
    $parser = new Parser(smtputf8: true);
    $parser->parseReply(implode("\r\n", [
      '220-ducks.gov honk',
      '220 ANIMAL DUCK=YES',
      '250 ok',
      ''
    ]));
  }

  public function testParseReplyWithIncompleteReply()
  {
    $this->expectException(IncompleteReply::class);
    $parser = new Parser(smtputf8: true);
    $parser->parseReply(implode("\r\n", [
      '220-ducks.gov honk',
      '220-ANIMAL DUCK=YES',
      ''
    ]));
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

  public function getParserCommandsUtf8(): array
  {
    return [
      [new Data()],
      [new Ehlo(new Domain('🐢.com'))],
      [new Ehlo(new AddressLiteral('127.0.0.1'))],
      [new EndOfData()],
      [new Expn('🐢turtle@🐢turtle.com')],
      [new Helo(new Domain('🐢.com'))],
      [new Help()],
      [new Help('thing')],
      [new MailFrom(new ReversePath(null))],
      [new MailFrom(new ReversePath(null), [new Param('PARAM1'), new Param('PARAM2', 'MU🍄SH')])],
      [new MailFrom(new ReversePath(new Mailbox('🤠', new Domain('🐢.com'))))],
      [new Noop()],
      [new Noop('🚧 test 🚧')],
      [new Quit()],
      [new RcptTo(new ForwardPath(new Mailbox('🤠', new Domain('🐢.com'))))],
      [new RcptTo(new ForwardPath(new Mailbox('normal', new Domain('dot.com'))))],
      [new Rset()],
      [new Unknown('UNKN', '🐢 I like turtles 🐢')],
      [new Unknown('UNKN')],
      [new Vrfy('🐢turtle@🐢turtle.com')],
    ];
  }

  /**
   * @dataProvider getParserReplies
   */
  public function testParseRenderedReply(Reply $reply)
  {
    $renderer = new Renderer(smtputf8: true);
    $parser = new Parser(smtputf8: true);
    $parsed = $parser->parseReply(
      $renderer->renderReply($reply)
    );
    $this->assertEquals($reply, $parsed);
  }

  public function getParserReplies(): array
  {
    return [
      [new GenericReply(Code::ok(), ['Ello m8', 'Do you have any bread?'])],
      [
        new EhloReply(Code::ehloOk(), new Domain('ducks.gov'), 'Ello m8', [
          new EhloKeywordBase('CHEF', [
            new EhloParamBase('BOYARDEE'),
            new EhloParamBase('🦆'),
          ]),
        ])
      ],
    ];
  }
}

