<?php
declare(strict_types=1);

namespace MeetMage\AdvancedSeo\Model\Meta;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Magento\Framework\View\Page\Config as PageConfig;
use MeetMage\AdvancedSeo\Model\Config;
use MeetMage\AdvancedSeo\Model\ProductCategoryResolver;

/**
 * Applies the meta title / description templates to product and category pages.
 *
 * Runs just before the page head is rendered, so it reliably wins over the titles that
 * core controllers and blocks set earlier. Merchant-entered meta values are never replaced.
 */
class MetaApplier
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @var PageConfig
     */
    private PageConfig $pageConfig;

    /**
     * @var TemplateRenderer
     */
    private TemplateRenderer $renderer;

    /**
     * @var VariableProvider
     */
    private VariableProvider $variableProvider;

    /**
     * @var ProductCategoryResolver
     */
    private ProductCategoryResolver $categoryResolver;

    /**
     * @param Config $config
     * @param Registry $registry
     * @param PageConfig $pageConfig
     * @param TemplateRenderer $renderer
     * @param VariableProvider $variableProvider
     * @param ProductCategoryResolver $categoryResolver
     */
    public function __construct(
        Config $config,
        Registry $registry,
        PageConfig $pageConfig,
        TemplateRenderer $renderer,
        VariableProvider $variableProvider,
        ProductCategoryResolver $categoryResolver
    ) {
        $this->config = $config;
        $this->registry = $registry;
        $this->pageConfig = $pageConfig;
        $this->renderer = $renderer;
        $this->variableProvider = $variableProvider;
        $this->categoryResolver = $categoryResolver;
    }

    /**
     * Apply templates for the current page.
     *
     * @param string $fullActionName
     * @return void
     */
    public function apply(string $fullActionName): void
    {
        if ($fullActionName === 'catalog_product_view') {
            $this->applyToProduct();
        } elseif ($fullActionName === 'catalog_category_view') {
            $this->applyToCategory();
        }
    }

    /**
     * Apply the product templates.
     *
     * @return void
     */
    private function applyToProduct(): void
    {
        $product = $this->registry->registry('current_product');
        if (!$product instanceof Product) {
            return;
        }
        $titleTemplate = $this->needsValue($product->getData('meta_title'))
            ? $this->config->getProductTitleTemplate() : '';
        $descriptionTemplate = $this->needsValue($product->getData('meta_description'))
            ? $this->config->getProductDescriptionTemplate() : '';
        if ($titleTemplate === '' && $descriptionTemplate === '') {
            return;
        }

        $variables = $this->variableProvider->forProduct(
            $product,
            $this->categoryResolver->getCategory($product),
            $this->renderer->getAttributeCodes($titleTemplate . ' ' . $descriptionTemplate)
        );
        $this->setTitle($titleTemplate, $variables);
        $this->setDescription($descriptionTemplate, $variables);
    }

    /**
     * Apply the category templates.
     *
     * @return void
     */
    private function applyToCategory(): void
    {
        $category = $this->registry->registry('current_category');
        if (!$category instanceof Category) {
            return;
        }
        $titleTemplate = $this->needsValue($category->getData('meta_title'))
            ? $this->config->getCategoryTitleTemplate() : '';
        $descriptionTemplate = $this->needsValue($category->getData('meta_description'))
            ? $this->config->getCategoryDescriptionTemplate() : '';
        if ($titleTemplate === '' && $descriptionTemplate === '') {
            return;
        }

        $variables = $this->variableProvider->forCategory($category);
        $this->setTitle($titleTemplate, $variables);
        $this->setDescription($descriptionTemplate, $variables);
    }

    /**
     * Set the page title (and meta title) from a template, if it renders to something.
     *
     * @param string $template
     * @param array<string,string> $variables
     * @return void
     */
    private function setTitle(string $template, array $variables): void
    {
        if ($template === '') {
            return;
        }
        $title = $this->renderer->render($template, $variables, $this->config->getTitleMaxLength());
        if ($title !== '') {
            $this->pageConfig->getTitle()->set($title);
            $this->pageConfig->setMetaTitle($title);
        }
    }

    /**
     * Set the meta description from a template, if it renders to something.
     *
     * @param string $template
     * @param array<string,string> $variables
     * @return void
     */
    private function setDescription(string $template, array $variables): void
    {
        if ($template === '') {
            return;
        }
        $description = $this->renderer->render($template, $variables, $this->config->getDescriptionMaxLength());
        if ($description !== '') {
            $this->pageConfig->setDescription($description);
        }
    }

    /**
     * Whether an entity's own meta value is empty, so a template should fill it.
     *
     * @param mixed $value
     * @return bool
     */
    private function needsValue(mixed $value): bool
    {
        return !is_scalar($value) || trim((string) $value) === '';
    }
}
