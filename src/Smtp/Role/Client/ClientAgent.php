<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Client;

use Tap\Smtp\Element\Command\Quit;
use Tap\Smtp\Element\Origin\Origin;
use Tap\Smtp\Role\Agent\Agent;
use Tap\Smtp\Role\Client\Action\SendCommand;
use Tap\Smtp\Role\Client\Action\SendMail;
use Tap\Smtp\Role\Client\Middleware\ClientBehavior;
use Tap\Smtp\Session\Session;
use Tap\Smtp\Support\SystemDomain;

class ClientAgent extends Agent
{
  public ClientBehavior $smtp;
  public Origin $origin;

  public function __construct(
    ?Origin $origin = null,
    ?ClientBehavior $smtp = null,
    ...$userTaps,
  )
  {
    // TODO: maybe grab ClientBehavior wherever it appears in the userTaps
    // and make one if it doesn't exist. This would allow folks to wrap it
    // in other middleware.
    $this->origin = $origin ?? new SystemDomain();
    $this->smtp = $smtp ?? new ClientBehavior($this->origin);
    $this->tap(
      $this->smtp,
      ...$userTaps,
    );
  }

  public function getSession(): Session
  {
    return $this->smtp->session;
  }

  public function sendMail(SendMail $sendMail): self
  {
    $this->dispatch($sendMail);
    return $this;
  }

  public function quit(Quit $quit = null): self
  {
    $this->dispatch(new SendCommand($quit ?? new Quit()));
    return $this;
  }
}

