<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Server\Action;

use Tap\Smtp\Element\Command\MailFrom;
use Tap\Smtp\Element\Command\RcptTo;

/**
 * TODO: Separate actions for SendCommand and ReceiveCommand? Are they the same?
 * These are really identical between server and client... we ought to
 * simplify and combine them into MailDataAction or something like that.
 *
 * Same with Send/Receive actions -- can be a unified action.
 *
 * Originally I wanted to make them distinct but now I don't like it.
 * I guess one important distinction is for middleware that applies to both
 * clients and servers... it could determine the difference between the
 * Send/Receive actions and perform the correct action.
 *
 * One example might be DKIM, where clients should sign outbound messages
 * and servers should verify incoming messages. This can be alleviated by
 * instructing the middleware what to do manually or splitting it into two
 * middlewares that perform each separate agent role.
 *
 * Another example might be PIPELINING support, which perhaps needs to know
 * the difference between SendCommand and ReceiveCommand to work universally
 * on servers and clients alike. Hmmm.... or does it?
 *
 * So maybe having the actions split out is actually meaningfully good.
 *
 * Or maybe it's bad -- now to handle a command generically, I need to check
 * for SendCommand and ReceiveCommand.
 *
 * One tell is that there's no really good name for a CommandAction except that.
 * Command confuses actions with Elements.
 *
 * Okay, how about both? You have a CommandAction that is extended by both
 * ReceiveCommand and SendCommand. That way you can handle them discretely or
 * independently.
 *
 * This brings up important questions about ReflectiveTap and inheritance.
 */
class ReceiveMail extends ServerAction
{
  public function __construct(
    public ?MailFrom $mailFrom = null,
    /**
     * @var RcptTo[]
     */
    public array $rcptTos = [],
    /**
     * @var resource
     */
    public $dataStream = null,
  )
  {
  }
}

