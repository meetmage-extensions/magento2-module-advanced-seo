<?php
declare(strict_types=1);

namespace MeetMage\AdvancedSeo\Model\StructuredData;

/**
 * Encodes schema.org nodes as a JSON-LD document that is safe to print inside a <script> tag.
 */
class JsonLdEncoder
{
    /**
     * Wrap nodes in a single "@graph" document and encode it.
     *
     * "<", ">", "&" and quotes are written as JSON unicode escapes, so values can never close the script tag.
     *
     * @param array<int,array<string,mixed>> $nodes
     * @return string Empty string when there is nothing to output
     */
    public function encode(array $nodes): string
    {
        $nodes = array_values(array_filter($nodes));
        if ($nodes === []) {
            return '';
        }

        $json = json_encode(
            ['@context' => 'https://schema.org', '@graph' => $nodes],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS
            | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
        );

        return $json === false ? '' : $json;
    }
}
