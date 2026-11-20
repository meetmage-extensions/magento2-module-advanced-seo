<?php
declare(strict_types=1);

namespace MeetMage\AdvancedSeo\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Typed access to the module's admin configuration (Stores > Configuration > MeetMage > Advanced SEO Suite).
 */
class Config
{
    public const XML_PATH_ENABLED = 'meetmage_advancedseo/general/enabled';

    private const META = 'meetmage_advancedseo/meta/';
    private const STRUCTURED_DATA = 'meetmage_advancedseo/structured_data/';
    private const CANONICAL = 'meetmage_advancedseo/canonical/';

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Whether the module is enabled for the given store (current store when null).
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isEnabled(int|string|null $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Meta title template for products without their own meta title.
     *
     * @return string
     */
    public function getProductTitleTemplate(): string
    {
        return $this->getValue(self::META . 'product_title');
    }

    /**
     * Meta description template for products without their own meta description.
     *
     * @return string
     */
    public function getProductDescriptionTemplate(): string
    {
        return $this->getValue(self::META . 'product_description');
    }

    /**
     * Meta title template for categories without their own meta title.
     *
     * @return string
     */
    public function getCategoryTitleTemplate(): string
    {
        return $this->getValue(self::META . 'category_title');
    }

    /**
     * Meta description template for categories without their own meta description.
     *
     * @return string
     */
    public function getCategoryDescriptionTemplate(): string
    {
        return $this->getValue(self::META . 'category_description');
    }

    /**
     * Maximum length of a generated meta title (0 = no limit).
     *
     * @return int
     */
    public function getTitleMaxLength(): int
    {
        return max(0, (int) $this->getValue(self::META . 'title_max_length'));
    }

    /**
     * Maximum length of a generated meta description (0 = no limit).
     *
     * @return int
     */
    public function getDescriptionMaxLength(): int
    {
        return max(0, (int) $this->getValue(self::META . 'description_max_length'));
    }

    /**
     * Whether Organization structured data is output on every page.
     *
     * @return bool
     */
    public function isOrganizationEnabled(): bool
    {
        return $this->isFlag(self::STRUCTURED_DATA . 'organization');
    }

    /**
     * Organization name (falls back to the store name when empty).
     *
     * @return string
     */
    public function getOrganizationName(): string
    {
        return $this->getValue(self::STRUCTURED_DATA . 'organization_name');
    }

    /**
     * Organization logo: absolute URL, or a path relative to the media folder.
     *
     * @return string
     */
    public function getOrganizationLogo(): string
    {
        return $this->getValue(self::STRUCTURED_DATA . 'organization_logo');
    }

    /**
     * Social / profile URLs for "sameAs", one per line; anything that is not an http(s) URL is ignored.
     *
     * @return string[]
     */
    public function getSameAs(): array
    {
        $lines = preg_split('/\R/', $this->getValue(self::STRUCTURED_DATA . 'same_as')) ?: [];
        $urls = array_filter(array_map('trim', $lines), static function (string $url): bool {
            return (bool) preg_match('#^https?://\S+$#i', $url);
        });

        return array_values($urls);
    }

    /**
     * Whether WebSite structured data (with site search) is output on every page.
     *
     * @return bool
     */
    public function isWebsiteEnabled(): bool
    {
        return $this->isFlag(self::STRUCTURED_DATA . 'website');
    }

    /**
     * Whether Product structured data is output on product pages.
     *
     * @return bool
     */
    public function isProductEnabled(): bool
    {
        return $this->isFlag(self::STRUCTURED_DATA . 'product');
    }

    /**
     * Product attribute code used for the schema.org brand.
     *
     * @return string
     */
    public function getBrandAttribute(): string
    {
        return $this->getValue(self::STRUCTURED_DATA . 'brand_attribute');
    }

    /**
     * Product attribute code that holds the GTIN / EAN / UPC.
     *
     * @return string
     */
    public function getGtinAttribute(): string
    {
        return $this->getValue(self::STRUCTURED_DATA . 'gtin_attribute');
    }

    /**
     * Whether BreadcrumbList structured data is output on product and category pages.
     *
     * @return bool
     */
    public function isBreadcrumbsEnabled(): bool
    {
        return $this->isFlag(self::STRUCTURED_DATA . 'breadcrumbs');
    }

    /**
     * Whether CMS pages and the homepage get a canonical URL.
     *
     * @return bool
     */
    public function isCmsCanonicalEnabled(): bool
    {
        return $this->isFlag(self::CANONICAL . 'cms_pages');
    }

    /**
     * Whether simple products that belong to a configurable product point their canonical to the parent.
     *
     * @return bool
     */
    public function isParentCanonicalEnabled(): bool
    {
        return $this->isFlag(self::CANONICAL . 'configurable_parent');
    }

    /**
     * Read a store-scoped value as a trimmed string.
     *
     * @param string $path
     * @return string
     */
    private function getValue(string $path): string
    {
        return trim((string) $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE));
    }

    /**
     * Read a store-scoped Yes/No value.
     *
     * @param string $path
     * @return bool
     */
    private function isFlag(string $path): bool
    {
        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE);
    }
}
