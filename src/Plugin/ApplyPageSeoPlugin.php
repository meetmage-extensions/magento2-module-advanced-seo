<?php
declare(strict_types=1);

namespace MeetMage\AdvancedSeo\Plugin;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\View\Page\Config\Renderer;
use MeetMage\AdvancedSeo\Model\Canonical\CanonicalApplier;
use MeetMage\AdvancedSeo\Model\Config;
use MeetMage\AdvancedSeo\Model\Meta\MetaApplier;
use Psr\Log\LoggerInterface;

/**
 * Applies meta templates and canonical URLs right before the page head is rendered.
 *
 * At this point controllers and blocks have finished setting their own values, so the
 * result is the same on Luma and Hyvä no matter in which order the layout was built.
 */
class ApplyPageSeoPlugin
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
     * @var MetaApplier
     */
    private MetaApplier $metaApplier;

    /**
     * @var CanonicalApplier
     */
    private CanonicalApplier $canonicalApplier;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var bool
     */
    private bool $applied = false;

    /**
     * @param Config $config
     * @param HttpRequest $request
     * @param MetaApplier $metaApplier
     * @param CanonicalApplier $canonicalApplier
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $config,
        HttpRequest $request,
        MetaApplier $metaApplier,
        CanonicalApplier $canonicalApplier,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->request = $request;
        $this->metaApplier = $metaApplier;
        $this->canonicalApplier = $canonicalApplier;
        $this->logger = $logger;
    }

    /**
     * Apply SEO values once per request, before the head content is rendered.
     *
     * @param Renderer $subject
     * @return null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeRenderHeadContent(Renderer $subject)
    {
        if ($this->applied || !$this->config->isEnabled()) {
            return null;
        }
        $this->applied = true;

        $action = (string) $this->request->getFullActionName();
        try {
            $this->metaApplier->apply($action);
            $this->canonicalApplier->apply($action);
        } catch (\Throwable $e) {
            // SEO tweaks must never break the storefront
            $this->logger->error('MeetMage_AdvancedSeo: could not apply page SEO: ' . $e->getMessage());
        }

        return null;
    }
}
