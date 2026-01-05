<?php

declare(strict_types=1);

namespace Igeek\RectorHtmlOutput;

use Rector\ValueObject\Reporting\FileDiff;
use Igeek\RectorHtmlOutput\Config\HtmlOutputConfig;
use Igeek\RectorHtmlOutput\Template\TemplateRenderer;
use Igeek\RectorHtmlOutput\ValueObject\ReportData;
use Rector\ChangesReporting\Contract\Output\OutputFormatterInterface;
use Rector\ValueObject\Configuration;
use Rector\ValueObject\ProcessResult;
use RuntimeException;

/**
 * HTML output formatter for Rector PHP
 */
class HtmlOutputFormatter implements OutputFormatterInterface
{
    public const string NAME = 'html';

    public function __construct(
        private readonly TemplateRenderer $templateRenderer,
        private readonly HtmlOutputConfig $config,
    ) {}

    public function getName(): string
    {
        return self::NAME;
    }

    public function report(ProcessResult $processResult, Configuration $configuration): void
    {
        // Get only files with changes (default behavior, parameter = true)
        $fileDiffs = $processResult->getFileDiffs(true);

        // Don't generate report if no changes and configured to skip
        if ($fileDiffs === [] && $this->config->shouldSkipEmptyReports()) {
            echo 'No changes detected. HTML report not generated.';
            echo PHP_EOL;

            return;
        }

        $reportData = $this->buildReportData($fileDiffs, $configuration);
        $html = $this->templateRenderer->render($reportData);

        $outputPath = $this->determineOutputPath();

        $bytesWritten = file_put_contents($outputPath, $html);

        if ($bytesWritten === false) {
            throw new RuntimeException(
                sprintf('Failed to write HTML report to: %s', $outputPath)
            );
        }

        echo sprintf('HTML report generated: %s', $outputPath).PHP_EOL;
        echo sprintf('  Files changed: %d', count($fileDiffs)).PHP_EOL;
        echo sprintf('  Lines added: +%d', $reportData->getTotalLinesAdded()).PHP_EOL;
        echo sprintf('  Lines removed: -%d', $reportData->getTotalLinesRemoved()).PHP_EOL;
    }

    /**
     * @param array<FileDiff> $fileDiffs
     */
    private function buildReportData(array $fileDiffs, Configuration $configuration): ReportData
    {
        $useAbsolutePaths = $configuration->isReportingWithRealPath();

        $filesData = [];

        foreach ($fileDiffs as $index => $fileDiff) {
            // Fallback to relative path if absolute path is null
            $filePath = $useAbsolutePaths
                ? ($fileDiff->getAbsoluteFilePath() ?? $fileDiff->getRelativeFilePath())
                : $fileDiff->getRelativeFilePath();

            $filesData[] = [
                'index' => $index,
                'file' => $filePath,
                'diff' => $fileDiff->getDiff(),
            ];
        }

        return new ReportData(
            fileDiffs: $filesData,
            timestamp: date('Y-m-d H:i:s'),
        );
    }

    private function determineOutputPath(): string
    {
        $outputDir = $this->config->getOutputDirectory();
        $filename = $this->config->getFilename();

        if (! str_ends_with($filename, '.html')) {
            $filename .= '.html';
        }

        $fullPath = $outputDir.DIRECTORY_SEPARATOR.$filename;

        // Auto-increment if file exists and config allows
        if ($this->config->shouldAutoIncrementFilename() && file_exists($fullPath)) {
            $counter = 2;
            $maxAttempts = 1000;
            $basename = pathinfo($filename, PATHINFO_FILENAME);

            do {
                $filename = sprintf('%s-%d.html', $basename, $counter);
                $fullPath = $outputDir.DIRECTORY_SEPARATOR.$filename;

                if ($counter++ > $maxAttempts) {
                    throw new RuntimeException(
                        sprintf('Too many report files exist in %s (max: %d)', $outputDir, $maxAttempts)
                    );
                }
            } while (file_exists($fullPath));
        }

        return $fullPath;
    }
}
