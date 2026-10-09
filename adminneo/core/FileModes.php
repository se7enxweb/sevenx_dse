<?php

namespace AdminNeo;

/**
 * Gives the files and directories AdminNeo creates (compiled assets, lock files) their modes within the limits the
 * Exponential kernel sets (EZP_FILE_MODE_MAX / EZP_DIR_MODE_MAX in config.php, see eZFile::fileMode() and
 * eZDir::dirMode()): never wider, and without a limit exactly the mode asked for, as before. Where the kernel is
 * not loaded (the asset build script) or has no such helpers (Exponential before 6.0.15) the mode asked for as it is.
 */
class FileModes
{
	/**
	 * chmod() with the mode of a file within EZP_FILE_MODE_MAX.
	 *
	 * @param string $path
	 * @param int $mode
	 * @return bool
	 */
	public static function chmod(string $path, int $mode): bool
	{
		return \chmod($path, self::fileMode($mode));
	}

	/**
	 * mkdir() with the mode of a directory within EZP_DIR_MODE_MAX, under the umask of the process as mkdir() is.
	 *
	 * @param string $dir
	 * @param int $mode
	 * @param bool $recursive
	 * @return bool
	 */
	public static function mkdir(string $dir, int $mode = 0777, bool $recursive = false): bool
	{
		return \mkdir($dir, self::dirMode($mode), $recursive);
	}

	public static function fileMode(int $mode): int
	{
		return method_exists('eZFile', 'fileMode') ? \eZFile::fileMode($mode) : $mode;
	}

	public static function dirMode(int $mode): int
	{
		return method_exists('eZDir', 'dirMode') ? \eZDir::dirMode($mode) : $mode;
	}
}
