<?php

namespace SocialDept\AtpSupport\Tests\Unit\Crypto;

use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\EC\PrivateKey;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use SocialDept\AtpSupport\Crypto\Base58;
use SocialDept\AtpSupport\Crypto\DidKey;
use SocialDept\AtpSupport\Crypto\SignatureVerifier;

class SignatureVerifierTest extends TestCase
{
    /**
     * Curve orders, for normalizing test signatures to low-S.
     */
    protected const ORDER = [
        'secp256k1' => 'FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141',
        'secp256r1' => 'FFFFFFFF00000000FFFFFFFFFFFFFFFFBCE6FAADA7179E84F3B9CAC2FC632551',
    ];

    protected SignatureVerifier $verifier;

    protected function setUp(): void
    {
        $this->verifier = new SignatureVerifier();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function curveProvider(): array
    {
        return [
            'secp256k1' => ['secp256k1', DidKey::SECP256K1_PREFIX],
            'p256' => ['secp256r1', DidKey::P256_PREFIX],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('curveProvider')]
    public function test_it_verifies_a_signature_it_can_attribute(string $curve, string $prefix): void
    {
        [$didKey, $sign] = $this->keypair($curve, $prefix);
        $data = 'the exact bytes that were signed';

        $this->assertTrue($this->verifier->verify($didKey, $data, $sign($data)));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('curveProvider')]
    public function test_it_rejects_a_signature_over_different_data(string $curve, string $prefix): void
    {
        [$didKey, $sign] = $this->keypair($curve, $prefix);

        $this->assertFalse($this->verifier->verify($didKey, 'tampered', $sign('original')));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('curveProvider')]
    public function test_it_rejects_another_keys_signature(string $curve, string $prefix): void
    {
        [$didKey] = $this->keypair($curve, $prefix);
        [, $signOther] = $this->keypair($curve, $prefix);

        $this->assertFalse($this->verifier->verify($didKey, 'data', $signOther('data')));
    }

    /**
     * For every valid (r, s) there is an equally valid (r, n - s). Accepting
     * both would give one signature two byte representations.
     */
    public function test_it_rejects_a_high_s_signature(): void
    {
        [$didKey, $sign] = $this->keypair('secp256k1', DidKey::SECP256K1_PREFIX);
        $data = 'malleability check';
        $signature = $sign($data);

        $this->assertTrue($this->verifier->verify($didKey, $data, $signature));

        $order = new BigInteger('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141', 16);
        $s = new BigInteger(substr($signature, 32, 32), 256);
        $flipped = str_pad($order->subtract($s)->toBytes(), 32, "\0", STR_PAD_LEFT);
        $malleable = substr($signature, 0, 32).$flipped;

        $this->assertNotSame($signature, $malleable);
        $this->assertFalse($this->verifier->verify($didKey, $data, $malleable));

        // Still mathematically valid — only the protocol rule rejects it.
        $this->assertTrue($this->verifier->verify($didKey, $data, $malleable, allowMalleable: true));
    }

    /**
     * Only 64 raw bytes of r || s. A DER signature must not be re-encoded and
     * accepted, or one signature would have two representations again.
     */
    public function test_it_rejects_a_der_encoded_signature(): void
    {
        $key = EC::createKey('secp256k1');
        $didKey = $this->didKeyFor($key, DidKey::SECP256K1_PREFIX);

        $der = $key->withSignatureFormat('ASN1')->withHash('sha256')->sign('data');

        $this->assertNotSame(SignatureVerifier::SIGNATURE_BYTES, strlen($der));
        $this->assertFalse($this->verifier->verify($didKey, 'data', $der));
    }

    public function test_it_rejects_a_signature_of_the_wrong_length(): void
    {
        [$didKey] = $this->keypair('secp256k1', DidKey::SECP256K1_PREFIX);

        $this->assertFalse($this->verifier->verify($didKey, 'data', str_repeat("\x01", 63)));
        $this->assertFalse($this->verifier->verify($didKey, 'data', ''));
    }

    public function test_it_rejects_a_zero_s_signature(): void
    {
        [$didKey] = $this->keypair('secp256k1', DidKey::SECP256K1_PREFIX);

        $this->assertFalse($this->verifier->verify($didKey, 'data', str_repeat("\x00", 64)));
    }

    public function test_an_unparseable_key_never_verifies(): void
    {
        $this->assertFalse($this->verifier->verify('did:key:nonsense', 'data', str_repeat("\x01", 64)));
    }

    /**
     * A signature made on one curve must not verify against the other.
     */
    public function test_curves_are_not_interchangeable(): void
    {
        [$k1Key] = $this->keypair('secp256k1', DidKey::SECP256K1_PREFIX);
        [, $signP256] = $this->keypair('secp256r1', DidKey::P256_PREFIX);

        $this->assertFalse($this->verifier->verify($k1Key, 'data', $signP256('data')));
    }

    /**
     * A did:key and a signer over the same fresh keypair.
     *
     * @return array{0: string, 1: callable(string): string}
     */
    protected function keypair(string $curve, string $prefix): array
    {
        $key = EC::createKey($curve);

        $order = new BigInteger(self::ORDER[$curve], 16);

        $sign = function (string $data) use ($key, $order): string {
            $signature = $key->withSignatureFormat('Raw')->withHash('sha256')->sign($data);
            $s = $signature['s'];

            // phpseclib does not normalize, but an atproto signer must: roughly
            // half of raw signatures come back high-S and are rejected on the
            // wire. Flipping to n - s yields the canonical form.
            [$half] = $order->divide(new BigInteger(2));

            if ($s->compare($half) > 0) {
                $s = $order->subtract($s);
            }

            return str_pad($signature['r']->toBytes(), 32, "\0", STR_PAD_LEFT)
                .str_pad($s->toBytes(), 32, "\0", STR_PAD_LEFT);
        };

        return [$this->didKeyFor($key, $prefix), $sign];
    }

    protected function didKeyFor(PrivateKey $key, string $prefix): string
    {
        $point = $key->getPublicKey()->getEncodedCoordinates();

        // Compress: 0x02/0x03 by the parity of y, then x.
        $x = substr($point, 1, 32);
        $y = substr($point, 33, 32);
        $compressed = chr(0x02 | (ord($y[31]) & 1)).$x;

        return 'did:key:z'.Base58::encode($prefix.$compressed);
    }
}
