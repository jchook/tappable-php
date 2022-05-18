# Tappable: a Plugin Framework for PHP

![License MIT](https://img.shields.io/badge/license-MIT-brightgreen)
![Test Coverage 89%](https://img.shields.io/badge/test%20coverage-89%25-green)
![PHP](https://img.shields.io/badge/php-8.1%2B-787cb5)

Tappable helps you create modular PHP apps that accept plugins or "middleware"
similar to redux, express, haraka, etc.

## Concepts

A `Tap` is a plugin that can be added to a `Tappable` app.

An `Action` is the common interface between all plugins.

Whenever a `Tappable` app dispatches an `Action`, it passes sequentially through
each registered plugin. Plugins can respond to any action of the application or
other plugins, store internal state, expose methods, dispatch new or custom
actions, or throw errors.

Each plugin wraps the next plugin in the chain, allowing plugins to modify
actions before forwarding them down the chain, perform "before" and "after"
business logic, wrap other plugins with try/catch, cancel actions, etc.

This design enables plugin authors to write modular functionality with
versatile, statically-typed interoperability and powerful control flow.

## Example Usage

The simple skeleton for a `Tappable` app looks like this:

```php
<?php

class MyApp extends TappableBase {}

class MyTap extends TapBase {}

class MyAction extends ActionBase {}

$app = new MyApp();
$app->tap(new MyTap());
$app->dispatch(new MyAction());

?>
```
