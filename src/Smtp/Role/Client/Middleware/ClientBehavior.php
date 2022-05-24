<?php

namespace Tap\Smtp\Role\Client\Middleware;

use Tap\ReflectiveTap;
use Tap\Smtp\Element\Command\Data;
use Tap\Smtp\Element\Command\Ehlo;
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
use Tap\Smtp\Session\Session;

class ClientBehavior extends ReflectiveTap
{
  public const DEFAULT_TRANSACTION_ID = 'default';

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

    // Receive the reply into the session state
    $this->session->receiveReply($reply);

    // Respond to greeting with a EHLO
    if ($reply instanceof Greeting && $reply->code->isPositive()) {
      $this->dispatch(new SendCommand(
        new Ehlo($this->origin)
      ));
    }

    // Are we in the process of sending mail in an automated fashion?
    // Was the reply successful?
    // Okay, then update state and send the next command!
    if ($this->session->sendMail && $reply->getCode()->isPositive()) {
      if ($action->command instanceof MailFrom) {
        foreach ($this->session->rcptTos as $rcptTo) {
          $this->dispatch(new SendCommand(
            new RcptTo($rcptTo)
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
    //
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
    $this->session->prepareToSendMail($action);
    $this->dispatch(new SendCommand(new MailFrom($action->reversePath)));
  }
}


