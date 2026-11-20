# Advanced SEO Suite for Magento 2

Smart meta templates, structured data and canonical URL control for Magento 2, with Hyvä and Luma support.

> **Latest release:** [v1.0.0](https://github.com/meetmage-extensions/magento2-module-advanced-seo/releases/tag/v1.0.0)
> (tested on Magento Open Source 2.4.9). Install it from
> [Packagist](https://packagist.org/packages/meetmage/module-advanced-seo).

[![CI](https://github.com/meetmage-extensions/magento2-module-advanced-seo/actions/workflows/ci.yml/badge.svg)](https://github.com/meetmage-extensions/magento2-module-advanced-seo/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/meetmage/module-advanced-seo.svg)](https://packagist.org/packages/meetmage/module-advanced-seo)
![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)

## Features

### Meta templates
Fill in missing meta titles and descriptions for products and categories from templates such as
`{{name}} by {{attr:manufacturer}} | {{price}}`.

- Applied **only** when a product or category has no meta value of its own; merchant-entered values are never replaced.
- Variables: `{{name}}`, `{{store}}`, `{{description}}`; products also `{{sku}}`, `{{price}}`, `{{category}}`,
  `{{short_description}}` and any attribute as `{{attr:code}}` (option labels for select attributes);
  categories also `{{parent}}`.
- Empty variables and the separators around them are removed, so `{{name}} | {{attr:brand}} | {{store}}`
  still reads well for products without a brand.
- Titles and descriptions are cut at a word boundary (60 / 160 characters by default, configurable).

### Structured data (JSON-LD)
One server-rendered `@graph` in the page head, so it works the same on Hyvä and Luma:

- **Organization** on every page: name, URL, logo and social profiles (`sameAs`).
- **WebSite** with the store search as `SearchAction`.
- **Product** on product pages: name, SKU, description, image, brand, GTIN and an `Offer` with the
  final price in the visitor's currency and stock status.
- **BreadcrumbList** on category and product pages. Product pages opened directly (as search
  engines do) use the product's deepest category, so the trail is never just Home > Product.

Each type can be switched off, for example if your theme already outputs Product markup.

### Canonical URLs
- Canonical URL for **CMS pages and the homepage**, without tracking parameters.
- Optional: simple products that are visible on their own but belong to a configurable product use the
  **parent's URL as canonical**, to avoid duplicate content.

Magento's own product and category canonical settings (**Catalog → Search Engine Optimization**) keep working.

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

## Configuration

**Stores → Configuration → MeetMage → Advanced SEO Suite** (default, website and store view scope)

| Group | Setting | Default |
|---|---|---|
| General | Enable | Yes |
| Meta Templates | Product / category meta title and description templates | Empty (Magento default) |
| Meta Templates | Title / description max length | 60 / 160 |
| Structured Data | Organization, WebSite, Product, Breadcrumbs | Yes |
| Structured Data | Organization name / logo / social profiles | Store name / none / none |
| Structured Data | Brand attribute / GTIN attribute | `manufacturer` / none |
| Canonical URLs | CMS pages and homepage | Yes |
| Canonical URLs | Point child products to their configurable parent | No |

Check the result with Google's [Rich Results Test](https://search.google.com/test/rich-results).

## How it works

- Meta templates and canonical URLs are applied by a plugin on `Magento\Framework\View\Page\Config\Renderer`
  just before the page head is rendered, after core controllers and blocks have set their values.
- Structured data is a block in `head.additional` with a view model; values are JSON-encoded with
  `<`, `>` and `&` escaped, so content can never break out of the script tag.
- Errors are logged and never break the storefront.

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
