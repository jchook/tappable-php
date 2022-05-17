<?php declare(strict_types=1);

namespace Email\Smtp;


class ReduxClient
{
	private $commands = [];
	private $replies = [];

	public function __construct($stream)
	{
		$this->stream = $stream;
	}

	// public function dispatch(
	// 	StoreInterface $store,
	// 	callable $next,
	// 	ActionInterface $action
	// ): void
	// {
	//
	// }

	// public function reduce(
	// 	StateInterface $state,
	// 	ActionInterface $action
	// ): StateInterface
	// {
	// 	switch ($action->type) {
	// 		case Smtp::COMMAND:
	// 			return $state->with([
	// 				'commands' => array_merge($state->commands, [$action->payload]),
	// 			]);
	// 		case Smtp::REPLY:
	// 			return $state->with([
	// 				'replies' => array_merge($state->commands, [$action->payload]),
	// 			]);
	// 	}
	// }

	public function resolve(array $action): void
	{
		switch ($action->type) {
			case Smtp::COMMAND:
				$this->commands[] = $action['command'];
				break;
			case Smtp::REPLY:
				$command = array_shift($this->commands);
				$this->store->dispatch([
					'type' => Smtp::COMMAND_REPLY,
					'reply' => $action['reply'],
					'command' => $command,
				]);
				break;
		}
	}
}