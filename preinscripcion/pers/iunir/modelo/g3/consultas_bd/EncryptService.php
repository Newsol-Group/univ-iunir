<?php
namespace iunir\modelo\g3\consultas_bd;

class EncryptService
{
	public static function EncryptString($plainText, $phrase)
	{
		if (strlen($phrase) < 32) {
			while (strlen($phrase) < 32) {
				$phrase .= $phrase;
			}
			$phrase = substr($phrase, 0, 32);
		}
		if (strlen($phrase) > 32) {
			$phrase = substr($phrase, 0, 32);
		}
		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
		$string = openssl_encrypt($plainText, "aes-256-cbc", $phrase, OPENSSL_RAW_DATA, $iv);
		return base64_encode($iv . $string);
	}

	public static function DecryptString($plainText, $phrase)
	{
		if (strlen($phrase) < 32) {
			while (strlen($phrase) < 32) {
				$phrase .= $phrase;
			}
			$phrase = substr($phrase, 0, 32);
		}
		if (strlen($phrase) > 32) {
			$phrase = substr($phrase, 0, 32);
		}

		$plainText = base64_decode($plainText);
		$encodedData = substr($plainText, openssl_cipher_iv_length('aes-256-cbc'));
		$iv = substr($plainText, 0, openssl_cipher_iv_length('aes-256-cbc'));
		$decrypted = openssl_decrypt($encodedData, "aes-256-cbc", $phrase, OPENSSL_RAW_DATA, $iv);
		return $decrypted;
	}

	public static function Generate($ipAddress, $secretKey, $comercio, $sucursal, $amount)
	{
		if (self::IsNullOrEmptyString($ipAddress)) {
			$ipAddress = self::getRealIpAddr();
		}

		$input = sprintf("%s*%s*%s*%s*%s", $ipAddress, $comercio, $sucursal, $amount, $secretKey);
		$inputArray = utf8_encode($input);
		$hashedArray = unpack('C*', hash("sha256", $inputArray, true));
		$string = null;
		for ($i = 1; $i <= count($hashedArray); $i++) {
			$string .= str_pad(strtolower(dechex($hashedArray[$i])), 2, '0', STR_PAD_LEFT);
		}
		return $string;
	}

	private static function getRealIpAddr()
	{
		if (!empty($_SERVER['HTTP_CLIENT_IP']))
			return $_SERVER['HTTP_CLIENT_IP'];

		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
			return $_SERVER['HTTP_X_FORWARDED_FOR'];

		return $_SERVER['REMOTE_ADDR'];
	}

	private static function IsNullOrEmptyString($str)
	{
		return (!isset($str) || trim($str) === '');
	}
}
