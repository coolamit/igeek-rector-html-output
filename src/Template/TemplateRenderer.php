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
        $tree = $this->buildTree($reportData->fileDiffs);
        $sortedFileDiffs = $this->flattenTree($tree);

        $placeholders = [
            'FILE_COUNT' => count($reportData->fileDiffs),
            'TOTAL_ADDED' => $reportData->getTotalLinesAdded(),
            'TOTAL_REMOVED' => $reportData->getTotalLinesRemoved(),
            'TIMESTAMP' => $reportData->timestamp,
            'RUN_MODE' => $reportData->isDryRun ? 'Dry Run' : 'Applied',
            'RUN_MODE_CLASS' => $reportData->isDryRun ? 'run-mode-dry-run' : 'run-mode-applied',
            'SIDEBAR_NAV' => empty($tree) ? '<li class="empty-state">No files changed</li>' : $this->renderTree($tree),
            'FILES_CONTENT' => $this->buildFilesContent($sortedFileDiffs),
        ];

        return $this->placeholderReplacer->replace($template, $placeholders);
    }

    /**
     * @param  list<array{index: int, file: string, diff: string}>  $fileDiffs
     * @return array<string, mixed>
     */
    private function buildTree(array $fileDiffs): array
    {
        $tree = [];

        foreach ($fileDiffs as $fileData) {
            $parts = explode('/', $fileData['file']);
            $current = &$tree;

            for ($i = 0; $i < count($parts) - 1; $i++) {
                $folder = $parts[$i];

                if (! isset($current[$folder]) || ! is_array($current[$folder]) || isset($current[$folder]['__file'])) {
                    $current[$folder] = [];
                }

                $current = &$current[$folder];
            }

            $fileName = end($parts);
            $current[$fileName] = ['__file' => true, '__data' => $fileData];
        }

        return $tree;
    }

    /**
     * @param  array<string, mixed>  $tree
     * @return list<array{index: int, file: string, diff: string}>
     */
    private function flattenTree(array $tree): array
    {
        $result = [];

        $folders = [];
        $files = [];

        foreach ($tree as $name => $node) {
            if (is_array($node) && ! isset($node['__file'])) {
                $folders[$name] = $node;
            } else {
                $files[$name] = $node;
            }
        }

        ksort($folders);
        ksort($files);

        foreach ($folders as $children) {
            $result = array_merge($result, $this->flattenTree($children));
        }

        foreach ($files as $node) {
            $result[] = $node['__data'];
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $tree
     */
    private function renderTree(array $tree): string
    {
        $html = '';

        // Separate folders and files, render folders first
        $folders = [];
        $files = [];

        foreach ($tree as $name => $node) {
            if (is_array($node) && ! isset($node['__file'])) {
                $folders[$name] = $node;
            } else {
                $files[$name] = $node;
            }
        }

        ksort($folders);
        ksort($files);

        foreach ($folders as $folderName => $children) {
            $escapedName = htmlspecialchars((string) $folderName, ENT_QUOTES, 'UTF-8');
            $html .= sprintf(
                '<li class="tree-folder"><span class="folder-name"><svg class="folder-arrow" width="14" height="14"><use href="#icon-toggle"/></svg> %s</span><ul>%s</ul></li>',
                $escapedName,
                $this->renderTree($children),
            );
        }

        foreach ($files as $node) {
            $fileData = $node['__data'];
            $filename = htmlspecialchars($fileData['file'], ENT_QUOTES, 'UTF-8');
            $shortName = htmlspecialchars(basename($fileData['file']), ENT_QUOTES, 'UTF-8');
            $html .= sprintf(
                '<li class="tree-file"><a href="#file-%s" title="%s"><svg class="file-icon" width="14" height="14"><use href="#icon-file"/></svg> %s</a></li>',
                $fileData['index'],
                $filename,
                $shortName,
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
