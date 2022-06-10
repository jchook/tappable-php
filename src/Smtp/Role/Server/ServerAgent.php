<?php

namespace Tap\Smtp\Role\Server;

use Generator;
use InvalidArgumentException;
use Tap\Smtp\Role\Server\Exception\ServerException;
use Tap\Smtp\Role\Server\Middleware\ServerBehavior;
use Tap\Tappable;
use Tap\TappableBase;

/**
 *
 * Experimental SMTP Server using Redux pattern.
 *
 */

class ServerAgent
{
	/**
	 * @var resource[]
	 */
	protected array $sockets = [];

	/**
	 * @var Tappable[]
	 */
	protected array $servers = [];
	private int $nextId = 0;

	/**
	 * @param callable():Tappable $serverFactory
	 */
	public function __construct(
		private callable $serverFactory = 'self::defaultServerFactory',
		protected int $tvSec = 1,
		protected int $tvUsec = 100000,
	)
	{
	}

	public static function defaultServerFactory(): Tappable
	{
		return new TappableBase(new ServerBehavior());
	}

	/**
	 * Bind to the given url, e.g. tcp://127.0.0.1:8000
	 * @throws InvalidArgumentException
	 * @throws ServerException
	 */
	public function bind($url): void
	{
		// Check URL
		$parts = parse_url($url);
		if (empty($parts['port']) || empty($parts['host'])) {
			throw new InvalidArgumentException(
				'Please bind to a URL including host and port, e.g. 127.0.0.1:8000'
			);
		}

		// Listen
		$listener = stream_socket_server($url, $errno, $errstr);
		if (!$listener) {
			throw new ServerException($errstr, $errno);
		}

		// Bind to this socket
		$this->bindListener($listener);
	}

	/**
	 * Bind to the given socket...
	 */
	public function bindListener($socket): void
	{
		$this->close();
		$this->listener = $socket;
		$this->sockets[$this->nextId++] = $socket;
	}

	/**
	 * Close all connections and reset everything
	 */
	public function close(): void
	{
		foreach ($this->sockets as $socket) {
			if (is_resource($socket)) {
				fclose($socket);
			}
		}
		unset($this->sockets);
		unset($this->servers);
		$this->sockets = [];
		$this->servers = [];
		$this->nextId = 0;
	}

	public function getSockets()
	{
		return $this->sockets;
	}

	public function createServer(): Tappable
	{
		return ($this->serverFactory)();
	}

	/**
	 * Receive mail
	 */
	public function receiveMail(): Generator
	{
		// Local instance vars
		$listener = &$this->listener;
		$sockets = &$this->sockets;
		$stores = &$this->stores;
		$nextId = &$this->nextId;
		$createStore = $this->createStore;
		$tvSec = $this->tvSec;
		$tvUsec = $this->tvUsec;

		// Watch
		do {

			// Get sockets with changes
			$read = $sockets;
			$write = $except = null;
			$count = stream_select($read, $write, $except, $tvSec, $tvUsec);
			if ($count === FALSE) {
				throw new ServerException(
					error_get_last()['message']
						?? 'An unknown stream_select error has occurred'
				);
			}

			// Idle
			if (count($read) === 0) {
				yield;
			}

			// Per-socket
			foreach ($read as $id => $socket) {

				// New connection?
				if ($socket === $listener) {
					$id = $nextId++; // Yes the ++ goes after. I want current val.

					// I think this blocks even with non-blocking mode
					// TODO: Need to limit the number of servers and pass this by when
					// it's "full".
					$sockets[$id] = stream_socket_accept($listener, 0, $peername);
					stream_set_blocking($sockets[$id], false);
					$stores[$id] = ($createStore)();
					$stores[$id]->dispatch(
						Smtp::connected($sockets[$id], $peername, $hostname)
					);
					continue;
				}

				// Readable socket
				// $stores[$id]->dispatch(Smtp::readable($socket));
				$hasInput = false;
				while (is_resource($socket) && ($input = fread($socket, 4096))) {
					$hasInput = true;
					$stores[$id]->dispatch(Smtp::input($input));
				}
				if ($hasInput) {
					$stores[$id]->dispatch(Smtp::inputDone());
				}

				// Any mail?
				$mail = $stores[$id]->getState()->mail ?? null;
				if ($mail) {
					$stores[$id]->dispatch(new Action(self::YIELD_MAIL));
					yield $mail;

					// Close mail
					if (is_resource($mail->getData())) {
						fclose($mail->getData());
					}
				}

				// Check for disconnections
				if (!is_resource($socket) || feof($socket)) {
					$stores[$id]->dispatch(Smtp::disconnected());
					if (is_resource($socket)) fclose($socket);
					unset($sockets[$id]);
					unset($stores[$id]);
					continue;
				}
			}

		} while (true);
	}
}

