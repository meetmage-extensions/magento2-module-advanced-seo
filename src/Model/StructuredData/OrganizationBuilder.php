<?php
declare(strict_types=1);

namespace MeetMage\AdvancedSeo\Model\StructuredData;

use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use MeetMage\AdvancedSeo\Model\Config;

/**
 * Builds the schema.org Organization and WebSite nodes shown on every page.
 */
class OrganizationBuilder
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(Config $config, StoreManagerInterface $storeManager)
    {
        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    /**
     * Organization node, referenced by the WebSite node as publisher.
     *
     * @return array<string,mixed>
     */
    public function buildOrganization(): array
    {
        $baseUrl = $this->getBaseUrl();
        $node = [
            '@type' => 'Organization',
            '@id' => $baseUrl . '#organization',
            'name' => $this->getOrganizationName(),
            'url' => $baseUrl,
        ];

        $logo = $this->getLogoUrl();
        if ($logo !== '') {
            $node['logo'] = $logo;
        }
        $sameAs = $this->config->getSameAs();
        if ($sameAs !== []) {
            $node['sameAs'] = $sameAs;
        }

        return $node;
    }

    /**
     * WebSite node with the store search as SearchAction.
     *
     * @param bool $withPublisher Link to the Organization node (only when it is output too)
     * @return array<string,mixed>
     */
    public function buildWebSite(bool $withPublisher): array
    {
        $baseUrl = $this->getBaseUrl();
        $node = [
            '@type' => 'WebSite',
            '@id' => $baseUrl . '#website',
            'name' => $this->getOrganizationName(),
            'url' => $baseUrl,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $baseUrl . 'catalogsearch/result/?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
        if ($withPublisher) {
            $node['publisher'] = ['@id' => $baseUrl . '#organization'];
        }

        return $node;
    }

    /**
     * Configured organization name, or the store name.
     *
     * @return string
     */
    private function getOrganizationName(): string
    {
        $name = $this->config->getOrganizationName();
        if ($name !== '') {
            return $name;
        }
        $store = $this->getStore();
        $frontendName = (string) $store->getFrontendName();

        return $frontendName !== '' ? $frontendName : (string) $store->getName();
    }

    /**
     * Absolute logo URL from the configured URL or media path.
     *
     * @return string
     */
    private function getLogoUrl(): string
    {
        $logo = $this->config->getOrganizationLogo();
        if ($logo === '' || preg_match('#^https?://#i', $logo)) {
            return $logo;
        }

        return $this->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . ltrim($logo, '/');
    }

    /**
     * Store base URL with a trailing slash.
     *
     * @return string
     */
    private function getBaseUrl(): string
    {
        return rtrim((string) $this->getStore()->getBaseUrl(UrlInterface::URL_TYPE_LINK), '/') . '/';
    }

    /**
     * Current store.
     *
     * @return Store
     */
    private function getStore(): Store
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore();

        return $store;
    }
}
