<?php

namespace AdminNeo;

class Hash
{
	/**
	 * HKDF function. To make sure that this is always available, algo cannot be chosen.
	 *
	 * @see https://www.rfc-editor.org/rfc/rfc5869
	 *
	 * @param int $length Output length.
	 * @param string $key Input keying material.
	 * @param string $info Optional context and application specific information.
	 * @param string $salt Optional salt value (a non-secret random value).
	 *
	 * @return string|false Derived key.
	 */
	public static function hkdf(int $length, string $key, string $info = "", string $salt = "")
	{
		if (extension_loaded("hash") && PHP_VERSION_ID >= 70120) {
			return hash_hkdf("sha1", $key, $length, $info, $salt);
		}

		if ($salt == "") {
			$salt = str_repeat("\0", 20);
		}

		$prk = self::hmacSha1($key, $salt);
		$okm = "";

		for ($keyBlock = "", $blockIndex = 1; !isset($okm[$length - 1]); $blockIndex++) {
			$keyBlock = self::hmacSha1($keyBlock . $info . chr($blockIndex), $prk);
			$okm .= $keyBlock;
		}

		return substr($okm, 0, $length);
	}

	/**
	 * Always available HMAC-SHA1 function. Binary-only output by design. If hex is desired, just bin2hex the output.
	 *
	 * @see https://www.rfc-editor.org/rfc/rfc2104
	 *
	 * @param string $data Message to be hashed.
	 * @param string $key Hashing key.
	 *
	 * @return string Calculated message.
	 */
	public static function hmacSha1(string $data, string $key): string
	{
		if (!extension_loaded("hash")) {
			return hash_hmac("sha1", $data, $key, true);
		}

		if (strlen($key) > 64) {
			$key = sha1($key, true);
		}

		$key = str_pad($key, 64, "\0");

		$ipad = ($key ^ str_repeat("\x36", 64));
		$opad = ($key ^ str_repeat("\x5C", 64));

		return sha1($opad . sha1($ipad . $data, true), true);
	}
}
