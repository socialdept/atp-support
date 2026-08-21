<?php

namespace SocialDept\AtpSupport\Crypto;

use InvalidArgumentException;
use phpseclib3\Crypt\EC\PublicKey;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Math\BigInteger;
use Throwable;

/**
 * Verifies AT Protocol signatures against a published key.
 *
 * Two rules are enforced that a general-purpose ECDSA check would not, both
 * required by the protocol:
 *
 * - **Compact signatures only.** 64 raw bytes of `r || s`. A DER-encoded
 *   signature is rejected rather than parsed, because accepting both forms
 *   would let the same signature be re-encoded into a different byte string.
 * - **Low-S only.** For every valid `(r, s)` there is an equally valid
 *   `(r, n - s)`. Accepting both means a signature has two representations,
 *   and anything that treats a signature as an identifier breaks.
 *
 * Together these make a signature's bytes canonical.
 */
class SignatureVerifier
{
    public const SIGNATURE_BYTES = 64;

    /**
     * Curve orders, for the low-S check.
     */
    protected const ORDER = [
        DidKey::SECP256K1 => 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141',
        DidKey::P256 => 'FFFFFFFF00000000FFFFFFFFFFFFFFFFBCE6FAADA7179E84F3B9CAC2FC632551',
    ];

    /**
     * ASN.1 object identifiers for the SubjectPublicKeyInfo wrapper.
     */
    protected const OID_EC_PUBLIC_KEY = "\x06\x07\x2a\x86\x48\xce\x3d\x02\x01";
    protected const OID_SECP256K1 = "\x06\x05\x2b\x81\x04\x00\x0a";
    protected const OID_P256 = "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";

    /**
     * Verify a signature made by the holder of a published key.
     *
     * @param  string  $didKey  A `did:key:…` or bare `z…` multibase key
     * @param  string  $data    The exact bytes that were signed
     * @param  string  $signature  64 raw bytes of r || s
     * @param  bool  $allowMalleable  Accept high-S signatures. Only for reading
     *                                historical data known to predate the rule.
     */
    public function verify(
        string $didKey,
        string $data,
        string $signature,
        bool $allowMalleable = false,
    ): bool {
        $key = DidKey::tryParse($didKey);

        if ($key === null) {
            return false;
        }

        return $this->verifyWith($key, $data, $signature, $allowMalleable);
    }

    /**
     * Verify against an already-parsed key.
     */
    public function verifyWith(
        DidKey $key,
        string $data,
        string $signature,
        bool $allowMalleable = false,
    ): bool {
        if (strlen($signature) !== self::SIGNATURE_BYTES) {
            return false;
        }

        $r = new BigInteger(substr($signature, 0, 32), 256);
        $s = new BigInteger(substr($signature, 32, 32), 256);

        if (! $allowMalleable && ! $this->isLowS($key, $s)) {
            return false;
        }

        try {
            $publicKey = $this->loadPublicKey($key);

            return (bool) $publicKey
                ->withSignatureFormat('Raw')
                ->withHash('sha256')
                ->verify($data, ['r' => $r, 's' => $s]);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Whether `s` is in the lower half of the curve order.
     */
    protected function isLowS(DidKey $key, BigInteger $s): bool
    {
        $order = new BigInteger(self::ORDER[$key->curve], 16);
        [$half] = $order->divide(new BigInteger(2));

        return $s->compare($half) <= 0 && $s->compare(new BigInteger(0)) > 0;
    }

    /**
     * Wrap the compressed point in a DER SubjectPublicKeyInfo phpseclib can load.
     *
     * phpseclib decompresses the point itself, so there is no modular-sqrt work
     * to do here.
     */
    protected function loadPublicKey(DidKey $key): PublicKey
    {
        $curveOid = $key->curve === DidKey::SECP256K1 ? self::OID_SECP256K1 : self::OID_P256;

        $algorithm = self::sequence(self::OID_EC_PUBLIC_KEY.$curveOid);
        $bitString = "\x03".self::length(strlen($key->publicKey) + 1)."\x00".$key->publicKey;

        $der = self::sequence($algorithm.$bitString);

        $pem = "-----BEGIN PUBLIC KEY-----\n"
            .chunk_split(base64_encode($der), 64, "\n")
            ."-----END PUBLIC KEY-----\n";

        $loaded = PublicKeyLoader::load($pem);

        if (! $loaded instanceof PublicKey) {
            throw new InvalidArgumentException('Decoded key is not an EC public key.');
        }

        return $loaded;
    }

    /**
     * Wrap DER contents in a SEQUENCE.
     */
    protected static function sequence(string $contents): string
    {
        return "\x30".self::length(strlen($contents)).$contents;
    }

    /**
     * DER definite-length encoding.
     */
    protected static function length(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }

        $bytes = ltrim(pack('N', $length), "\0");

        return chr(0x80 | strlen($bytes)).$bytes;
    }
}
