<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Agent\Action;

use Tap\Smtp\Element\ReversePath;
use Tap\Smtp\Element\ForwardPath;
use Tap\Smtp\Role\Agent\Action\AgentAction;

/**
 * 2.3.1.  Mail Objects
 *
 *    SMTP transports a mail object.  A mail object contains an envelope
 *    and content.
 *
 *    The SMTP envelope is sent as a series of SMTP protocol units
 *    (described in Section 3).  It consists of an originator address (to
 *    which error reports should be directed), one or more recipient
 *    addresses, and optional protocol extension material.
 *
 * Can't decide whether we should store the protocol units or just the paths
 * and data stream. Yes the agents have access to Session, but mail transaction
 * information can be cleared from the session with EHLO, MAIL, or RSET.
 */
class MailAction extends AgentAction
{
  public function __construct(
    public ReversePath $reversePath,
    /**
     * @var ForwardPath[]
     */
    public array $forwardPaths,
    /**
     * @var resource
     */
    public $dataStream,
  )
  {
  }
}

