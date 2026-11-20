# Advanced SEO Suite for Magento 2

Smart meta templates, structured data and canonical URL control for Magento 2, with Hyvä and Luma support.

> **Status:** in development, not released yet.

[![CI](https://github.com/meetmage-extensions/magento2-module-advanced-seo/actions/workflows/ci.yml/badge.svg)](https://github.com/meetmage-extensions/magento2-module-advanced-seo/actions/workflows/ci.yml)
![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)

## Features

- Coming in v1.0.0

## Compatibility

| | Supported |
|---|---|
| Magento Open Source / Adobe Commerce | 2.4.6, 2.4.7, 2.4.8 |
| PHP | 8.1 – 8.5 |
| Hyvä Themes | 1.3+ |
| Luma | Yes |

## Installation

```bash
composer require meetmage/module-advanced-seo
bin/magento module:enable MeetMage_AdvancedSeo
bin/magento setup:upgrade
bin/magento cache:flush
```

On Hyvä stores, rebuild the theme CSS afterwards so the module's Tailwind classes are included.

## Configuration

**Stores → Configuration → MeetMage → Advanced SEO Suite**

| Setting | Default | Description |
|---|---|---|
| Enable | Yes | Turns the module on or off per store view |

## Development

```bash
composer config repositories.mage-os composer https://mirror.mage-os.org/
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse
```

## Support

- Bugs and feature requests: [GitHub issues](https://github.com/meetmage-extensions/magento2-module-advanced-seo/issues)
- Custom Magento & Hyvä development: [meetshekhat.com](https://meetshekhat.com)

## License

[MIT](LICENSE) © 2026 Meet Shekhat
