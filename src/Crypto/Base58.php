<?php

namespace SocialDept\AtpSupport\Crypto;

use InvalidArgumentException;
use phpseclib3\Math\BigInteger;

/**
 * Base58 in the Bitcoin alphabet, which is how AT Protocol encodes the key
 * material inside a `did:key` and a DID document's `publicKeyMultibase`.
 *
 * Uses phpseclib's BigInteger rather than GMP so this stays available wherever
 * the rest of the package is.
 */
class Base58
{
    public const ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    /**
     * Decode a base58btc string to raw bytes.
     *
     * @throws InvalidArgumentException
     */
    public static function decode(string $encoded): string
    {
        if ($encoded === '') {
            return '';
        }

        $base = new BigInteger(58);
        $number = new BigInteger(0);

        foreach (str_split($encoded) as $character) {
            $index = strpos(self::ALPHABET, $character);

            if ($index === false) {
                throw new InvalidArgumentException("Invalid base58 character \"{$character}\".");
            }

            $number = $number->multiply($base)->add(new BigInteger($index));
        }

        $bytes = $number->toBytes();

        // A leading "1" encodes a leading zero byte, which the integer loses.
        $leadingZeros = strlen($encoded) - strlen(ltrim($encoded, self::ALPHABET[0]));

        return str_repeat("\0", $leadingZeros).$bytes;
    }

    /**
     * Encode raw bytes as base58btc.
     */
    public static function encode(string $bytes): string
    {
        if ($bytes === '') {
            return '';
        }

        $base = new BigInteger(58);
        $zero = new BigInteger(0);
        $number = new BigInteger($bytes, 256);

        $encoded = '';

        while ($number->compare($zero) > 0) {
            [$number, $remainder] = $number->divide($base);
            $encoded = self::ALPHABET[(int) $remainder->toString()].$encoded;
        }

        $leadingZeros = strlen($bytes) - strlen(ltrim($bytes, "\0"));

        return str_repeat(self::ALPHABET[0], $leadingZeros).$encoded;
    }
}
