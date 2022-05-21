This is a line-based parser so far. Let it be known!


---

## Sessions

Unsure whether to allow client and server to manage multiple transactions at a
time.

On the one hand I like the simplicity of only maintaining a single transaction
per agent instance. That dramatically simplifies things and means we do not
need to pass the transaction around everywhere or worry about tons of stale
transaction data getting lost in middleware instance state.

In the single transaction scenario, a concurrent agent would need to manage
instances/factories of the non-concurrent agents, one per transaction, and could
flush the entire client instance and all of its middleware whenever a transaction
completes, or perhaps maintain 10 or so of them and reuse them.

Yeah I kinda like that best. It keeps the middleware from needing to manage
multiple transactions.


4.5.1.  Minimum Implementation

   In order to make SMTP workable, the following minimum implementation
   MUST be provided by all receivers.  The following commands MUST be
   supported to conform to this specification:

      EHLO
      HELO
      MAIL
      RCPT
      DATA
      RSET
      NOOP
      QUIT
      VRFY

   Any system that includes an SMTP server supporting mail relaying or
   delivery MUST support the reserved mailbox "postmaster" as a case-
   insensitive local name.  This postmaster address is not strictly
   necessary if the server always returns 554 on connection opening (as
   described in Section 3.1).  The requirement to accept mail for
   postmaster implies that RCPT commands that specify a mailbox for
   postmaster at any of the domains for which the SMTP server provides
   mail service, as well as the special case of "RCPT TO:<Postmaster>"
   (with no domain specification), MUST be supported.

   SMTP systems are expected to make every reasonable effort to accept
   mail directed to Postmaster from any other system on the Internet.
   In extreme cases -- such as to contain a denial of service attack or
   other breach of security -- an SMTP server may block mail directed to
   Postmaster.  However, such arrangements SHOULD be narrowly tailored
   so as to avoid blocking messages that are not part of such attacks.


