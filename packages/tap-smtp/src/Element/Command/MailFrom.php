<?php declare(strict_types=1);

namespace Tap\Smtp\Element\Command;

use Tap\Smtp\Element\Param;
use Tap\Smtp\Element\ReversePath;

/**
 * 4.1.1.2.  MAIL (MAIL)
 *
 *    This command is used to initiate a mail transaction in which the mail
 *    data is delivered to an SMTP server that may, in turn, deliver it to
 *    one or more mailboxes or pass it on to another system (possibly using
 *    SMTP).  The argument clause contains a reverse-path and may contain
 *    optional parameters.  In general, the MAIL command may be sent only
 *    when no mail transaction is in progress, see Section 4.1.4.
 *
 *    The reverse-path consists of the sender mailbox.  Historically, that
 *    mailbox might optionally have been preceded by a list of hosts, but
 *    that behavior is now deprecated (see Appendix C).  In some types of
 *    reporting messages for which a reply is likely to cause a mail loop
 *    (for example, mail delivery and non-delivery notifications), the
 *    reverse-path may be null (see Section 3.6).
 *
 *    This command clears the reverse-path buffer, the forward-path buffer,
 *    and the mail data buffer, and it inserts the reverse-path information
 *    from its argument clause into the reverse-path buffer.
 *
 *    If service extensions were negotiated, the MAIL command may also
 *    carry parameters associated with a particular service extension.
 *    Syntax:
 *
 *    mail = "MAIL FROM:" Reverse-path
 *                                        [SP Mail-parameters] CRLF
 */
class MailFrom implements Command
{
  public string $verb = 'MAIL';
  /**
   * @var Param[]
   */
  public array $params = [];
	public function __construct(
    public ReversePath $reversePath,
    ...$params,
	)
	{
    $this->params = $params;
	}
}

