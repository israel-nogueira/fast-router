# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.1] - 2026-02-10

### Fixed
- Fixed typo in method name: `MATH` → `MATCH` in Router class
- Fixed inconsistent exception usage in Route class (now uses RouterException)
- Improved middleware autoload robustness with multiple fallback paths

### Changed
- Auto-dispatch in static mode is now optional and controlled via `enableAutoDispatch()`
- Added `Router::run()` static method for manual dispatch control

### Added
- New `enableAutoDispatch()` method for static mode configuration
- New `run()` static method for better control over route execution

## [2.0.0] - 2026-02-10

### Added
- Complete refactoring with modern PHP 8.1+ features
- Strict type hints throughout the codebase
- Improved middleware system with chain support
- Better error handling with custom exceptions
- PSR-4 autoloading structure
- Debug mode for development
- Route groups with prefix and middleware inheritance
- Optional route parameters support
- Regex constraints for route parameters
- Multiple HTTP methods support
- Controller support (string, array, closure syntax)

### Changed
- Namespace changed from `IsraelNogueira\fastRouter` to `IsraelNogueira\FastRouter`
- Minimum PHP version requirement: 8.1+
- All methods now use strict types

### Fixed
- Various edge cases in route matching
- Middleware execution order
- Route parameter extraction

## [1.x] - Legacy

Initial releases with basic routing functionality.

[2.0.1]: https://github.com/israel-nogueira/fast-router/compare/v2.0.0...v2.0.1
[2.0.0]: https://github.com/israel-nogueira/fast-router/compare/v1.x...v2.0.0
