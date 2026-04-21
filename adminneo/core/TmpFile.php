<?php

namespace AdminNeo;

class TmpFile
{
	/** @var resource|false */
	private $handler;

	/** @var int */
	private $size;

	public function __construct()
	{
		$this->handler = tmpfile();
	}

	public function getSize(): int
	{
		return $this->size;
	}

	public function write($contents): void
	{
		if (!$this->handler) {
			return;
		}

		$this->size += strlen($contents);

		fwrite($this->handler, $contents);
	}

	public function send(): void
	{
		if (!$this->handler) {
			return;
		}

		fseek($this->handler, 0);
		fpassthru($this->handler);
		fclose($this->handler);
	}
}
