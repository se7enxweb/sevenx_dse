<?php

namespace AdminNeo;

use Exception;

class Random
{
	/**
	 * Returns a random string with 256 bits of entropy.
	 *
	 * The result is safe to use in URL parameters and file names.
	 *
	 * @throws Exception
	 */
	public static function strongKey(): string
	{
		return strtr(rtrim(base64_encode(Random::bytes(32)), "="), "+/", "-_");
	}

	/**
	 * Returns requested amount of random bytes.
	 *
	 * @return string A binary string.
	 *
	 * @throws Exception
	 */
	public static function bytes(int $length): string
	{
		if (PHP_VERSION_ID >= 70000) {
			// There is an astronomically low chance of this throwing an exception. If it happens, the exception is
			// purposefully not caught, because it means that there is something very wrong in the system, and it cannot
			// be trusted to do TLS securely either.
			return random_bytes($length);
		}

		$result = self::tryAlternatives($length);
		if ($result !== false) {
			return $result;
		}

		$result = self::lastResortRandom($length);
		if ($result !== false) {
			return $result;
		}

		throw new Exception("Error generating random bytes");
	}

	/**
	 * @return string|false
	 */
	private static function tryAlternatives(int $length)
	{
		if (extension_loaded("libsodium")) {
			return \Sodium\randombytes_buf($length);
		}

		$unix = DIRECTORY_SEPARATOR === "/";

		if ($unix) {
			$result = self::readDevUrandom($length);
			if ($result !== false) {
				return $result;
			}
		}

		// https://bugs.php.net/bug.php?id=69833
		$bug69833 = $unix && PHP_VERSION_ID > 50609 && PHP_VERSION_ID < 50613;

		if (extension_loaded("mcrypt") && !$bug69833) {
			// MCRYPT_DEV_URANDOM means "something secure" provided by the os.
			// It's something completely else on Windows.
			$result = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
			if ($result !== false) {
				return $result;
			}
		}

		/* TODO Comment out after testing.
		if (!$unix && extension_loaded('com_dotnet') && class_exists('COM'))
		{
			return self::readCapicom($length);
		}
		*/

		// https://bugs.php.net/bug.php?id=70014
		$bug70014 = PHP_VERSION_ID < 50444 || (PHP_VERSION_ID > 50500 && PHP_VERSION_ID < 50528) || (PHP_VERSION_ID > 50600 && PHP_VERSION_ID < 50612);

		if (extension_loaded("openssl") && !$bug70014) {
			$result = openssl_random_pseudo_bytes($length, $strong);
			if ($strong) {
				return $result;
			}
		}

		// No reliable source of randomness was found.
		return false;
	}

	/**
	 * @return string|false
	 */
	private static function readDevUrandom(int $length)
	{
		static $file = null;

		if ($file === null) {
			$file = @fopen("/dev/urandom", "rb");
		}
		if (!$file) {
			return false;
		}

		$remaining = $length;
		$result = "";

		do {
			$data = fread($file, $remaining);
			if ($data === false) {
				return false;
			}

			$remaining -= strlen($data);
			$result .= $data;
		} while ($remaining > 0);

		return $result;
	}

	/**
	 * @return string|false
	 */
	private static function readCapicom(int $length)
	{
		$com = new \COM("CAPICOM.Utilities.1");

		$remaining = $length;
		$result = "";

		do {
			$data = base64_decode((string)$com->GetRandom($length, 0));
			$remaining -= strlen($data);
			$result .= $data;
		} while ($remaining > 0);

		return $result;
	}

	/**
	 * @return string|false
	 */
	private static function lastResortRandom(int $length)
	{
		static $key = null;
		static $salt = null;

		if ($key === null) {
			$data = $_SERVER;
			$data[] = uniqid("", true);
			shuffle($data);

			$key = sha1(serialize($data), true);

			// See referenced bug 70014 above.
			if (extension_loaded("openssl")) {
				$salt = openssl_random_pseudo_bytes(20);
			} else {
				$salt = "";
				for ($i = 0; $i < 20; $i++) {
					$salt .= chr((mt_rand() ^ mt_rand()) % 256);
				}
			}
		} else {
			if ((ord($key) % 2 === 0) === (ord($salt) % 2 === 0)) {
				$key = Hash::hmacSha1($key, $salt);
			} else {
				$salt = Hash::hmacSha1($salt, $key);
			}
		}

		return Hash::hkdf($length, $key, "$length", $salt);
	}
}
