<?php

namespace Tap\Smtp\Role\Server\Middleware;

use Tap\ReflectiveTap;
use Tap\Smtp\Element\Command\Data;
use Tap\Smtp\Element\Command\Ehlo;
use Tap\Smtp\Element\Command\EndOfData;
use Tap\Smtp\Element\Command\Helo;
use Tap\Smtp\Element\Command\MailFrom;
use Tap\Smtp\Element\Command\Noop;
use Tap\Smtp\Element\Command\Quit;
use Tap\Smtp\Element\Command\RcptTo;
use Tap\Smtp\Element\Command\Rset;
use Tap\Smtp\Element\Command\Vrfy;
use Tap\Smtp\Element\Origin\Domain;
use Tap\Smtp\Element\Reply\Code;
use Tap\Smtp\Element\Reply\EhloKeywordBase;
use Tap\Smtp\Element\Reply\EhloReply;
use Tap\Smtp\Element\Reply\Greeting;
use Tap\Smtp\Element\Reply\ReplyFactory;
use Tap\Smtp\Role\Server\Action\NewSession;
use Tap\Smtp\Role\Server\Action\ReceiveCommand;
use Tap\Smtp\Role\Server\Action\SendCommandReply;
use Tap\Smtp\Role\Server\Action\SendGreeting;
use Tap\Smtp\Session\Session;

class ServerBehavior extends ReflectiveTap
{
  public const DEFAULT_TRANSACTION_ID = 'default';

  public function __construct(
    public Domain $domain,
    public Session $session = new Session(self::DEFAULT_TRANSACTION_ID),
  )
  {
  }

  protected function newSession(NewSession $action): void
  {
    $this->next($action);
    $this->session = $action->session;
    $this->dispatch(
      new SendGreeting(
        new Greeting($this->origin),
        ['Tappable SMTP Server'],
      )
    );
  }

  protected function sendGreeting(SendGreeting $action): void
  {
    $this->next($action);
    $this->session->receiveGreeting($action->greeting);
  }

  protected function sendCommandReply(SendCommandReply $action): void
  {
    $this->next($action);
    $this->session->receiveReply($action->reply);
  }

  /**
   * 4.5.1.  Minimum Implementation
   *
   *    In order to make SMTP workable, the following minimum implementation
   *    MUST be provided by all receivers.  The following commands MUST be
   *    supported to conform to this specification:
   *
   *       EHLO
   *       HELO
   *       MAIL
   *       RCPT
   *       DATA
   *       RSET
   *       NOOP
   *       QUIT
   *       VRFY
   */
  protected function receiveCommand(ReceiveCommand $action)
  {
    $this->next($action);
    $command = $action->command;
    $this->session->receiveCommand($command);

    if ($command instanceof Ehlo) {
      $reply = new EhloReply(Code::ok(), $this->domain, 'Hi :)', [
        new EhloKeywordBase('SMTPUTF8'),
      ]);
    } elseif ($command instanceof RcptTo || $command instanceof Vrfy) {
      $reply = ReplyFactory::mailboxUnavailable();
    } elseif ($command instanceof Helo) {
      $reply = ReplyFactory::heloOk($this->domain->domain);
    } elseif ($command instanceof Data) {
      $reply = ReplyFactory::startMailInput();
    } elseif ($command instanceof EndOfData) {
      $reply = ReplyFactory::ok();
    } elseif (
      $command instanceof MailFrom ||
      $command instanceof Rset ||
      $command instanceof Noop
    ) {
      $reply = ReplyFactory::ok();
    } elseif ($command instanceof Quit) {
      $reply = ReplyFactory::serviceClosingChannel();
    } else {
      $reply = ReplyFactory::commandUnrecognized();
    }

    $this->dispatch(
      new SendCommandReply($command, $reply)
    );
  }
}

