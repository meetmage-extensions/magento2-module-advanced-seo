<?php
declare(strict_types=1);

namespace MeetMage\AdvancedSeo\Model;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Category to use for a product page.
 *
 * Magento only knows the "current" category when the visitor came from a category page.
 * Search engines open product URLs directly, so without a fallback the breadcrumb trail
 * and the {{category}} variable would be empty for them. The fallback is the product's
 * deepest active category under the current store's root category.
 */
class ProductCategoryResolver
{
    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var array<int,Category|null>
     */
    private array $resolved = [];

    /**
     * @param Registry $registry
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Registry $registry,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->registry = $registry;
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * The current category, or the product's deepest category in this store.
     *
     * @param Product $product
     * @return Category|null
     */
    public function getCategory(Product $product): ?Category
    {
        $current = $this->registry->registry('current_category');
        if ($current instanceof Category) {
            return $current;
        }

        $productId = (int) $product->getId();
        if (!array_key_exists($productId, $this->resolved)) {
            $this->resolved[$productId] = $this->findDeepest($product);
        }

        return $this->resolved[$productId];
    }

    /**
     * Deepest active category of the product under the current store's root category.
     *
     * @param Product $product
     * @return Category|null
     */
    private function findDeepest(Product $product): ?Category
    {
        $categoryIds = array_filter(array_map('intval', (array) $product->getCategoryIds()));
        if (!$categoryIds) {
            return null;
        }

        $store = $this->storeManager->getStore();
        $rootCategoryId = (int) $this->storeManager->getGroup((string) $store->getStoreGroupId())->getRootCategoryId();
        $collection = $this->collectionFactory->create();
        $collection->setStoreId((int) $store->getId())
            ->addAttributeToSelect(['name', 'url_key', 'url_path'])
            ->addIdFilter($categoryIds)
            ->addIsActiveFilter()
            ->addPathFilter('^1/' . $rootCategoryId . '/')
            ->setOrder('level', 'DESC')
            ->setPageSize(1);

        $category = $collection->getFirstItem();

        return $category instanceof Category && $category->getId() ? $category : null;
    }
}
