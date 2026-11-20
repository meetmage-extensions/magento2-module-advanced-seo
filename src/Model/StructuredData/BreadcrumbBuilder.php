<?php
declare(strict_types=1);

namespace MeetMage\AdvancedSeo\Model\StructuredData;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Builds a schema.org BreadcrumbList for category and product pages: Home > categories > item.
 */
class BreadcrumbBuilder
{
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * Breadcrumbs for a category page.
     *
     * @param Category $category
     * @return array<string,mixed>
     */
    public function forCategory(Category $category): array
    {
        return $this->build(array_merge([$this->home()], $this->categoryTrail($category)));
    }

    /**
     * Breadcrumbs for a product page, through the category the visitor came from (if any).
     *
     * @param Product $product
     * @param Category|null $category
     * @return array<string,mixed>
     */
    public function forProduct(Product $product, ?Category $category): array
    {
        $items = [$this->home()];
        if ($category) {
            $items = array_merge($items, $this->categoryTrail($category));
        }
        $items[] = ['name' => (string) $product->getName(), 'url' => (string) $product->getProductUrl()];

        return $this->build($items);
    }

    /**
     * Build the BreadcrumbList node from ordered name / URL pairs.
     *
     * @param array<int,array{name:string,url:string}> $items
     * @return array<string,mixed>
     */
    public function build(array $items): array
    {
        $elements = [];
        foreach ($items as $item) {
            $name = trim($item['name']);
            if ($name === '' || $item['url'] === '') {
                continue;
            }
            $elements[] = [
                '@type' => 'ListItem',
                'position' => count($elements) + 1,
                'name' => $name,
                'item' => $item['url'],
            ];
        }

        return ['@type' => 'BreadcrumbList', 'itemListElement' => $elements];
    }

    /**
     * The "Home" crumb.
     *
     * @return array{name:string,url:string}
     */
    private function home(): array
    {
        return ['name' => (string) __('Home'), 'url' => (string) $this->storeManager->getStore()->getBaseUrl()];
    }

    /**
     * Parent categories (top level first) followed by the category itself, without the root.
     *
     * @param Category $category
     * @return array<int,array{name:string,url:string}>
     */
    private function categoryTrail(Category $category): array
    {
        $categories = array_filter(
            (array) $category->getParentCategories(),
            static function ($item): bool {
                return $item instanceof Category && (int) $item->getLevel() >= 2;
            }
        );
        $byId = [];
        foreach ($categories as $item) {
            $byId[(int) $item->getId()] = $item;
        }
        // Make sure the trail always ends with the category itself
        $byId[(int) $category->getId()] = $category;
        uasort($byId, static function (Category $a, Category $b): int {
            return (int) $a->getLevel() <=> (int) $b->getLevel();
        });

        $trail = [];
        foreach ($byId as $item) {
            $trail[] = ['name' => (string) $item->getName(), 'url' => (string) $item->getUrl()];
        }

        return $trail;
    }
}
