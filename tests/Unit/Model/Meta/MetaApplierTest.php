<?php
declare(strict_types=1);

namespace MeetMage\AdvancedSeo\Test\Unit\Model\Meta;

use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\View\Page\Title;
use MeetMage\AdvancedSeo\Model\Config;
use MeetMage\AdvancedSeo\Model\Meta\MetaApplier;
use MeetMage\AdvancedSeo\Model\Meta\TemplateRenderer;
use MeetMage\AdvancedSeo\Model\Meta\VariableProvider;
use PHPUnit\Framework\TestCase;

class MetaApplierTest extends TestCase
{
    public function testFillsOnlyTheMissingProductMetaValues(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getData')->willReturnMap([
            ['meta_title', null, ''],
            ['meta_description', null, 'Written by the merchant'],
        ]);
        $registry = $this->createMock(Registry::class);
        $registry->method('registry')->willReturnMap([
            ['current_product', $product],
            ['current_category', null],
        ]);

        $config = $this->createMock(Config::class);
        $config->method('getProductTitleTemplate')->willReturn('{{name}} | {{attr:brand}} | {{store}}');
        $config->method('getProductDescriptionTemplate')->willReturn('Buy {{name}}');
        $config->method('getTitleMaxLength')->willReturn(60);

        $variables = $this->createMock(VariableProvider::class);
        $variables->expects($this->once())
            ->method('forProduct')
            ->with($product, null, ['brand'])
            ->willReturn(['name' => 'Trail Shoe', 'store' => 'Shoe Shop', 'attr:brand' => '']);

        $title = $this->createMock(Title::class);
        $title->expects($this->once())->method('set')->with('Trail Shoe | Shoe Shop');
        $pageConfig = $this->createMock(PageConfig::class);
        $pageConfig->method('getTitle')->willReturn($title);
        $pageConfig->expects($this->once())->method('setMetaTitle')->with('Trail Shoe | Shoe Shop');
        $pageConfig->expects($this->never())->method('setDescription');

        (new MetaApplier($config, $registry, $pageConfig, new TemplateRenderer(), $variables))
            ->apply('catalog_product_view');
    }

    public function testIgnoresOtherPages(): void
    {
        $registry = $this->createMock(Registry::class);
        $registry->expects($this->never())->method('registry');

        (new MetaApplier(
            $this->createMock(Config::class),
            $registry,
            $this->createMock(PageConfig::class),
            new TemplateRenderer(),
            $this->createMock(VariableProvider::class)
        ))->apply('checkout_cart_index');
    }
}
