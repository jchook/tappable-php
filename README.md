# Tappable: a Plugin Framework for PHP

![License MIT](https://img.shields.io/github/license/jchook/tappable-php?color=brightgreen)
![Test Coverage 100%](https://img.shields.io/badge/test%20coverage-100%25-brightgreen)
![PHP](https://img.shields.io/badge/php-8.1%2B-787cb5)

Tappable helps you create modular PHP apps that accept plugins or "middleware"
similar to redux, express, haraka, etc.

## Concepts

A `Tappable` app has _plugins_.

Plugins communicate via _actions_.

Every action passes through each plugin, sequentially.

Plugins can dispatch new actions, handle actions, keep state, or throw errors.

Each plugin wraps the next plugin. Plugins can wrap next() calls to perform
"before" and "after" business logic, try/catch logic, modify actions, cancel
actions, etc.

This design enables plugin authors to write modular functionality with
versatile, statically-typed interoperability and powerful control flow.

## Example Usage

The simple skeleton for a `Tappable` app looks like this.

```php
<?php

class MyApp extends TappableBase {}

class MyPlugin extends TapBase {}

class MyAction extends ActionBase {}

$app = new MyApp();
$app->tap(new MyPlugin());
$app->dispatch(new MyAction());

?>
```
