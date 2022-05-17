<?php

namespace Jchook\Tap;

interface TappableInterface {
  public function tap(string $method, PluginInterface $plugin);
}

