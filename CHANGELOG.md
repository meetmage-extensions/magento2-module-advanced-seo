# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.0.1] - 2026-11-25

### Changed
- Composer keywords for Packagist search (seo, meta-tags, structured-data, json-ld, schema-org, rich-results, canonical). No code changes.

## [1.0.0] - 2026-11-20

### Added
- Meta title / description templates for products and categories, with `{{attr:code}}` variables,
  automatic cleanup of empty variables and word-boundary length limits
- JSON-LD structured data: Organization, WebSite with SearchAction, Product with Offer, BreadcrumbList
- Product pages without a current category fall back to the product's deepest active category
  for breadcrumbs and the `{{category}}` variable
- Canonical URLs for CMS pages and the homepage
- Optional canonical from child simple products to their configurable parent
- Admin configuration under Stores > Configuration > MeetMage > Advanced SEO Suite
- Hyvä Tailwind registration

[Unreleased]: https://github.com/meetmage-extensions/magento2-module-advanced-seo/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/meetmage-extensions/magento2-module-advanced-seo/releases/tag/v1.0.0
