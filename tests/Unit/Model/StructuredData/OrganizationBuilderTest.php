<?php
declare(strict_types=1);

namespace MeetMage\AdvancedSeo\Test\Unit\Model\StructuredData;

use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use MeetMage\AdvancedSeo\Model\Config;
use MeetMage\AdvancedSeo\Model\StructuredData\OrganizationBuilder;
use PHPUnit\Framework\TestCase;

class OrganizationBuilderTest extends TestCase
{
    private Config $config;

    private OrganizationBuilder $builder;

    protected function setUp(): void
    {
        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')->willReturnCallback(static function ($type = UrlInterface::URL_TYPE_LINK) {
            return $type === UrlInterface::URL_TYPE_MEDIA ? 'https://shop.test/media/' : 'https://shop.test';
        });
        $store->method('getFrontendName')->willReturn('Shoe Shop');

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $this->config = $this->createMock(Config::class);
        $this->builder = new OrganizationBuilder($this->config, $storeManager);
    }

    public function testOrganizationFallsBackToStoreNameAndResolvesMediaLogo(): void
    {
        $this->config->method('getOrganizationName')->willReturn('');
        $this->config->method('getOrganizationLogo')->willReturn('/logo/default/logo.png');
        $this->config->method('getSameAs')->willReturn(['https://www.linkedin.com/company/shoe-shop']);

        $node = $this->builder->buildOrganization();

        $this->assertSame('Organization', $node['@type']);
        $this->assertSame('https://shop.test/#organization', $node['@id']);
        $this->assertSame('Shoe Shop', $node['name']);
        $this->assertSame('https://shop.test/', $node['url']);
        $this->assertSame('https://shop.test/media/logo/default/logo.png', $node['logo']);
        $this->assertSame(['https://www.linkedin.com/company/shoe-shop'], $node['sameAs']);
    }

    public function testOrganizationKeepsAbsoluteLogoAndOmitsEmptySameAs(): void
    {
        $this->config->method('getOrganizationName')->willReturn('Shoe Shop Ltd');
        $this->config->method('getOrganizationLogo')->willReturn('https://cdn.test/logo.svg');
        $this->config->method('getSameAs')->willReturn([]);

        $node = $this->builder->buildOrganization();

        $this->assertSame('Shoe Shop Ltd', $node['name']);
        $this->assertSame('https://cdn.test/logo.svg', $node['logo']);
        $this->assertArrayNotHasKey('sameAs', $node);
    }

    public function testWebSiteHasSearchActionAndOptionalPublisher(): void
    {
        $this->config->method('getOrganizationName')->willReturn('');

        $withPublisher = $this->builder->buildWebSite(true);
        $withoutPublisher = $this->builder->buildWebSite(false);

        $this->assertSame(
            'https://shop.test/catalogsearch/result/?q={search_term_string}',
            $withPublisher['potentialAction']['target']['urlTemplate']
        );
        $this->assertSame(['@id' => 'https://shop.test/#organization'], $withPublisher['publisher']);
        $this->assertArrayNotHasKey('publisher', $withoutPublisher);
    }
}
