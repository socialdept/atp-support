<?php

namespace SocialDept\AtpSupport\Tests\Unit\Crypto;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SocialDept\AtpSupport\Crypto\Base58;
use SocialDept\AtpSupport\Crypto\DidKey;

class DidKeyTest extends TestCase
{
    /** A real secp256k1 key, from an account on Bluesky's spaces alpha PDS. */
    protected const SECP256K1 = 'zQ3shRsCQBcwtEg6XySQtTqztts9qpT28JsQNQQrqSHspMT7r';

    /** From the did:key spec's P-256 test vectors. */
    protected const P256 = 'zDnaerDaTF5BXEavCrfRZEk316dpbLsfPDZ3WJ5hRTPFU2169';

    public function test_it_parses_a_secp256k1_key(): void
    {
        $key = DidKey::parse('did:key:'.self::SECP256K1);

        $this->assertSame(DidKey::SECP256K1, $key->curve);
        $this->assertSame('ES256K', $key->jwtAlgorithm());
        $this->assertSame('secp256k1', $key->curveName());
        $this->assertSame(33, strlen($key->publicKey));
    }

    public function test_it_parses_a_p256_key(): void
    {
        $key = DidKey::parse('did:key:'.self::P256);

        $this->assertSame(DidKey::P256, $key->curve);
        $this->assertSame('ES256', $key->jwtAlgorithm());
        $this->assertSame('secp256r1', $key->curveName());
        $this->assertSame(33, strlen($key->publicKey));
    }

    /**
     * DID documents publish the bare multibase; did:key adds a prefix. Both
     * describe the same key, so both must parse.
     */
    public function test_it_accepts_the_bare_multibase_form(): void
    {
        $this->assertEquals(
            DidKey::parse('did:key:'.self::SECP256K1),
            DidKey::parse(self::SECP256K1),
        );
    }

    public function test_it_round_trips(): void
    {
        $key = DidKey::parse('did:key:'.self::SECP256K1);

        $this->assertSame(self::SECP256K1, $key->toMultibase());
        $this->assertSame('did:key:'.self::SECP256K1, (string) $key);
    }

    /**
     * An unrecognised curve must fail loudly. Treating it as unverifiable-but-ok
     * would turn a failed check into a skipped one.
     */
    public function test_it_rejects_an_unsupported_key_type(): void
    {
        // ed25519-pub (0xed 0x01) — valid multicodec, not used for atproto signing.
        $encoded = 'z'.Base58::encode("\xed\x01".str_repeat("\x01", 32));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported key type');

        DidKey::parse($encoded);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function malformedProvider(): array
    {
        return [
            'not multibase' => ['did:key:QQQ'],
            'empty' => [''],
            'too short' => ['z1'],
            'bad base58 character' => ['did:key:z0OIl'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('malformedProvider')]
    public function test_it_rejects_malformed_keys(string $value): void
    {
        $this->assertNull(DidKey::tryParse($value));
        $this->assertFalse(DidKey::isSupported($value));
    }

    public function test_base58_round_trips_including_leading_zeros(): void
    {
        foreach ([random_bytes(33), "\x00\x00".random_bytes(10), "\x00", 'hello'] as $bytes) {
            $this->assertSame($bytes, Base58::decode(Base58::encode($bytes)));
        }
    }

    public function test_base58_matches_a_known_vector(): void
    {
        // The Bitcoin base58 alphabet has no 0, O, I or l.
        $this->assertSame('2NEpo7TZRRrLZSi2U', Base58::encode('Hello World!'));
        $this->assertSame('Hello World!', Base58::decode('2NEpo7TZRRrLZSi2U'));
    }
}
