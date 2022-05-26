<?php

namespace Tap\Smtp\Role\Client\Middleware;

use Tap\ReflectiveTap;
use Tap\Smtp\Element\Command\Data;
use Tap\Smtp\Element\Command\Ehlo;
use Tap\Smtp\Element\Command\Helo;
use Tap\Smtp\Element\Command\MailFrom;
use Tap\Smtp\Element\Command\RcptTo;
use Tap\Smtp\Element\Origin\Origin;
use Tap\Smtp\Element\Reply\Greeting;
use Tap\Smtp\Role\Client\Action\NewSession;
use Tap\Smtp\Role\Client\Action\ReceiveCommandReply;
use Tap\Smtp\Role\Client\Action\ReceiveGreeting;
use Tap\Smtp\Role\Client\Action\SendCommand;
use Tap\Smtp\Role\Client\Action\SendMail;
use Tap\Smtp\Role\Client\Exception\ClientSpokeTooEarly;
use Tap\Smtp\Role\Client\Exception\MissingHello;
use Tap\Smtp\Role\Client\Exception\MissingSession;
use Tap\Smtp\Role\Client\Exception\TemporarilyUnavailable;
use Tap\Smtp\Session\Session;

class ClientBehavior extends ReflectiveTap
{
  public const DEFAULT_TRANSACTION_ID = 'default';

  /**
   * Keeps track of the current mail being sent
   */
  protected ?SendMail $sendMail = null;

  public function __construct(
    public Origin $origin,
    public Session $session = new Session(self::DEFAULT_TRANSACTION_ID),
  )
  {
  }

  protected function receiveGreeting(ReceiveGreeting $action): void
  {
    $this->next($action);
    $this->session->receiveReply($action->greeting);
    $this->dispatch(
      new SendCommand(
        new Ehlo($this->origin)
      )
    );
  }

  protected function newSession(NewSession $action): void
  {
    $this->session = $action->session;
    $this->next($action);
  }

  protected function receiveCommandReply(ReceiveCommandReply $action): void
  {
    $this->next($action);
    $reply = $action->reply;
    $code = $reply->getCode();
    $command = $action->command;

    // Receive the reply into the session state
    $this->session->receiveReply($reply);

    // Respond to greeting with a EHLO
    if ($reply instanceof Greeting && $reply->code->isPositive()) {
      $this->dispatch(new SendCommand(
        new Ehlo($this->origin)
      ));
    }

    // Negative EHLO reply?
    elseif ($command instanceof Ehlo && $code->isNegative()) {
      if ($code->isPermanant()) {
        $this->dispatch(new SendCommand(
          new Helo($this->origin)
        ));
      } else {
        // TODO: this should be like... ServerReplyException or something
        // and include the actual reply on the Exception object
        throw new TemporarilyUnavailable(
          'Mail server is temporarily unavailable: ' . $reply->getCode()->value
        );
      }
    }

    // Continue sending mail?
    $sendMail = $this->sendMail;
    if ($sendMail && $reply->getCode()->isPositive()) {
      if ($action->command instanceof MailFrom) {
        foreach ($sendMail->forwardPaths as $forwardPath) {
          $this->dispatch(new SendCommand(
            new RcptTo($forwardPath),
          ));
        }
      } elseif ($action->command instanceof RcptTo) {
        if (!$this->session->awaitingReply()) {
          $this->dispatch(new SendCommand(
            new Data()
          ));
        }
      }
    }
  }

  protected function sendCommand(SendCommand $action): void
  {
    // Maybe let the server complain about this rather than limiting the client.
    if (!$this->session->greeting) {
      throw new ClientSpokeTooEarly(
        'Protocol error: client attempted to send a ' .
        $action->command->getVerb() . ' command before receiving server greeting.'
      );
    }
    $this->next($action);
    $this->session->receiveCommand($action->command);
  }

  protected function sendMail(SendMail $action): void
  {
    if (!$this->session) {
      throw new MissingSession('No mail session is available');
    }
    if (!$this->session->saidHello()) {
      throw new MissingHello('Clients must say hello before sending mail');
    }
    $this->next($action);
    $this->sendMail = $action;
    $this->dispatch(new SendCommand(new MailFrom($action->reversePath)));
  }
}


