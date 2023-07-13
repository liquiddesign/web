# Ⓦ LiquidDesign/Web - CHANGELOG

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2023-07-13

Note to versioning: version 1 is skipped to match version 2 with other packages.

### Added

- Entities now supports Shop:
  - `Page`
  - `News`
  - `Banner`
  - `HomepageSlide`
- `TemplateFactory` to create templates
  - Extends original `TemplateFactory` from `liquiddesign/base` package
  - Adds Pages functions

### Changed

- **BREAKING:** PHP version 8.2 or higher is required
- **BREAKING:** `Page` unique index on URL is removed. **Due to limitations of migrator, create indexes manually.**
- **BREAKING:** Changed `getCollection` methods for entities with shops.

### Removed

### Deprecated

### Fixed