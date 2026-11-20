<?php
declare(strict_types=1);

namespace MeetMage\AdvancedSeo\Observer;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Adds this module to Hyvä's generated Tailwind config (hyva_config_generate_before),
 * so classes used in its templates are compiled into Hyvä themes.
 */
class RegisterModuleForHyvaConfig implements ObserverInterface
{
    private const MODULE_NAME = 'MeetMage_AdvancedSeo';

    /**
     * @var ComponentRegistrar
     */
    private ComponentRegistrar $componentRegistrar;

    /**
     * @param ComponentRegistrar $componentRegistrar
     */
    public function __construct(ComponentRegistrar $componentRegistrar)
    {
        $this->componentRegistrar = $componentRegistrar;
    }

    /**
     * Append this module's path to the "extensions" list of the Hyvä config.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $config = $observer->getData('config');
        $extensions = $config->hasData('extensions') ? $config->getData('extensions') : [];
        $path = (string) $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, self::MODULE_NAME);
        // Hyvä expects a path relative to the Magento root
        $extensions[] = ['src' => defined('BP') ? ltrim(substr($path, strlen(BP)), '/') : $path];
        $config->setData('extensions', $extensions);
    }
}
