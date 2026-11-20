<?php
declare(strict_types=1);

namespace MeetMage\AdvancedSeo\Model\StructuredData;

use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use MeetMage\AdvancedSeo\Model\Config;
use MeetMage\AdvancedSeo\Model\Meta\TemplateRenderer;
use MeetMage\AdvancedSeo\Model\ProductAttributeReader;

/**
 * Builds the schema.org Product node (with Offer, brand and GTIN) for a product page.
 */
class ProductBuilder
{
    private const MAX_DESCRIPTION_LENGTH = 5000;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var PriceCurrencyInterface
     */
    private PriceCurrencyInterface $priceCurrency;

    /**
     * @var ProductAttributeReader
     */
    private ProductAttributeReader $attributeReader;

    /**
     * @var TemplateRenderer
     */
    private TemplateRenderer $renderer;

    /**
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param PriceCurrencyInterface $priceCurrency
     * @param ProductAttributeReader $attributeReader
     * @param TemplateRenderer $renderer
     */
    public function __construct(
        Config $config,
        StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceCurrency,
        ProductAttributeReader $attributeReader,
        TemplateRenderer $renderer
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
        $this->attributeReader = $attributeReader;
        $this->renderer = $renderer;
    }

    /**
     * Product node.
     *
     * @param Product $product
     * @return array<string,mixed>
     */
    public function build(Product $product): array
    {
        $url = (string) $product->getProductUrl();
        $node = [
            '@type' => 'Product',
            '@id' => $url . '#product',
            'name' => $this->renderer->clean((string) $product->getName()),
            'sku' => (string) $product->getSku(),
            'url' => $url,
        ];

        $description = $this->getDescription($product);
        if ($description !== '') {
            $node['description'] = $description;
        }
        $image = $this->getImageUrl($product);
        if ($image !== '') {
            $node['image'] = $image;
        }
        $brand = $this->attributeReader->getText($product, $this->config->getBrandAttribute());
        if ($brand !== '') {
            $node['brand'] = ['@type' => 'Brand', 'name' => $brand];
        }
        $gtin = $this->attributeReader->getText($product, $this->config->getGtinAttribute());
        if ($gtin !== '') {
            $node['gtin'] = $gtin;
        }
        $offer = $this->buildOffer($product, $url);
        if ($offer !== []) {
            $node['offers'] = $offer;
        }

        return $node;
    }

    /**
     * Offer with the final price in the visitor's currency and the stock status.
     *
     * @param Product $product
     * @param string $url
     * @return array<string,mixed> Empty when the product has no usable price
     */
    private function buildOffer(Product $product, string $url): array
    {
        $amount = (float) $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
        if ($amount <= 0) {
            return [];
        }

        return [
            '@type' => 'Offer',
            'url' => $url,
            'price' => number_format($this->priceCurrency->convertAndRound($amount), 2, '.', ''),
            'priceCurrency' => (string) $this->getStore()->getCurrentCurrencyCode(),
            'availability' => $product->isSalable() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/NewCondition',
        ];
    }

    /**
     * Short description, or the full description, as plain text.
     *
     * @param Product $product
     * @return string
     */
    private function getDescription(Product $product): string
    {
        $text = $this->renderer->clean((string) $product->getData('short_description'));
        if ($text === '') {
            $text = $this->renderer->clean((string) $product->getData('description'));
        }

        return $this->renderer->truncate($text, self::MAX_DESCRIPTION_LENGTH);
    }

    /**
     * Absolute URL of the product's base image.
     *
     * @param Product $product
     * @return string
     */
    private function getImageUrl(Product $product): string
    {
        $image = (string) $product->getData('image');
        if ($image === '' || $image === 'no_selection') {
            return '';
        }

        return $this->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'catalog/product/' . ltrim($image, '/');
    }

    /**
     * Current store.
     *
     * @return Store
     */
    private function getStore(): Store
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore();

        return $store;
    }
}
