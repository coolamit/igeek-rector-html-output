# Rector HTML Output

![License][icon-license]
![PHP][icon-php]
[![Latest Version on Packagist][icon-version]][href-version]
[![GitHub PHPUnit Action Status][icon-tests]][href-tests]
[![GitHub PHPStan Action Status][icon-phpstantest]][href-phpstantest]
[![GitHub PhpCsFixer Action Status][icon-style]][href-style]

Beautiful, self-contained HTML output formatter for [Rector PHP](https://getrector.com/).

## Features

- **Beautiful HTML Reports** - Modern, responsive design with dark/light mode
- **Zero External Dependencies** - No CDN CSS/JS libraries required
- **Easy Setup** - Simple helper function for `rector.php`
- **Auto-increment** - Never overwrite existing reports
- **Statistics** - Lines added/removed, files changed
- **Dark Mode** - Automatic theme with localStorage persistence
- **Searchable** - Filter files by name in sidebar
- **Collapsible** - Expand/collapse individual file diffs

## Installation

```bash
composer require --dev igeek/rector-html-output
```

## Usage

**IMPORTANT**: This package requires the callback-style `rector.php` because the fluent `RectorConfigBuilder::registerService()` only accepts class names, not factory closures needed for custom configuration.

### Basic Configuration

Add to your `rector.php`:

```php
<?php

use Rector\Config\RectorConfig;
use function Igeek\RectorHtmlOutput\withHtmlOutput;

return function (RectorConfig $rectorConfig): void {
    // Standard Rector configuration
    $rectorConfig->paths([__DIR__ . '/app']);

    // Register HTML output formatter
    withHtmlOutput($rectorConfig, __DIR__ . '/rector-reports');
};
```

### Run Rector with HTML Output

```bash
# Dry run with HTML report
vendor/bin/rector process --dry-run --output-format=html

# Apply changes with HTML report
vendor/bin/rector process --output-format=html

# Specific paths
vendor/bin/rector process src/Services --output-format=html
```

### Advanced Configuration

```php
<?php

use Rector\Config\RectorConfig;
use function Igeek\RectorHtmlOutput\withHtmlOutput;

return function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([__DIR__ . '/app']);

    withHtmlOutput(
        rectorConfig: $rectorConfig,
        outputDirectory: __DIR__ . '/reports',    // Auto-created if missing
        filename: 'my-custom-report',              // Without .html extension
        autoIncrement: true,                       // Auto-increment if file exists
        skipEmpty: true,                           // Skip report if no changes
    );
};
```

### Manual Registration (Advanced)

For users who need full control:

```php
<?php

use Rector\Config\RectorConfig;
use Igeek\RectorHtmlOutput\HtmlOutputFormatter;
use Igeek\RectorHtmlOutput\Config\HtmlOutputConfig;
use Igeek\RectorHtmlOutput\Template\TemplateRenderer;
use Igeek\RectorHtmlOutput\Template\PlaceholderReplacer;
use Rector\ChangesReporting\Contract\Output\OutputFormatterInterface;

return function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([__DIR__ . '/app']);

    $rectorConfig->singleton(HtmlOutputConfig::class, fn() => new HtmlOutputConfig(
        outputDirectory: __DIR__ . '/rector-reports',
        filename: 'my-report',
    ));
    $rectorConfig->singleton(PlaceholderReplacer::class);
    $rectorConfig->singleton(TemplateRenderer::class, fn($c) => new TemplateRenderer(
        $c->get(HtmlOutputConfig::class)->getTemplatePath(),
        $c->get(PlaceholderReplacer::class)
    ));
    $rectorConfig->singleton(HtmlOutputFormatter::class);
    $rectorConfig->tag(HtmlOutputFormatter::class, OutputFormatterInterface::class);
};
```

## Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `outputDirectory` | string | *required* | Directory where reports will be saved (auto-created) |
| `filename` | string | `'rector-report'` | Base filename (without extension) |
| `autoIncrement` | bool | `true` | Auto-increment filename if exists |
| `skipEmpty` | bool | `true` | Skip report generation if no changes |

## Output Example

Reports include:
- Summary statistics (files changed, lines added/removed)
- Searchable file list sidebar
- Syntax-highlighted diffs with line numbers
- Dark/light mode toggle (persisted via localStorage)
- Collapsible file sections
- Collapse/expand all button

## Requirements

- PHP 8.3 or higher
- Rector ^2.0

## License

MIT License - see [LICENSE](LICENSE) file

## Credits

Created by [Amit Gupta](https://amitgupta.in/)

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.


[href-phpstantest]: https://github.com/coolamit/igeek-rector-html-output/actions/workflows/phpstan.yml
[href-style]: https://github.com/coolamit/igeek-rector-html-output/actions/workflows/code-style.yml
[href-tests]: https://github.com/coolamit/igeek-rector-html-output/actions/workflows/phpunit.yml
[href-version]: https://packagist.org/packages/igeek/rector-html-output
[icon-codestyle]: https://img.shields.io/github/actions/workflow/status/coolamit/igeek-rector-html-output/code-style.yml?branch=master&label=Code%20Style
[icon-license]: https://img.shields.io/github/license/coolamit/igeek-rector-html-output?color=blue&label=License
[icon-phpstantest]: https://img.shields.io/github/actions/workflow/status/coolamit/igeek-rector-html-output/phpstan.yml?branch=master&label=PHPStan
[icon-php]: https://img.shields.io/packagist/php-v/igeek/rector-html-output?color=blue&label=PHP
[icon-style]: https://img.shields.io/github/actions/workflow/status/coolamit/igeek-rector-html-output/code-style.yml?label=Code%20Style
[icon-tests]: https://img.shields.io/github/actions/workflow/status/coolamit/igeek-rector-html-output/phpunit.yml?label=Tests
[icon-version]: https://img.shields.io/packagist/v/igeek/rector-html-output.svg?label=Packagist
