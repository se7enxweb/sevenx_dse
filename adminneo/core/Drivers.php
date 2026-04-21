<?php

namespace AdminNeo;

class Drivers
{
	/** @var string[] */
	private static $drivers = [];

	/** @var string[][] */
	private static $extensions = [];

	/**
	 * Adds a driver.
	 */
	public static function add(string $id, string $name, array $extensions): void
	{
		self::$drivers[$id] = $name;
		self::$extensions[$id] = $extensions;
	}

	public static function setName(string $id, string $name): void
	{
		if (isset(self::$drivers[$id])) {
			self::$drivers[$id] = $name;
		}
	}

	/**
	 * Returns driver name.
	 */
	public static function get(string $id): ?string
	{
		return self::$drivers[$id] ?? null;
	}

	/**
	 * Returns the list of available drivers.
	 *
	 * @return string[]
	 */
	public static function getList(): array
	{
		return self::$drivers;
	}

	/**
	 * Returns the list of supported PHP extensions for given driver.
	 *
	 * @return string[]
	 */
	public static function getExtensions(string $id): array
	{
		return self::$extensions[$id] ?? [];
	}
}

/**
 * @deprecated
 */
function get_drivers(): array
{
	return Drivers::getList();
}
