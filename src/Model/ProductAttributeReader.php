<?php
declare(strict_types=1);

namespace MeetMage\AdvancedSeo\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use MeetMage\AdvancedSeo\Model\Meta\TemplateRenderer;

/**
 * Reads a product attribute as display text: option labels for select / multiselect
 * attributes, plain text (HTML stripped) for everything else.
 */
class ProductAttributeReader
{
    /**
     * @var ProductResource
     */
    private ProductResource $productResource;

    /**
     * @var TemplateRenderer
     */
    private TemplateRenderer $renderer;

    /**
     * @param ProductResource $productResource
     * @param TemplateRenderer $renderer
     */
    public function __construct(ProductResource $productResource, TemplateRenderer $renderer)
    {
        $this->productResource = $productResource;
        $this->renderer = $renderer;
    }

    /**
     * Display value of an attribute, or an empty string when it is missing or empty.
     *
     * @param Product $product
     * @param string $attributeCode
     * @return string
     */
    public function getText(Product $product, string $attributeCode): string
    {
        $attributeCode = trim($attributeCode);
        $value = $attributeCode === '' ? null : $product->getData($attributeCode);
        if ($value === null || $value === '' || $value === false) {
            return '';
        }

        $attribute = $this->productResource->getAttribute($attributeCode);
        if ($attribute && $attribute->usesSource()) {
            return $this->labelToText($attribute->getSource()->getOptionText($value));
        }

        return is_scalar($value) ? $this->renderer->clean((string) $value) : '';
    }

    /**
     * Option label(s) as text.
     *
     * Multiselect attributes return an array of labels at runtime, even though the source
     * model interface documents string|bool.
     *
     * @param mixed $label
     * @return string
     */
    private function labelToText(mixed $label): string
    {
        if (is_array($label)) {
            $label = implode(', ', array_filter(array_map('strval', $label)));
        }

        return is_scalar($label) && $label !== false ? $this->renderer->clean((string) $label) : '';
    }
}
