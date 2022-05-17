# Tap: a Plugin Framework for PHP

Tap helps you create modular PHP apps that accept plugins or "middleware"
similar to redux, express, haraka, etc.

## Concepts

At its core, a `Tappable` app is a chain of plugin functions.

When a `Tappable` app dispatches an `Action`, it passes sequentially through
each plugin, called a `Tap`.

Plugins can respond to any action of the application or other plugins, store
internal state, expose methods, dispatch new or custom actions, or throw errors.

Each plugin wraps the next plugin in the chain, allowing plugins to modify
actions before forwarding them down the chain, and to wrap other plugins with
try/catch, etc.

## Example Usage

```php
<?php

use Tap\{App, BasicTap, Tap, Tappable};

class MyApp implements Tappable
{
  use App;
}

class MyTap implements Tap
{
  use BasicTap;
  public function handleAction(Action $action)
  {
    $this->next($action);
  }
}

?>
```
