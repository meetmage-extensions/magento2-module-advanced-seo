<?php
declare(strict_types=1);

namespace MeetMage\AdvancedSeo\Test\Unit\Model;

use MeetMage\AdvancedSeo\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testIsEnabledReadsStoreScopedFlag(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(Config::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, 3)
            ->willReturn(true);

        $this->assertTrue((new Config($scopeConfig))->isEnabled(3));
    }

    public function testIsDisabledWhenFlagIsOff(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturn(false);

        $this->assertFalse((new Config($scopeConfig))->isEnabled());
    }

    public function testSameAsKeepsOnlyHttpUrls(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn(
            "https://www.linkedin.com/company/shop\r\n\n  https://instagram.com/shop  \nnot a url\njavascript:alert(1)"
        );

        $this->assertSame(
            ['https://www.linkedin.com/company/shop', 'https://instagram.com/shop'],
            (new Config($scopeConfig))->getSameAs()
        );
    }

    public function testMaxLengthsAreNeverNegative(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn('-5');

        $this->assertSame(0, (new Config($scopeConfig))->getTitleMaxLength());
    }
}
