<?php
declare(strict_types=1);

namespace MeetMage\AdvancedSeo\Test\Unit\Model\StructuredData;

use Magento\Store\Model\StoreManagerInterface;
use MeetMage\AdvancedSeo\Model\StructuredData\BreadcrumbBuilder;
use PHPUnit\Framework\TestCase;

class BreadcrumbBuilderTest extends TestCase
{
    public function testBuildNumbersItemsAndSkipsIncompleteOnes(): void
    {
        $builder = new BreadcrumbBuilder($this->createMock(StoreManagerInterface::class));

        $node = $builder->build([
            ['name' => 'Home', 'url' => 'https://shop.test/'],
            ['name' => '', 'url' => 'https://shop.test/hidden.html'],
            ['name' => 'Shoes', 'url' => 'https://shop.test/shoes.html'],
            ['name' => 'Trail Shoe', 'url' => 'https://shop.test/trail-shoe.html'],
        ]);

        $this->assertSame('BreadcrumbList', $node['@type']);
        $this->assertCount(3, $node['itemListElement']);
        $this->assertSame([1, 2, 3], array_column($node['itemListElement'], 'position'));
        $this->assertSame(
            ['Home', 'Shoes', 'Trail Shoe'],
            array_column($node['itemListElement'], 'name')
        );
        $this->assertSame('https://shop.test/shoes.html', $node['itemListElement'][1]['item']);
    }
}
