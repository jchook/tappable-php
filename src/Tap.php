<?php

namespace Tap;

trait Tap
{
  public function dispatch(string $methodName, array $args = [])
  {
    $taps = array_values($this->taps);
    $idx = 0;
    $next = null;
    $setNext = function() use (&$next, $taps) {
      foreach ($taps as $tap) {
        $tap->setNext($next);
      }
    };
    $next = function() use (&$idx, $taps, $setNext) {
      $idx++;
      $taps[$idx];
      $setNext();
    };
    foreach ($this->taps as $tapName => $tapObj) {
      if (method_exists($tapObj, $methodName)) {
        call_user_func_array([$tapObj, $methodName], $args);
      }
    }
  }

  public function __tap()
  {
    $reflect = new \ReflectionClass($this);
    $methods = $reflect->getMethods();
    foreach ($methods as $method) {
      if ($method->isStatic()) continue;
      $name = $method->getName();
      if (substr($name, 0, 6) === '__tap_') {
        $method->invoke($this);
      }
    }
  }
}
