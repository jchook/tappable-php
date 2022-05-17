<?php

namespace Tap;

/**
 * Uses PHP Reflection to automatically map actions to handler methods.
 */
trait ReflectiveTapTrait
{
  use TapTrait;

  protected ?array $actionHandlers = null;

  protected array $skipActionHandlers = [];

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
   * We can detect action handlers dynamically! No need to write big switch
   * cases inside __invoke()!
   */
  private function generateActionHandlers(): void
  {
    if (is_null($this->actionHandlers)) {
      $this->actionHandlers = [];
    }

    // For each of the methods on this class
    $class = new \ReflectionClass($this);
    $methods = $class->getMethods();
    foreach ($methods as $method) {

      // Action handlers receive a single action argument
      $params = $method->getParameters();
      if (!$params || count($params) !== 1) {
        continue;
      }

      // Action handlers must accept one concrete class, not union types, etc
      $type = $params[0]->getType();
      if (!($type instanceof \ReflectionNamedType) || $type->isBuiltin() || $type->allowsNull()) {
        continue;
      }

      // Make sure the class author did not specifically blacklist this method
      if (in_array($method->getName(), $this->skipActionHandlers)) {
        continue;
      }

      // The action type must be a class
      $typeName = $type->getName();
      // if (!class_exists($typeName)) {
      //   continue;
      // }

      // Make sure the class implements Action
      $interfaces = class_implements($typeName);
      if (!in_array(Action::class, $interfaces)) {
        continue;
      }

      // Make sure we didn't already find a handler for it
      if (isset($this->actionHandlers[$typeName])) {
        continue;
      }

      // Finally, use this method as the action handler
      $this->actionHandlers[$typeName] = [$this, $method->getName()];
    }
  }
}


