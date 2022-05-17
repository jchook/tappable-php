# Tappable: a Plugin Framework for PHP

Tap helps you create modular PHP apps that accept plugins or "middleware"
similar to redux, express, haraka, etc.

## Concepts

A `Tap` is a plugin that can be added to a `Tappable` app.

The common interface for each plugin is called an `Action`. When a `Tappable`
app dispatches an `Action`, it passes sequentially through each registered
plugin.

Plugins can respond to any action of the application or other plugins, store
internal state, expose methods, dispatch new or custom actions, or throw errors.

Each plugin wraps the next plugin in the chain, allowing plugins to modify
actions before forwarding them down the chain, and to wrap other plugins with
try/catch, etc.

This design enables plugin authors to write modular functionality with
versatile, statically-typed interoperability and powerful control flow.


## Example Usage

```php
<?php

class MyApp extends App {}

class MyAction extends BasicAction {}

class MyTap extends BasicTap {}

$app = new MyApp();
$app->tap(new MyTap());
$app->dispatch(new MyAction());

?>
```

