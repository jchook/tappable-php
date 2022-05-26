Autocomplete
------------

Autocomplete is feedback. When code has good autocomplete, it means you
organized things well.


Tests
-----

One goal is:

Have a predefined set of SMTP conversations in textual form and then force the
system to parse, render, and interpret those conversations.

Can test all kinds of situations with purely textual test cases.


Public Properties
-----------------

ONLY the "pure data" namespaces like `Element` and `Action` may have `public`
properties. That's it. Everything else MUST expose properties with methods.

Unfortunately `interface` in PHP cannot expose properties. Why? I dunno! So a
pure data namespace may also expose getters and setters as needed, however they
MUST NOT do any logic other than getting or setting.

How do you know if a namespace is pure data? The data model is dumb on purpose
and does NO logic except assign defaults. Just Data™, except Just Typed Data™.


Namespaces
----------

Every time you see like `Session`, `SessionState`, `SessionTranscript`, etc,
it's probably better to subnamespace it. Why not?

It's a real pain to move it later, so just put it there now.


Public Arrays
-------------

Every time you want to expose an array (public or protected), it MUST be
0-indexed, contiguous, and homogeneous. So-called "associative arrays" are not
allowed. Use a class, trait, or interface instead.

Recent benchmarks show that class instances have better performance than
associative arrays, especially when in a (large) array.


Binary data
-----------

This is a line-based parser so far. Let it be known! Support binary data with
Taps.


Pipelining
----------

There are two main ways to handle this:

1. The Session and ClientBehavior etc will be pipelining-aware or
2. The system is completely agnostic about it except for middleware

Really, #2 seems cleaner and better. The trick is that we cannot dispatch
all of our RCPT actions in rapid succession. Need to keep track of which ones
we have dispatched so far and dispatch after each reply is received. Actually
that precludes the ability to even *do* pipelining... since further RCPT actions
will not be dispatched until the reply is made available.

So some middleware will need to be careful about buffering successive commands
or replies to non-pipelining-aware agents.

Eventually we can support all of these via middleware...

- PIPELINING
- SIZE 10240000
- VRFY
- ETRN
- STARTTLS
- ENHANCEDSTATUSCODES
- 8BITMIME
- DSN
- SMTPUTF8
- CHUNKING


Sessions
--------

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


Minimum Implementation
----------------------

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


