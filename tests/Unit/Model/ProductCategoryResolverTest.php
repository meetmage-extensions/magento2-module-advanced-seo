<?php
declare(strict_types=1);

namespace MeetMage\AdvancedSeo\Test\Unit\Model;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Registry;
use Magento\Store\Model\Group;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use MeetMage\AdvancedSeo\Model\ProductCategoryResolver;
use PHPUnit\Framework\TestCase;

class ProductCategoryResolverTest extends TestCase
{
    public function testCurrentCategoryWins(): void
    {
        $current = $this->createMock(Category::class);
        $registry = $this->createMock(Registry::class);
        $registry->method('registry')->with('current_category')->willReturn($current);
        $factory = $this->createMock(CollectionFactory::class);
        $factory->expects($this->never())->method('create');

        $resolver = new ProductCategoryResolver(
            $registry,
            $factory,
            $this->createMock(StoreManagerInterface::class)
        );

        $this->assertSame($current, $resolver->getCategory($this->createMock(Product::class)));
    }

    public function testFallsBackToDeepestCategoryInStoreRootOnce(): void
    {
        $deepest = $this->createMock(Category::class);
        $deepest->method('getId')->willReturn(12);

        $collection = $this->createMock(Collection::class);
        foreach (['setStoreId', 'addAttributeToSelect', 'addIsActiveFilter', 'setOrder', 'setPageSize'] as $method) {
            $collection->method($method)->willReturnSelf();
        }
        $collection->expects($this->once())->method('addIdFilter')->with([4, 12])->willReturnSelf();
        $collection->expects($this->once())->method('addPathFilter')->with('^1/2/')->willReturnSelf();
        $collection->method('getFirstItem')->willReturn($deepest);
        $factory = $this->createMock(CollectionFactory::class);
        $factory->expects($this->once())->method('create')->willReturn($collection);

        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn(1);
        $store->method('getStoreGroupId')->willReturn(1);
        $group = $this->createMock(Group::class);
        $group->method('getRootCategoryId')->willReturn(2);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);
        $storeManager->method('getGroup')->with('1')->willReturn($group);

        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn(5);
        $product->method('getCategoryIds')->willReturn(['4', '12']);

        $resolver = new ProductCategoryResolver($this->createMock(Registry::class), $factory, $storeManager);

        $this->assertSame($deepest, $resolver->getCategory($product));
        $this->assertSame($deepest, $resolver->getCategory($product));
    }

    public function testProductWithoutCategoriesHasNone(): void
    {
        $factory = $this->createMock(CollectionFactory::class);
        $factory->expects($this->never())->method('create');
        $product = $this->createMock(Product::class);
        $product->method('getCategoryIds')->willReturn([]);

        $resolver = new ProductCategoryResolver(
            $this->createMock(Registry::class),
            $factory,
            $this->createMock(StoreManagerInterface::class)
        );

        $this->assertNull($resolver->getCategory($product));
    }
}
