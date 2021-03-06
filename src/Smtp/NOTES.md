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


Line-based parser
-----------------

This is a line-based parser so far. Let it be known! It would be neat to
upgrade it to an LALR or LL(1) parser or some such but that has proven to be
very slow/cumbersome in PHP.


Mail Data
---------

I feel tempted to emit mail data in buffer chunks, allowing the core stack
to completely avoid mucking with streams. Delivery middleware can push the
data chunks onto a temp stream and then emit a final "ReceivedMailData" action
with the stream attached to it for various other middleware to consume and
manipulate as a whole.

The question is... should all mail be "spooled" first and then "delivered"
afterwards? I suppose that's the cleanest way, especially since we are told
by RFC 5321 to respond to <CRLF>.<CRLF> as quickly as possible and not to
delay it by handling the mail first.

So perhaps the stream-based middleware spools the entire data stream into a
spool stream and then attaches that to the ReceivedMailData action.

Also the EndOfData command has always been a bit smelly. It's kind of a command
because it receives a reply, but also it doesn't have a proper verb except ".".
It would be nice to handle end of data in the same switch where your other
state changes / commands live.

It seems we have built an SMTP server that does everything except handle mail.

The Renderer and Parser should dot-stuff and de-dot-stuff, except we do not
want to be rendering mail data to a string. We could return a generator that
produces chunks from the stream, or use a stream filter, or render to a stream.

Render to a stream seems correct. Perhaps user-supplied.

Seeing now:

Emitting data in chunks does not actually allow the core stack to ignore
streams of data, because they can never represent the complete data as a
string.

Receiving mail data is a core function of the SMTP server behavior itself.
However, I feel it should receive the data as a reliable, spooled stream.
Spooling the stream is a concern external to the core SMTP behavior. It needs
to be handled by optional spooling, e.g. a /dev/null spooling agent, mbox,
maildir, sql, etc.



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


