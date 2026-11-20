<?php
declare(strict_types=1);

namespace MeetMage\AdvancedSeo\ViewModel;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use MeetMage\AdvancedSeo\Model\Config;
use MeetMage\AdvancedSeo\Model\StructuredData\BreadcrumbBuilder;
use MeetMage\AdvancedSeo\Model\StructuredData\JsonLdEncoder;
use MeetMage\AdvancedSeo\Model\StructuredData\OrganizationBuilder;
use MeetMage\AdvancedSeo\Model\StructuredData\ProductBuilder;
use Psr\Log\LoggerInterface;

/**
 * Collects the JSON-LD nodes for the current page. Theme-independent (Hyvä and Luma).
 */
class StructuredData implements ArgumentInterface
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var HttpRequest
     */
    private HttpRequest $request;

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @var OrganizationBuilder
     */
    private OrganizationBuilder $organizationBuilder;

    /**
     * @var ProductBuilder
     */
    private ProductBuilder $productBuilder;

    /**
     * @var BreadcrumbBuilder
     */
    private BreadcrumbBuilder $breadcrumbBuilder;

    /**
     * @var JsonLdEncoder
     */
    private JsonLdEncoder $encoder;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param Config $config
     * @param HttpRequest $request
     * @param Registry $registry
     * @param OrganizationBuilder $organizationBuilder
     * @param ProductBuilder $productBuilder
     * @param BreadcrumbBuilder $breadcrumbBuilder
     * @param JsonLdEncoder $encoder
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        HttpRequest $request,
        Registry $registry,
        OrganizationBuilder $organizationBuilder,
        ProductBuilder $productBuilder,
        BreadcrumbBuilder $breadcrumbBuilder,
        JsonLdEncoder $encoder,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->request = $request;
        $this->registry = $registry;
        $this->organizationBuilder = $organizationBuilder;
        $this->productBuilder = $productBuilder;
        $this->breadcrumbBuilder = $breadcrumbBuilder;
        $this->encoder = $encoder;
        $this->logger = $logger;
    }

    /**
     * JSON-LD document for the current page, or an empty string.
     *
     * @return string
     */
    public function getJson(): string
    {
        if (!$this->config->isEnabled()) {
            return '';
        }

        try {
            return $this->encoder->encode($this->getNodes());
        } catch (\Throwable $e) {
            // Structured data must never break the storefront
            $this->logger->error('MeetMage_AdvancedSeo: structured data failed: ' . $e->getMessage());

            return '';
        }
    }

    /**
     * Schema.org nodes for the current page.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getNodes(): array
    {
        $nodes = [];
        $withOrganization = $this->config->isOrganizationEnabled();
        if ($withOrganization) {
            $nodes[] = $this->organizationBuilder->buildOrganization();
        }
        if ($this->config->isWebsiteEnabled()) {
            $nodes[] = $this->organizationBuilder->buildWebSite($withOrganization);
        }

        $action = $this->request->getFullActionName();
        $category = $this->registry->registry('current_category');
        $category = $category instanceof Category ? $category : null;

        if ($action === 'catalog_product_view') {
            $product = $this->registry->registry('current_product');
            if ($product instanceof Product) {
                if ($this->config->isProductEnabled()) {
                    $nodes[] = $this->productBuilder->build($product);
                }
                if ($this->config->isBreadcrumbsEnabled()) {
                    $nodes[] = $this->breadcrumbBuilder->forProduct($product, $category);
                }
            }
        } elseif ($action === 'catalog_category_view' && $category && $this->config->isBreadcrumbsEnabled()) {
            $nodes[] = $this->breadcrumbBuilder->forCategory($category);
        }

        return $nodes;
    }
}
