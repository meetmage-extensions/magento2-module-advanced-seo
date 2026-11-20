<?php
declare(strict_types=1);

namespace MeetMage\AdvancedSeo\Test\Unit\Model\Canonical;

use Magento\Cms\Helper\Page as CmsPageHelper;
use Magento\Cms\Model\Page as CmsPage;
use Magento\Framework\Registry;
use Magento\Framework\View\Asset\AssetInterface;
use Magento\Framework\View\Asset\GroupedCollection;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use MeetMage\AdvancedSeo\Model\Canonical\CanonicalApplier;
use MeetMage\AdvancedSeo\Model\Canonical\ParentProductResolver;
use MeetMage\AdvancedSeo\Model\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CanonicalApplierTest extends TestCase
{
    private Config&MockObject $config;

    private PageConfig&MockObject $pageConfig;

    private GroupedCollection&MockObject $assets;

    private CmsPage&MockObject $cmsPage;

    private CmsPageHelper&MockObject $cmsPageHelper;

    private CanonicalApplier $applier;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->pageConfig = $this->createMock(PageConfig::class);
        $this->assets = $this->createMock(GroupedCollection::class);
        $this->pageConfig->method('getAssetCollection')->willReturn($this->assets);
        $this->cmsPage = $this->createMock(CmsPage::class);
        $this->cmsPageHelper = $this->createMock(CmsPageHelper::class);

        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')->willReturn('https://shop.test/');
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $this->applier = new CanonicalApplier(
            $this->config,
            $this->pageConfig,
            $this->createMock(Registry::class),
            $this->cmsPage,
            $this->cmsPageHelper,
            $storeManager,
            $this->createMock(ParentProductResolver::class)
        );
    }

    public function testHomepageGetsBaseUrlAndOldCanonicalIsReplaced(): void
    {
        $this->config->method('isCmsCanonicalEnabled')->willReturn(true);
        $canonical = $this->createMock(AssetInterface::class);
        $canonical->method('getContentType')->willReturn('canonical');
        $stylesheet = $this->createMock(AssetInterface::class);
        $stylesheet->method('getContentType')->willReturn('css');
        $this->assets->method('getAll')->willReturn([
            'https://shop.test/?utm_source=x' => $canonical,
            'css/styles.css' => $stylesheet,
        ]);

        $this->assets->expects($this->once())->method('remove')->with('https://shop.test/?utm_source=x');
        $this->pageConfig->expects($this->once())
            ->method('addRemotePageAsset')
            ->with('https://shop.test/', 'canonical', ['attributes' => ['rel' => 'canonical']]);

        $this->applier->apply('cms_index_index');
    }

    public function testCmsPageUsesItsOwnUrl(): void
    {
        $this->config->method('isCmsCanonicalEnabled')->willReturn(true);
        $this->cmsPage->method('getId')->willReturn(7);
        $this->cmsPageHelper->method('getPageUrl')->with('7')->willReturn('https://shop.test/about-us');
        $this->assets->method('getAll')->willReturn([]);

        $this->pageConfig->expects($this->once())
            ->method('addRemotePageAsset')
            ->with('https://shop.test/about-us', 'canonical', $this->anything());

        $this->applier->apply('cms_page_view');
    }

    public function testNothingChangesWhenDisabled(): void
    {
        $this->config->method('isCmsCanonicalEnabled')->willReturn(false);
        $this->pageConfig->expects($this->never())->method('addRemotePageAsset');

        $this->applier->apply('cms_index_index');
    }
}
