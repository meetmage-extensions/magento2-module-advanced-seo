<?php
declare(strict_types=1);

namespace MeetMage\AdvancedSeo\Model\Meta;

/**
 * Renders meta templates such as "{{name}} | {{attr:color}} | {{store}}" into clean plain text.
 *
 * Unknown or empty variables render as an empty string, and the separators left behind
 * ("Name |  | Store") are collapsed, so a template works even when some values are missing.
 */
class TemplateRenderer
{
    private const VARIABLE_PATTERN = '/{{\s*([a-z0-9_]+(?::[a-z0-9_]+)?)\s*}}/i';

    /**
     * Characters treated as separators between template parts.
     */
    private const SEPARATORS = '|·•–—\-:,;\/';

    /**
     * Render a template with the given variables.
     *
     * @param string $template
     * @param array<string,string> $variables Keys are lower-case variable names, e.g. "name" or "attr:color"
     * @param int $maxLength Maximum length in characters; 0 = no limit
     * @return string
     */
    public function render(string $template, array $variables, int $maxLength = 0): string
    {
        $text = (string) preg_replace_callback(
            self::VARIABLE_PATTERN,
            static function (array $match) use ($variables): string {
                return $variables[strtolower($match[1])] ?? '';
            },
            $template
        );
        $text = $this->tidySeparators($this->clean($text));

        return $maxLength > 0 ? $this->truncate($text, $maxLength) : $text;
    }

    /**
     * Attribute codes referenced as {{attr:code}} in a template.
     *
     * @param string $template
     * @return string[]
     */
    public function getAttributeCodes(string $template): array
    {
        preg_match_all(self::VARIABLE_PATTERN, $template, $matches);
        $codes = [];
        foreach ($matches[1] as $name) {
            $name = strtolower($name);
            if (str_starts_with($name, 'attr:')) {
                $codes[] = substr($name, 5);
            }
        }

        return array_values(array_unique($codes));
    }

    /**
     * Turn HTML / rich text into a single line of plain text.
     *
     * @param string $text
     * @return string
     */
    public function clean(string $text): string
    {
        // Keep words apart when tags are removed: "<p>One</p><p>Two</p>" -> "One Two"
        $text = (string) preg_replace('/<[^>]*>/', ' ', $text);
        // Plain text for meta tags and JSON-LD; Magento escapes it again when rendering the page.
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string) preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }

    /**
     * Cut text to a maximum length, preferring a word boundary and never ending on a separator.
     *
     * @param string $text
     * @param int $maxLength
     * @return string
     */
    public function truncate(string $text, int $maxLength): string
    {
        if ($maxLength <= 0 || mb_strlen($text) <= $maxLength) {
            return $text;
        }

        $cut = mb_substr($text, 0, $maxLength + 1);
        $lastSpace = mb_strrpos($cut, ' ');
        // Only break at a space when it doesn't throw away too much of the text
        $cut = $lastSpace !== false && $lastSpace >= (int) floor($maxLength * 0.6)
            ? mb_substr($cut, 0, $lastSpace)
            : mb_substr($text, 0, $maxLength);

        return $this->trimSeparators($cut);
    }

    /**
     * Collapse runs of separators left by empty variables and trim them from both ends.
     *
     * @param string $text
     * @return string
     */
    private function tidySeparators(string $text): string
    {
        $separator = '[' . self::SEPARATORS . ']';
        // "A |  | B" -> "A | B", "A - | B" -> "A - B" (only separators surrounded by spaces)
        $text = (string) preg_replace(
            '/\s+(' . $separator . ')(?:\s+' . $separator . ')+(?=\s)/u',
            ' $1',
            $text
        );

        return $this->trimSeparators($text);
    }

    /**
     * Remove separators and whitespace from the start and end of the text.
     *
     * @param string $text
     * @return string
     */
    private function trimSeparators(string $text): string
    {
        $edge = '[\s' . self::SEPARATORS . ']+';

        return (string) preg_replace('/^' . $edge . '|' . $edge . '$/u', '', $text);
    }
}
