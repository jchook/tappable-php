The idea here is to kind of invert the middleware/plugin flow for better typing.

Often folks will have some geneic "app" that accepts dynamic "middleware" that
independently executes and has internal state but cannot really be accessed from
the outside unless it's created first, stored, inserted into the app, and then
passed around from site to site.

Instead we could create a MyApp class that actually lists the extensions it has.
This would give a known namespace to the extension, and let you access its
internal state or invoke it's methods with type information/autocomplete.


```php
<?php

namespace Tap\Smtp\Auth;

use Jchook\Mime\Message;
use Tap\Smtp\Auth\Auth;
use Tap\Smtp\Auth\AuthResult;
use Tap\Smtp\Mime\Mime;
use Tap\Tap;

class Spf
{
  use Tap, Auth, Mime;

  // thanks to MimePlugin we can do something with our message after it's parsed
  public function mimeMessageReceived(Message $message)
  {
    $result = $this->spfValidate($message);
    $this->auth->dispatchResult($result);
  }

  public function spfValidate(Message $message): AuthResult
  {
    $this->validator->validate($message);
  }
}

?>
```

Holy shit so PHP respects the *order* of the "use" trait statements!
Reflections will see the properties and methods in the correct order!

```php
<?php

namespace Tap\Smtp;

trait Tap {
}

trait Smtp {
  use Tap;
}


$app = new MyClient();
$app->smtp->command(new Command([Command::EHLO]));
$app->smtp->reply(new Reply(250));

class MyClient {
  use Tap, Smtp;
  public function command(Command $command)
  {
    return $this->next();
  }
}


?>
```


One crazy idea would be to create a separate class for each action type.
The downside here is that you have to list the same method signature more
than once (at least twice -- one for handle() one for dispatch()). Also it's
unclear how any other process would easily add this.

Also middleware would need to `use` each separate action from a package.
That part is kinda neat tbh.

From a code-generation standpoint... creating dispatchers is a little easier
because it's a separate file, e.g.

`$app->smtp->dispatch->command($cmd);`

With this it would look more like...

`$app->smtp->command->dispatch($cmd)`

```php
<?php

trait Command
{
  use Action;
  public function handle(Smtp\Command $command)
  {

  }

  public function dispatch(Smtp\Command $command)
  {
  }
}

?>
```

One idea is you could have the normal Middleware architecture underneath,
but wrap it with this manager class.

The manager class would expose all the dispatchers of the various middleware.
Each middleware would be bound to the next at runtime.

The middleware would intercept the actions and then call the appropriate
function, or forward to the next middleware.

---

Another idea is to have action classes that can be passed around.
This seems more ideal...

Could combine these two ideas...

Maybe actions don't even need a type string... their class name can be their
unique identifier. Also allows for extending actions....

