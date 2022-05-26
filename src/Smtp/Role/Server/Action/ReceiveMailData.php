<?php declare(strict_types=1);

namespace Tap\Smtp\Role\Server\Action;

/**
 * TODO:
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
 */
class ReceiveMailData extends ServerAction
{
  public function __construct(
    /**
     * @var resource
     */
    public $dataStream,
  )
  {
  }
}

