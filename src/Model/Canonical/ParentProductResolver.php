<?php
declare(strict_types=1);

namespace MeetMage\AdvancedSeo\Model\Canonical;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResource;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Finds the URL of the configurable product a simple product belongs to.
 */
class ParentProductResolver
{
    /**
     * @var ConfigurableResource
     */
    private ConfigurableResource $configurableResource;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param ConfigurableResource $configurableResource
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ConfigurableResource $configurableResource,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager
    ) {
        $this->configurableResource = $configurableResource;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
    }

    /**
     * URL of the first enabled, visible configurable parent in this website, or null.
     *
     * @param Product $product
     * @return string|null
     */
    public function getParentUrl(Product $product): ?string
    {
        if (!in_array($product->getTypeId(), ['simple', 'virtual'], true)) {
            return null;
        }

        $store = $this->storeManager->getStore();
        $storeId = (int) $store->getId();
        $websiteId = (int) $store->getWebsiteId();
        foreach ($this->configurableResource->getParentIdsByChild((int) $product->getId()) as $parentId) {
            try {
                $parent = $this->productRepository->getById((int) $parentId, false, $storeId);
            } catch (NoSuchEntityException $e) {
                continue;
            }
            if ($parent instanceof Product
                && (int) $parent->getStatus() === Status::STATUS_ENABLED
                && $parent->isVisibleInSiteVisibility()
                && in_array($websiteId, array_map('intval', (array) $parent->getWebsiteIds()), true)
            ) {
                return (string) $parent->getProductUrl();
            }
        }

        return null;
    }
}
