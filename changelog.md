<!--- BEGIN HEADER -->
# Changelog

All notable changes to this project will be documented in this file.
<!--- END HEADER -->

## [2.1.22](https://github.com/liquiddesign/web/compare/v2.1.21...v2.1.22) (2024-08-06)

### Bug Fixes

* Cast query parameter name to string in TemplateFactory ([9c4886](https://github.com/liquiddesign/web/commit/9c48868f84413f4ff78048d3ab4f2745a59cfab0))


---

## [2.1.21](https://github.com/liquiddesign/web/compare/v2.1.20...v2.1.21) (2024-08-02)

### Bug Fixes

* Cast query parameter name to string in TemplateFactory ([612480](https://github.com/liquiddesign/web/commit/612480bf1cd391b1a755a94e570ad5cae06ca18a))


---

## [2.1.13](https://github.com/liquiddesign/web/compare/v2.1.12...v2.1.13) (2024-06-05)

### Features


##### Template Factory

* Make $additionalParameters to lower ([da73ba](https://github.com/liquiddesign/web/commit/da73ba0eab1296588b31f2ded56401453dcb113b))

### Bug Fixes

* Change date input to polyfill ([09cfea](https://github.com/liquiddesign/web/commit/09cfea1ac686dce01e3e70b9eac54838d1bfaf3a))


---

## [2.1.12](https://github.com/liquiddesign/web/compare/v2.1.11...v2.1.12) (2024-04-04)

### Features

* Lower name in getUtmCanonicalUrl ([55a698](https://github.com/liquiddesign/web/commit/55a6989732389cd34d355b2281e96d3abe2251e7))

##### Template Factory

* Add getUtmCanonicalUrl ([6d743f](https://github.com/liquiddesign/web/commit/6d743f860bf200f3fd678b0a53fada6df03caf32))


---

## [2.1.11](https://github.com/liquiddesign/web/compare/v2.1.10...v2.1.11) (2024-04-03)

### Features


##### Template Factory

* Add getUtmCanonicalUrl ([474694](https://github.com/liquiddesign/web/commit/4746941288764c4d5f354bc214d52d3161fbad79))


---

## [2.1.10](https://github.com/liquiddesign/web/compare/v2.1.9...v2.1.10) (2024-03-22)

### Bug Fixes

* Change date input to polyfill ([3c8e18](https://github.com/liquiddesign/web/commit/3c8e184cf64bd321dbccac1cae9aca469521a61e), [846186](https://github.com/liquiddesign/web/commit/846186ea27f8812096bcceff8463ccde9be50463))


---

## [2.1.9](https://github.com/liquiddesign/web/compare/v2.1.8...v2.1.9) (2024-03-18)

### Bug Fixes

* Change date input to polyfill ([5e99f1](https://github.com/liquiddesign/web/commit/5e99f1a431fee0f366b23dcb30a4a94badfdde96))


---

## [2.1.7](https://github.com/liquiddesign/web/compare/v2.1.6...v2.1.7) (2024-03-08)


---

## [2.1.6](https://github.com/liquiddesign/web/compare/v2.1.5...v2.1.6) (2024-03-07)


---

## [2.1.5](https://github.com/liquiddesign/web/compare/v2.1.4...v2.1.5) (2024-03-07)


---

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