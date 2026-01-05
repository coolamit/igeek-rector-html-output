<?php

declare(strict_types=1);

namespace Igeek\RectorHtmlOutput\Template;

use Igeek\RectorHtmlOutput\ValueObject\ReportData;
use RuntimeException;

/**
 * Renders HTML reports using template files
 */
class TemplateRenderer
{
    public function __construct(
        private readonly string $templatePath,
        private readonly PlaceholderReplacerInterface $placeholderReplacer,
    ) {}

    public function render(ReportData $reportData): string
    {
        $template = $this->loadTemplate($this->templatePath);

        // Only placeholders that exist in the actual template
        $placeholders = [
            'FILE_COUNT' => count($reportData->fileDiffs),
            'TOTAL_ADDED' => $reportData->getTotalLinesAdded(),
            'TOTAL_REMOVED' => $reportData->getTotalLinesRemoved(),
            'TIMESTAMP' => $reportData->timestamp,
            'SIDEBAR_NAV' => $this->buildSidebarNav($reportData->fileDiffs),
            'FILES_CONTENT' => $this->buildFilesContent($reportData->fileDiffs),
        ];

        return $this->placeholderReplacer->replace($template, $placeholders);
    }

    /**
     * @param  list<array{index: int, file: string, diff: string}>  $fileDiffs
     */
    private function buildSidebarNav(array $fileDiffs): string
    {
        if (empty($fileDiffs)) {
            return '<li class="empty-state">No files changed</li>';
        }

        $html = '';

        foreach ($fileDiffs as $fileData) {
            $filename = htmlspecialchars($fileData['file'], ENT_QUOTES, 'UTF-8');
            $shortName = basename($filename);
            $html .= sprintf(
                '<li><a href="#file-%s" title="%s">%s</a></li>',
                $fileData['index'],
                $filename,
                htmlspecialchars($shortName, ENT_QUOTES, 'UTF-8'),
            );
        }

        return $html;
    }

    /**
     * @param  list<array{index: int, file: string, diff: string}>  $fileDiffs
     */
    private function buildFilesContent(array $fileDiffs): string
    {
        if (empty($fileDiffs)) {
            return $this->loadFragment('no-changes');
        }

        $html = '';

        foreach ($fileDiffs as $fileData) {
            $html .= $this->renderFileSection($fileData);
        }

        return $html;
    }

    /**
     * @param  array{index: int, file: string, diff: string}  $fileData
     */
    private function renderFileSection(array $fileData): string
    {
        $filename = htmlspecialchars($fileData['file'], ENT_QUOTES, 'UTF-8');
        $diffHtml = $this->formatDiff($fileData['diff']);

        $fragmentTemplate = $this->loadFragment('file-diff');

        return $this->placeholderReplacer->replace($fragmentTemplate, [
            'INDEX' => $fileData['index'],
            'FILENAME' => $filename,
            'DIFF_HTML' => $diffHtml,
        ]);
    }

    /**
     * Format unified diff into HTML with line numbers and syntax highlighting
     */
    private function formatDiff(string $diff): string
    {
        $lines = explode("\n", $diff);
        $html = '';
        $lineNumber = 0;

        foreach ($lines as $line) {
            $escaped = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');

            if (str_starts_with($line, '---') || str_starts_with($line, '+++')) {
                $html .= sprintf('<div class="diff-header"><span class="line-num"></span>%s</div>', $escaped);
            } elseif (str_starts_with($line, '@@')) {
                $html .= sprintf('<div class="diff-meta"><span class="line-num"></span>%s</div>', $escaped);

                /*
                 * Extract line number from @@ -18,8 +18,8 @@ (using OLD file line numbers)
                 *
                 * For NEW file line numbers, the pattern will be: '/@@ -\d+(?:,\d+)? \+(\d+)/'
                 */
                if (preg_match('/@@ -(\d+)/', $line, $matches)) {
                    $lineNumber = (int) $matches[1] - 1;
                }
            } elseif (str_starts_with($line, '-')) {
                $lineNumber++;
                $html .= sprintf(
                    '<div class="diff-removed"><span class="line-num">%s</span>%s</div>',
                    $lineNumber,
                    $escaped,
                );
            } elseif (str_starts_with($line, '+')) {
                $html .= sprintf('<div class="diff-added"><span class="line-num">+</span>%s</div>', $escaped);
            } else {
                $lineNumber++;
                $html .= sprintf(
                    '<div class="diff-context"><span class="line-num">%s</span>%s</div>',
                    $lineNumber,
                    $escaped,
                );
            }
        }

        return $html;
    }

    private function loadTemplate(string $path): string
    {
        $content = @file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException(sprintf('Failed to load template: %s', $path));
        }

        return $content;
    }

    private function loadFragment(string $fragmentName): string
    {
        $fragmentsDir = dirname($this->templatePath) . DIRECTORY_SEPARATOR . 'fragments';
        $fragmentPath = $fragmentsDir . DIRECTORY_SEPARATOR . $fragmentName . '.html';

        if (! file_exists($fragmentPath)) {
            throw new RuntimeException(
                sprintf('Template fragment not found: %s', $fragmentPath),
            );
        }

        return $this->loadTemplate($fragmentPath);
    }
}
