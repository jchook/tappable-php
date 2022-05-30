<?php

namespace Tap\Smtp\Role\Server\Middleware;

use Tap\ReflectiveTap;
use Tap\Smtp\Element\Command\Data;
use Tap\Smtp\Element\Command\DataStream;
use Tap\Smtp\Element\Command\Ehlo;
use Tap\Smtp\Element\Command\Helo;
use Tap\Smtp\Element\Command\MailFrom;
use Tap\Smtp\Element\Command\Noop;
use Tap\Smtp\Element\Command\Quit;
use Tap\Smtp\Element\Command\RcptTo;
use Tap\Smtp\Element\Command\Rset;
use Tap\Smtp\Element\Command\Unknown;
use Tap\Smtp\Element\Command\Vrfy;
use Tap\Smtp\Element\Origin\Domain;
use Tap\Smtp\Element\Reply\Greeting;
use Tap\Smtp\Element\Reply\ReplyFactory;
use Tap\Smtp\Role\Agent\Action\NewSession;
use Tap\Smtp\Role\Server\Action\ReceiveCommand;
use Tap\Smtp\Role\Server\Action\ReceiveMail;
use Tap\Smtp\Role\Server\Action\SendCommandReply;
use Tap\Smtp\Role\Server\Action\SendGreeting;
use Tap\Smtp\Session\Session;
use Tap\Smtp\Support\SystemDomain;

class ServerBehavior extends ReflectiveTap
{
  public const DEFAULT_SESSION_ID = 'default';

  public function __construct(
    public Domain $domain = new SystemDomain(),
    public Session $session = new Session(self::DEFAULT_SESSION_ID),
    public $dataStream = null,
  )
  {
  }

  protected function newSession(NewSession $action): void
  {
    $this->next($action);
    $this->session = $action->session;
    $this->dispatch(
      new SendGreeting(
        new Greeting($this->domain),
        ['Tappable SMTP Server'],
      )
    );
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
      $reply = ReplyFactory::ehloOk($this->domain);
    } elseif ($command instanceof Helo) {
      $reply = ReplyFactory::heloOk($this->domain);
    } elseif ($command instanceof RcptTo || $command instanceof Vrfy) {
      $reply = ReplyFactory::mailboxUnavailable();
    } elseif ($command instanceof Data) {
      $reply = ReplyFactory::startMailInput();
    } elseif ($command instanceof DataStream) {
      $reply = ReplyFactory::ok();
    } elseif (
      $command instanceof MailFrom ||
      $command instanceof Rset ||
      $command instanceof Noop
    ) {
      $reply = ReplyFactory::ok();
    } elseif ($command instanceof Quit) {
      $reply = ReplyFactory::serviceClosingChannel();
    } elseif ($command instanceof Unknown) {
      $reply = ReplyFactory::commandUnrecognized();
    } else {
      $reply = ReplyFactory::commandNotImplemented();
    }

    $this->dispatch(
      new SendCommandReply($command, $reply)
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

    $command = $action->command;
    $reply = $action->reply;

    if ($reply->getCode()->isCompletion()) {
      if ($command instanceof DataStream) {
        $this->dispatch(new ReceiveMail(
          $this->session->mailFrom->reversePath,
          $this->session->getForwardPaths(),
          $this->session->dataStream->dataStream,
        ));
      }
    }
  }
}

