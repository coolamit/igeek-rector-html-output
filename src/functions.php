<?php

declare(strict_types=1);

namespace Igeek\RectorHtmlOutput;

use Igeek\RectorHtmlOutput\Config\HtmlOutputConfig;
use Igeek\RectorHtmlOutput\Template\PlaceholderReplacer;
use Igeek\RectorHtmlOutput\Template\TemplateRenderer;
use Rector\ChangesReporting\Contract\Output\OutputFormatterInterface;
use Rector\Config\RectorConfig;

/**
 * Register HTML output formatter with Rector
 *
 * IMPORTANT: This function requires the callback-style rector.php because
 * the fluent RectorConfigBuilder::registerService() only accepts class names,
 * not factory closures needed for custom configuration.
 *
 * @param  RectorConfig  $rectorConfig  Rector configuration instance
 * @param  string  $outputDirectory  Directory where HTML reports will be saved (auto-created if missing)
 * @param  string  $filename  Base filename for reports (without extension)
 * @param  bool  $autoIncrement  Auto-increment filename if file exists
 * @param  bool  $skipEmpty  Skip report generation if no changes detected
 */
function withHtmlOutput(
    RectorConfig $rectorConfig,
    string $outputDirectory,
    string $filename = 'rector-report',
    bool $autoIncrement = true,
    bool $skipEmpty = true,
): void {
    $rectorConfig->singleton(
        HtmlOutputConfig::class,
        static fn() => new HtmlOutputConfig($outputDirectory, $filename, $autoIncrement, $skipEmpty),
    );

    $rectorConfig->singleton(PlaceholderReplacer::class);

    $rectorConfig->singleton(
        TemplateRenderer::class,
        static fn($container) => new TemplateRenderer(
            $container->make(HtmlOutputConfig::class)->getTemplatePath(),
            $container->make(PlaceholderReplacer::class),
        ),
    );

    $rectorConfig->singleton(HtmlOutputFormatter::class);

    $rectorConfig->tag(HtmlOutputFormatter::class, OutputFormatterInterface::class);
}
