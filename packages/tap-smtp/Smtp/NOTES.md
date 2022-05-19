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

