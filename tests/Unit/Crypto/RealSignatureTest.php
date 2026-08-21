<?php

namespace SocialDept\AtpSupport\Tests\Unit\Crypto;

use PHPUnit\Framework\TestCase;
use SocialDept\AtpSupport\Crypto\SignatureVerifier;
use SocialDept\AtpSupport\Data\DidDocument;

/**
 * A signature produced by Bluesky's PDS, checked against the key that account
 * publishes. Captured rather than generated, so this stays a fixed regression
 * test even as the account's tokens expire.
 */
class RealSignatureTest extends TestCase
{
    protected function document(): DidDocument
    {
        return DidDocument::fromArray([
            'id' => 'did:plc:3znwyhr2wogfy4nyhy4daii4',
            'alsoKnownAs' => ['at://dad.spaces-alpha.bsky.network'],
            'verificationMethod' => [[
                'id' => 'did:plc:3znwyhr2wogfy4nyhy4daii4#atproto',
                'type' => 'Multikey',
                'controller' => 'did:plc:3znwyhr2wogfy4nyhy4daii4',
                'publicKeyMultibase' => 'zQ3shRsCQBcwtEg6XySQtTqztts9qpT28JsQNQQrqSHspMT7r',
            ]],
            'service' => [[
                'id' => '#atproto_pds',
                'type' => 'AtprotoPersonalDataServer',
                'serviceEndpoint' => 'https://spaces-alpha.host.bsky.network',
            ]],
        ]);
    }

    public function test_a_did_document_yields_its_signing_key(): void
    {
        $this->assertSame(
            'did:key:zQ3shRsCQBcwtEg6XySQtTqztts9qpT28JsQNQQrqSHspMT7r',
            $this->document()->getSigningKey(),
        );
    }

    public function test_an_unknown_fragment_yields_nothing(): void
    {
        $this->assertNull($this->document()->getSigningKey('#atproto_space'));
    }

    /**
     * The real thing: a JWT signed by a live PDS, verified against the key from
     * that account's DID document.
     */
    public function test_it_verifies_a_token_signed_by_a_live_pds(): void
    {
        $fixture = json_decode(
            (string) file_get_contents(__DIR__.'/../../fixtures/live-delegation-token.json'),
            true,
        );

        $didKey = $this->document()->getSigningKey();
        $this->assertNotNull($didKey);

        $verifier = new SignatureVerifier();

        $this->assertTrue(
            $verifier->verify($didKey, $fixture['signing_input'], base64_decode($fixture['signature'])),
            'a real PDS signature must verify against the published key',
        );

        // The same signature over altered bytes must not.
        $this->assertFalse(
            $verifier->verify($didKey, $fixture['signing_input'].' ', base64_decode($fixture['signature'])),
        );
    }
}
