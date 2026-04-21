<?php

namespace AdminNeo;

use stdClass;

abstract class Result
{
	/** @var int */
	protected $rowsCount;

	public function __construct(int $rowsCount)
	{
		$this->rowsCount = $rowsCount;
	}

	public function getRowsCount(): int
	{
		return $this->rowsCount;
	}

	/**
	 * @return ?array|false
	 */
	public abstract function fetchAssoc();

	/**
	 * @return ?array|false
	 */
	public abstract function fetchRow();

	/**
	 * @return stdClass|false
	 */
	public abstract function fetchField();

	public function seek(int $offset): bool
	{
		// TODO: Do we need to implement seek in each driver?
		return false;
	}
}
