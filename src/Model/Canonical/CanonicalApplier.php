<?php
declare(strict_types=1);

namespace MeetMage\AdvancedSeo\Model\Canonical;

use Magento\Catalog\Model\Product;
use Magento\Cms\Helper\Page as CmsPageHelper;
use Magento\Cms\Model\Page as CmsPage;
use Magento\Framework\Registry;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Store\Model\StoreManagerInterface;
use MeetMage\AdvancedSeo\Model\Config;

/**
 * Sets canonical URLs for the homepage, CMS pages and child products of configurables.
 */
class CanonicalApplier
{
    private const CONTENT_TYPE = 'canonical';

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var PageConfig
     */
    private PageConfig $pageConfig;

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @var CmsPage
     */
    private CmsPage $cmsPage;

    /**
     * @var CmsPageHelper
     */
    private CmsPageHelper $cmsPageHelper;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var ParentProductResolver
     */
    private ParentProductResolver $parentProductResolver;

    /**
     * @param Config $config
     * @param PageConfig $pageConfig
     * @param Registry $registry
     * @param CmsPage $cmsPage Shared instance that Magento loads the current CMS page into
     * @param CmsPageHelper $cmsPageHelper
     * @param StoreManagerInterface $storeManager
     * @param ParentProductResolver $parentProductResolver
     */
    public function __construct(
        Config $config,
        PageConfig $pageConfig,
        Registry $registry,
        CmsPage $cmsPage,
        CmsPageHelper $cmsPageHelper,
        StoreManagerInterface $storeManager,
        ParentProductResolver $parentProductResolver
    ) {
        $this->config = $config;
        $this->pageConfig = $pageConfig;
        $this->registry = $registry;
        $this->cmsPage = $cmsPage;
        $this->cmsPageHelper = $cmsPageHelper;
        $this->storeManager = $storeManager;
        $this->parentProductResolver = $parentProductResolver;
    }

    /**
     * Apply the canonical URL for the current page.
     *
     * @param string $fullActionName
     * @return void
     */
    public function apply(string $fullActionName): void
    {
        $url = null;
        if ($fullActionName === 'cms_index_index' && $this->config->isCmsCanonicalEnabled()) {
            $url = (string) $this->storeManager->getStore()->getBaseUrl();
        } elseif ($fullActionName === 'cms_page_view' && $this->config->isCmsCanonicalEnabled()) {
            $pageId = (int) $this->cmsPage->getId();
            $url = $pageId ? $this->cmsPageHelper->getPageUrl((string) $pageId) : null;
        } elseif ($fullActionName === 'catalog_product_view' && $this->config->isParentCanonicalEnabled()) {
            $product = $this->registry->registry('current_product');
            $url = $product instanceof Product ? $this->parentProductResolver->getParentUrl($product) : null;
        }

        if ($url) {
            $this->setCanonical((string) $url);
        }
    }

    /**
     * Replace any canonical link already on the page with the given URL.
     *
     * @param string $url
     * @return void
     */
    public function setCanonical(string $url): void
    {
        $assets = $this->pageConfig->getAssetCollection();
        foreach ($assets->getAll() as $identifier => $asset) {
            if ($asset->getContentType() === self::CONTENT_TYPE) {
                $assets->remove((string) $identifier);
            }
        }
        $this->pageConfig->addRemotePageAsset($url, self::CONTENT_TYPE, ['attributes' => ['rel' => 'canonical']]);
    }
}
