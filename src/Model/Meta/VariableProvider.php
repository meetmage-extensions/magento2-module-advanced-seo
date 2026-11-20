<?php
declare(strict_types=1);

namespace MeetMage\AdvancedSeo\Model\Meta;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use MeetMage\AdvancedSeo\Model\ProductAttributeReader;

/**
 * Collects the values that meta templates can use for a product or a category.
 */
class VariableProvider
{
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var PriceCurrencyInterface
     */
    private PriceCurrencyInterface $priceCurrency;

    /**
     * @var TemplateRenderer
     */
    private TemplateRenderer $renderer;

    /**
     * @var ProductAttributeReader
     */
    private ProductAttributeReader $attributeReader;

    /**
     * @param StoreManagerInterface $storeManager
     * @param PriceCurrencyInterface $priceCurrency
     * @param TemplateRenderer $renderer
     * @param ProductAttributeReader $attributeReader
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceCurrency,
        TemplateRenderer $renderer,
        ProductAttributeReader $attributeReader
    ) {
        $this->storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
        $this->renderer = $renderer;
        $this->attributeReader = $attributeReader;
    }

    /**
     * Variables for a product page.
     *
     * @param Product $product
     * @param Category|null $category Category the visitor came from, if any
     * @param string[] $attributeCodes Codes used as {{attr:code}} in the templates
     * @return array<string,string>
     */
    public function forProduct(Product $product, ?Category $category = null, array $attributeCodes = []): array
    {
        $variables = [
            'name' => $this->renderer->clean((string) $product->getName()),
            'sku' => (string) $product->getSku(),
            'price' => $this->formatPrice($product),
            'store' => $this->getStoreName(),
            'category' => $category ? $this->renderer->clean((string) $category->getName()) : '',
            'short_description' => $this->renderer->clean((string) $product->getData('short_description')),
            'description' => $this->renderer->clean((string) $product->getData('description')),
        ];
        foreach ($attributeCodes as $code) {
            $variables['attr:' . strtolower($code)] = $this->attributeReader->getText($product, $code);
        }

        return $variables;
    }

    /**
     * Variables for a category page.
     *
     * @param Category $category
     * @return array<string,string>
     */
    public function forCategory(Category $category): array
    {
        $parentName = '';
        // Level 2 categories sit directly under the root category, which visitors never see
        if ((int) $category->getLevel() > 2) {
            $parentName = $this->renderer->clean((string) $category->getParentCategory()->getName());
        }

        return [
            'name' => $this->renderer->clean((string) $category->getName()),
            'parent' => $parentName,
            'store' => $this->getStoreName(),
            'description' => $this->renderer->clean((string) $category->getData('description')),
        ];
    }

    /**
     * Final price in the visitor's currency, formatted without HTML (e.g. "$29.00").
     *
     * @param Product $product
     * @return string
     */
    private function formatPrice(Product $product): string
    {
        $amount = (float) $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
        if ($amount <= 0) {
            return '';
        }

        return $this->renderer->clean((string) $this->priceCurrency->convertAndFormat($amount, false));
    }

    /**
     * Store name shown to visitors.
     *
     * @return string
     */
    private function getStoreName(): string
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore();
        $name = (string) $store->getFrontendName();

        return $this->renderer->clean($name !== '' ? $name : (string) $store->getName());
    }
}
