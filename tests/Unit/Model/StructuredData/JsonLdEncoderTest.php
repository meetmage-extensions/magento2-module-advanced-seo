<?php
declare(strict_types=1);

namespace MeetMage\AdvancedSeo\Test\Unit\Model\StructuredData;

use MeetMage\AdvancedSeo\Model\StructuredData\JsonLdEncoder;
use PHPUnit\Framework\TestCase;

class JsonLdEncoderTest extends TestCase
{
    public function testWrapsNodesInGraph(): void
    {
        $json = (new JsonLdEncoder())->encode([['@type' => 'Organization', 'url' => 'https://shop.test/']]);
        $data = json_decode($json, true);

        $this->assertSame('https://schema.org', $data['@context']);
        $this->assertSame('Organization', $data['@graph'][0]['@type']);
        $this->assertStringContainsString('https://shop.test/', $json, 'slashes stay readable');
    }

    public function testValuesCannotCloseTheScriptTag(): void
    {
        $json = (new JsonLdEncoder())->encode([['name' => '</script><script>alert(1)</script>']]);

        $this->assertStringNotContainsString('</script>', $json);
        $this->assertStringNotContainsString('<', $json);
        $this->assertSame(
            '</script><script>alert(1)</script>',
            json_decode($json, true)['@graph'][0]['name'],
            'the value is unchanged once decoded'
        );
    }

    public function testEmptyWhenNothingToOutput(): void
    {
        $this->assertSame('', (new JsonLdEncoder())->encode([]));
        $this->assertSame('', (new JsonLdEncoder())->encode([[]]));
    }
}
