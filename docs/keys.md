# Keys and signatures

Part of the [atp-support docs](introduction.md#documentation). Parsing `did:key` values, reading a DID document's signing key, and verifying signatures.

## Reading a signing key

A DID document publishes its keys in `verificationMethod`. `getSigningKey()` returns one as a `did:key`, by its fragment. The default is `#atproto`, the repo signing key every account publishes:

```php
use SocialDept\AtpSupport\Facades\Resolver;

$doc = Resolver::resolveDid($did);

$key = $doc->getSigningKey();           // did:key:z..., or null
$key = $doc->getSigningKey('#atproto'); // the same
```

Both the bare `#atproto` and the fully qualified `did:plc:...#atproto` ids match.

## Parsing keys

`DidKey` parses a `did:key:z...` value or the bare `z...` multibase form a DID document carries as `publicKeyMultibase`. Only the two curves AT Protocol uses are supported, secp256k1 and P-256:

```php
use SocialDept\AtpSupport\Crypto\DidKey;

$key = DidKey::parse($value);     // throws InvalidArgumentException
$key = DidKey::tryParse($value);  // or null
DidKey::isSupported($value);      // bool

$key->curve;          // DidKey::SECP256K1 ('secp256k1') or DidKey::P256 ('p256')
$key->publicKey;      // the 33-byte compressed point
$key->jwtAlgorithm(); // 'ES256K' or 'ES256'
$key->toMultibase();  // z...
(string) $key;        // did:key:z...
```

A key with any other multicodec prefix, a multibase other than base58btc, or a point that is not 33 bytes is rejected rather than accepted unchecked.

`Base58::encode()` and `Base58::decode()` in the same namespace convert raw bytes to and from base58btc.

## Verifying signatures

`SignatureVerifier` checks a signature against a published key:

```php
use SocialDept\AtpSupport\Crypto\SignatureVerifier;

$verifier = new SignatureVerifier();

$verifier->verify($didKey, $data, $signature);   // bool
$verifier->verifyWith($key, $data, $signature);  // with a parsed DidKey
```

`$data` is the exact bytes that were signed, and `$signature` is 64 raw bytes of `r || s`. Anything that cannot be verified returns `false`, including a key that does not parse.

The protocol requires two rules a general ECDSA check would not apply, and both are enforced:

- **Compact signatures only.** A DER-encoded signature is rejected, so the same signature cannot be encoded into a second byte string.
- **Low-S only.** For each valid `(r, s)` there is an equally valid `(r, n - s)`. Only the low one is accepted, so a signature has one form.

Pass `allowMalleable: true` to accept high-S signatures. Use it only for historical data known to predate the rule.
