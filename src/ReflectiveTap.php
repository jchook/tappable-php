<?php

namespace Tap;

/**
 * Uses PHP Reflection to automatically map actions to handler methods.
 */
trait ReflectiveTap
{
  use BasicTap;

  protected ?array $actionHandlers = null;

  private array $classInheritanceCache = [];

  public function __invoke(Action $action): void
  {
    if (is_null($this->actionHandlers)) {
      $this->generateActionHandlers();
    }
    $handler = $this->getActionHandler($action);
    if ($handler) {
      $handler($action);
    } else {
      $this->next($action);
    }
  }

  private function getActionHandler(Action $action): callable|null
  {
    // No action handlers?
    if (!$this->actionHandlers) {
      return null;
    }

    // Did we already figure out this action class inherits from another more
    // basic class we want to handle or not handle?
    $class = get_class($action);
    if (isset($this->classInheritanceCache[$class])) {
      $class = $this->classInheritanceCache[$class];
      if ($class === false) {
        return null;
      }
    }

    // Did we define a handler for this class of action?
    if (isset($this->actionHandlers[$class])) {
      return $this->actionHandlers[$class];
    }

    // Hm, maybe this action class extends an action we do want to handle...
    // We may not need to cache this...
    // Checking instanceof for each method is what we would do with a hand-
    // written __invoke function anyhow.
    foreach ($this->actionHandlers as $actionClass => $handler) {
      if ($action instanceof $actionClass) {
        $this->classInheritanceCache[$class] = $actionClass;
        return $handler;
      }
    }

    // No handler
    return null;
  }

  /**
   * We can detection action handlers dynamically! No need to wire them up
   * inside __invoke()!
   */
  private function generateActionHandlers(): void
  {
    if (is_null($this->actionHandlers)) {
      $this->actionHandlers = [];
    }
    $class = new \ReflectionClass($this);
    $methods = $class->getMethods();
    foreach ($methods as $method) {
      $params = $method->getParameters();
      if (!$params) {
        continue;
      }
      $type = $params[0]->getType();
      if (!method_exists($type, 'getName')) {
        continue;
      }
      $name = $type->getName();
      if (!isset($this->actionHandlers[$name])) {
        $this->actionHandlers[$name] = [$this, $method->getName()];
      }
    }
  }
}

