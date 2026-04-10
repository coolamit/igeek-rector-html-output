# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-04-09

### Updated
- Added sidebar file tree and dry-run badge:
  1. Replaced flat file list in sidebar with hierarchical folder tree.
  2. Sorted main content diff sections to match sidebar tree order.
  3. Added "Dry Run" vs "Applied" badge in report header.
  4. Added package attribution link in report header.

## [1.1.0] - 2026-04-08

### Updated
- Updated HTML templates:
  1. Changed file block header to be not clickable and file path to be selectable.
  2. Changed file block collapse/expand arrow icon to an inline SVG.
  3. Added file path copy button to file block header to easily copy file path.

## [1.0.1] - 2026-01-06

### Added
- PHP-CS_Fixer as dev dependency for code style consistency
- Github Actions workflow for automated testing

### Updated
- Code style improvements across the codebase

## [1.0.0] - 2026-01-06

### Added
- First stable release
- HTML output formatter for Rector PHP
- Self-contained HTML reports with dark/light mode
- Configurable output directory
- Auto-incrementing filenames
- Zero external dependencies
- Comprehensive test coverage
