<?php

namespace SocialDept\AtpSupport\Crypto;

use InvalidArgumentException;
use Stringable;

/**
 * A public key as AT Protocol publishes it.
 *
 *     did:key:z<base58btc( <multicodec prefix> <compressed point> )>
 *
 * The same encoding appears without the `did:key:` prefix as a DID document's
 * `publicKeyMultibase`, so both forms parse here.
 *
 * Only the two curves atproto uses are recognised. An unknown prefix is an
 * error rather than a shrug: silently accepting a key we cannot check would
 * turn a verification failure into a verification *skip*.
 */
class DidKey implements Stringable
{
    public const PREFIX = 'did:key:';
    public const MULTIBASE_BASE58 = 'z';

    /** secp256k1-pub */
    public const SECP256K1_PREFIX = "\xe7\x01";

    /** p256-pub */
    public const P256_PREFIX = "\x80\x24";

    public const SECP256K1 = 'secp256k1';
    public const P256 = 'p256';

    /**
     * @param  string  $curve  One of the self::SECP256K1 / self::P256 constants
     * @param  string  $publicKey  The compressed EC point, 33 bytes
     */
    public function __construct(
        public readonly string $curve,
        public readonly string $publicKey,
    ) {
    }

    /**
     * Parse either a `did:key:…` or a bare `z…` multibase string.
     *
     * @throws InvalidArgumentException
     */
    public static function parse(string $value): static
    {
        $multibase = str_starts_with($value, self::PREFIX)
            ? substr($value, strlen(self::PREFIX))
            : $value;

        if (! str_starts_with($multibase, self::MULTIBASE_BASE58)) {
            throw new InvalidArgumentException(
                'Only base58btc multibase keys are supported, expected a leading "z".'
            );
        }

        $decoded = Base58::decode(substr($multibase, 1));

        if (strlen($decoded) < 3) {
            throw new InvalidArgumentException('Key is too short to carry a multicodec prefix.');
        }

        $prefix = substr($decoded, 0, 2);
        $publicKey = substr($decoded, 2);

        $curve = match ($prefix) {
            self::SECP256K1_PREFIX => self::SECP256K1,
            self::P256_PREFIX => self::P256,
            default => throw new InvalidArgumentException(
                'Unsupported key type, multicodec prefix 0x'.bin2hex($prefix).'.'
            ),
        };

        if (strlen($publicKey) !== 33) {
            throw new InvalidArgumentException(
                'Expected a 33-byte compressed point, got '.strlen($publicKey).' bytes.'
            );
        }

        return new static($curve, $publicKey);
    }

    /**
     * Parse, returning null instead of throwing.
     */
    public static function tryParse(string $value): ?static
    {
        try {
            return static::parse($value);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Whether a string looks like a supported key, without decoding it fully.
     */
    public static function isSupported(string $value): bool
    {
        return static::tryParse($value) !== null;
    }

    /**
     * The JWT `alg` this curve signs with.
     */
    public function jwtAlgorithm(): string
    {
        return $this->curve === self::SECP256K1 ? 'ES256K' : 'ES256';
    }

    /**
     * The curve name phpseclib knows it by.
     */
    public function curveName(): string
    {
        return $this->curve === self::SECP256K1 ? 'secp256k1' : 'secp256r1';
    }

    /**
     * The multicodec prefix for this curve.
     */
    public function multicodecPrefix(): string
    {
        return $this->curve === self::SECP256K1 ? self::SECP256K1_PREFIX : self::P256_PREFIX;
    }

    /**
     * The `publicKeyMultibase` form, as a DID document carries it.
     */
    public function toMultibase(): string
    {
        return self::MULTIBASE_BASE58.Base58::encode($this->multicodecPrefix().$this->publicKey);
    }

    public function __toString(): string
    {
        return self::PREFIX.$this->toMultibase();
    }
}
