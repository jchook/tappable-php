<?php declare(strict_types=1);

namespace Tap\Smtp\Element;

class Path
{
	/**
	 * Since the @ symbol MUST NOT appear within the domain, we can safely
	 * isolate the domain and local-part of a mailbox, so long as we have the
	 * entire mailbox string properly isolated and otherwise unencoded.
	 *
	 * Whether this is a good idea... I do not know.
	 * Ultimately we should accept the local part and the domain separately, as
	 * that is the only way to truly incidate that we expect both to be decoded.
	 *
	 * The other idea is to force the end user to encode the string, which feels
	 * a bit sadistic, but also perhaps expected from their end.
	 *
	 * Seems like they should have simply banned special chars from local-part,
	 * and enforced dot-atom.
	 *
	 * @var int
	 */
	private $atPos;

	/**
	 * @var ?Mailbox
	 */
	private $mailbox;

	/**
	 * @var string
	 */
	private $type;

	public function __construct(string $type, ?Mailbox $mailbox = null)
	{
		$this->setType($type);
		$this->setMailbox($mailbox);
	}

	public function getMailbox(): ?Mailbox
	{
		return $this->mailbox;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function setMailbox(?Mailbox $mailbox): void
	{
		$this->mailbox = $mailbox;
	}

	public function setType(string $type): void
	{
		$this->type = strtoupper($type);
	}

	public function isForward(): bool
	{
		return $this->type === 'TO';
	}

	public function isReverse(): bool
	{
		return $this->type === 'FROM';
	}
}
