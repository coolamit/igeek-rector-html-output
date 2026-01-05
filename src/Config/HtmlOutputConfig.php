<?php

declare(strict_types=1);

namespace Igeek\RectorHtmlOutput\Config;

use RuntimeException;

/**
 * Configuration value object for HTML formatter
 */
class HtmlOutputConfig
{
    public function __construct(
        private readonly string $outputDirectory,
        private readonly string $filename = 'rector-report',
        private readonly bool $autoIncrementFilename = true,
        private readonly bool $skipEmptyReports = true,
    ) {
        $this->ensureOutputDirectoryExists();
    }

    public function getOutputDirectory(): string
    {
        return $this->outputDirectory;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getTemplatePath(): string
    {
        return dirname(__DIR__, 2) . '/templates/default/main.html';
    }

    public function shouldAutoIncrementFilename(): bool
    {
        return $this->autoIncrementFilename;
    }

    public function shouldSkipEmptyReports(): bool
    {
        return $this->skipEmptyReports;
    }

    /**
     * Auto-create output directory if it doesn't exist
     */
    private function ensureOutputDirectoryExists(): void
    {
        if (empty($this->outputDirectory)) {
            throw new RuntimeException('Output directory cannot be empty');
        }

        if (! is_dir($this->outputDirectory)) {
            if (! mkdir($this->outputDirectory, 0o755, true) && ! is_dir($this->outputDirectory)) {
                throw new RuntimeException(
                    sprintf('Failed to create output directory: %s', $this->outputDirectory),
                );
            }
        }

        if (! is_writable($this->outputDirectory)) {
            throw new RuntimeException(
                sprintf('Output directory is not writable: %s', $this->outputDirectory),
            );
        }
    }
}
