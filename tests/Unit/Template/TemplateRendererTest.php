<?php

declare(strict_types=1);

namespace Igeek\RectorHtmlOutput\Tests\Unit\Template;

use Igeek\RectorHtmlOutput\Template\PlaceholderReplacer;
use Igeek\RectorHtmlOutput\Template\TemplateRenderer;
use Igeek\RectorHtmlOutput\ValueObject\ReportData;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TemplateRendererTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/rector-renderer-test-' . uniqid();
        mkdir($this->tempDir);
        mkdir($this->tempDir . '/fragments');
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/fragments/*') ?: [];
        array_map('unlink', $files);

        $files = glob($this->tempDir . '/*') ?: [];
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        if (is_dir($this->tempDir . '/fragments')) {
            rmdir($this->tempDir . '/fragments');
        }

        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    #[Test]
    public function renderReplacesBasicPlaceholders(): void
    {
        $templatePath = $this->tempDir . '/main.html';
        file_put_contents($templatePath, '{{FILE_COUNT}} files, +{{TOTAL_ADDED}} -{{TOTAL_REMOVED}} at {{TIMESTAMP}}');
        file_put_contents($this->tempDir . '/fragments/no-changes.html', '<p>No changes</p>');

        $renderer = new TemplateRenderer($templatePath, new PlaceholderReplacer());

        $reportData = new ReportData(
            fileDiffs: [],
            timestamp: '2025-01-01 12:00:00',
        );

        $result = $renderer->render($reportData);

        $this->assertStringContainsString('0 files', $result);
        $this->assertStringContainsString('+0', $result);
        $this->assertStringContainsString('-0', $result);
        $this->assertStringContainsString('2025-01-01 12:00:00', $result);
    }

    #[Test]
    public function renderBuildsSidebarNavFromFileDiffs(): void
    {
        $templatePath = $this->tempDir . '/main.html';
        file_put_contents($templatePath, '{{SIDEBAR_NAV}}');
        file_put_contents($this->tempDir . '/fragments/file-diff.html', '<div>{{FILENAME}}</div>');

        $renderer = new TemplateRenderer($templatePath, new PlaceholderReplacer());

        $reportData = new ReportData(
            fileDiffs: [
                ['index' => 0, 'file' => 'app/Models/User.php', 'diff' => '+line'],
                ['index' => 1, 'file' => 'app/Http/Controllers/HomeController.php', 'diff' => '-line'],
            ],
            timestamp: '2025-01-01 12:00:00',
        );

        $result = $renderer->render($reportData);

        $this->assertStringContainsString('href="#file-0"', $result);
        $this->assertStringContainsString('href="#file-1"', $result);
        $this->assertStringContainsString('User.php', $result);
        $this->assertStringContainsString('HomeController.php', $result);
        $this->assertStringContainsString('tree-folder', $result);
        $this->assertStringContainsString('tree-file', $result);
        $this->assertStringContainsString('folder-name', $result);
    }

    #[Test]
    public function renderBuildsFilesContentFromFileDiffs(): void
    {
        $templatePath = $this->tempDir . '/main.html';
        file_put_contents($templatePath, '{{FILES_CONTENT}}');
        file_put_contents(
            $this->tempDir . '/fragments/file-diff.html',
            '<div id="file-{{INDEX}}"><h2>{{FILENAME}}</h2><pre>{{DIFF_HTML}}</pre></div>',
        );

        $renderer = new TemplateRenderer($templatePath, new PlaceholderReplacer());

        $reportData = new ReportData(
            fileDiffs: [
                ['index' => 0, 'file' => 'test.php', 'diff' => "+new line\n-old line"],
            ],
            timestamp: '2025-01-01 12:00:00',
        );

        $result = $renderer->render($reportData);

        $this->assertStringContainsString('id="file-0"', $result);
        $this->assertStringContainsString('test.php', $result);
        $this->assertStringContainsString('diff-added', $result);
        $this->assertStringContainsString('diff-removed', $result);
    }

    #[Test]
    public function renderShowsNoChangesFragmentWhenEmpty(): void
    {
        $templatePath = $this->tempDir . '/main.html';
        file_put_contents($templatePath, '{{FILES_CONTENT}}');
        file_put_contents($this->tempDir . '/fragments/no-changes.html', '<div class="no-changes">All good!</div>');

        $renderer = new TemplateRenderer($templatePath, new PlaceholderReplacer());

        $reportData = new ReportData(
            fileDiffs: [],
            timestamp: '2025-01-01 12:00:00',
        );

        $result = $renderer->render($reportData);

        $this->assertStringContainsString('no-changes', $result);
        $this->assertStringContainsString('All good!', $result);
    }

    #[Test]
    public function renderThrowsExceptionForMissingTemplate(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to load template');

        $renderer = new TemplateRenderer('/nonexistent/path.html', new PlaceholderReplacer());

        $reportData = new ReportData(
            fileDiffs: [],
            timestamp: '2025-01-01 12:00:00',
        );

        $renderer->render($reportData);
    }

    #[Test]
    public function renderThrowsExceptionForMissingFragment(): void
    {
        $templatePath = $this->tempDir . '/main.html';
        file_put_contents($templatePath, '{{FILES_CONTENT}}');
        // Note: not creating the no-changes.html fragment

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Template fragment not found');

        $renderer = new TemplateRenderer($templatePath, new PlaceholderReplacer());

        $reportData = new ReportData(
            fileDiffs: [],
            timestamp: '2025-01-01 12:00:00',
        );

        $renderer->render($reportData);
    }

    #[Test]
    public function renderEscapesHtmlInFilenames(): void
    {
        $templatePath = $this->tempDir . '/main.html';
        file_put_contents($templatePath, '{{SIDEBAR_NAV}}');
        file_put_contents($this->tempDir . '/fragments/file-diff.html', '<div>{{FILENAME}}</div>');

        $renderer = new TemplateRenderer($templatePath, new PlaceholderReplacer());

        $reportData = new ReportData(
            fileDiffs: [
                ['index' => 0, 'file' => 'app/<script>alert("xss")</script>.php', 'diff' => '+line'],
            ],
            timestamp: '2025-01-01 12:00:00',
        );

        $result = $renderer->render($reportData);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    #[Test]
    public function renderFormatsDiffWithLineNumbers(): void
    {
        $templatePath = $this->tempDir . '/main.html';
        file_put_contents($templatePath, '{{FILES_CONTENT}}');
        file_put_contents($this->tempDir . '/fragments/file-diff.html', '{{DIFF_HTML}}');

        $renderer = new TemplateRenderer($templatePath, new PlaceholderReplacer());

        $diff = <<<'DIFF'
            --- a/test.php
            +++ b/test.php
            @@ -10,3 +10,3 @@
             context line
            -removed line
            +added line
            DIFF;

        $reportData = new ReportData(
            fileDiffs: [
                ['index' => 0, 'file' => 'test.php', 'diff' => $diff],
            ],
            timestamp: '2025-01-01 12:00:00',
        );

        $result = $renderer->render($reportData);

        $this->assertStringContainsString('diff-header', $result);
        $this->assertStringContainsString('diff-meta', $result);
        $this->assertStringContainsString('diff-context', $result);
        $this->assertStringContainsString('diff-removed', $result);
        $this->assertStringContainsString('diff-added', $result);
        $this->assertStringContainsString('line-num', $result);
    }

    #[Test]
    public function renderOutputsDryRunMode(): void
    {
        $templatePath = $this->tempDir . '/main.html';
        file_put_contents($templatePath, '{{RUN_MODE}} {{RUN_MODE_CLASS}}');
        file_put_contents($this->tempDir . '/fragments/no-changes.html', '');

        $renderer = new TemplateRenderer($templatePath, new PlaceholderReplacer());

        $reportData = new ReportData(
            fileDiffs: [],
            timestamp: '2025-01-01 12:00:00',
            isDryRun: true,
        );

        $result = $renderer->render($reportData);

        $this->assertStringContainsString('Dry Run', $result);
        $this->assertStringContainsString('run-mode-dry-run', $result);
    }

    #[Test]
    public function renderOutputsAppliedMode(): void
    {
        $templatePath = $this->tempDir . '/main.html';
        file_put_contents($templatePath, '{{RUN_MODE}} {{RUN_MODE_CLASS}}');
        file_put_contents($this->tempDir . '/fragments/no-changes.html', '');

        $renderer = new TemplateRenderer($templatePath, new PlaceholderReplacer());

        $reportData = new ReportData(
            fileDiffs: [],
            timestamp: '2025-01-01 12:00:00',
            isDryRun: false,
        );

        $result = $renderer->render($reportData);

        $this->assertStringContainsString('Applied', $result);
        $this->assertStringContainsString('run-mode-applied', $result);
    }

    #[Test]
    public function renderBuildsFolderTreeInSidebar(): void
    {
        $templatePath = $this->tempDir . '/main.html';
        file_put_contents($templatePath, '{{SIDEBAR_NAV}}');
        file_put_contents($this->tempDir . '/fragments/file-diff.html', '<div>{{FILENAME}}</div>');

        $renderer = new TemplateRenderer($templatePath, new PlaceholderReplacer());

        $reportData = new ReportData(
            fileDiffs: [
                ['index' => 0, 'file' => 'app/Models/User.php', 'diff' => '+line'],
                ['index' => 1, 'file' => 'config/app.php', 'diff' => '+line'],
            ],
            timestamp: '2025-01-01 12:00:00',
        );

        $result = $renderer->render($reportData);

        // Folders should be rendered as tree-folder with folder-name spans
        $this->assertStringContainsString('class="tree-folder"', $result);
        $this->assertStringContainsString('class="folder-name"', $result);
        // Files should be rendered as tree-file
        $this->assertStringContainsString('class="tree-file"', $result);
        // Folder names should appear
        $this->assertStringContainsString('app', $result);
        $this->assertStringContainsString('Models', $result);
        $this->assertStringContainsString('config', $result);
    }

    #[Test]
    public function renderSortsFilesContentToMatchSidebarOrder(): void
    {
        $templatePath = $this->tempDir . '/main.html';
        file_put_contents($templatePath, '{{FILES_CONTENT}}');
        file_put_contents(
            $this->tempDir . '/fragments/file-diff.html',
            '<section>{{FILENAME}}</section>',
        );

        $renderer = new TemplateRenderer($templatePath, new PlaceholderReplacer());

        // rootfile.php given first, but folders should be output before root-level files
        $reportData = new ReportData(
            fileDiffs: [
                ['index' => 0, 'file' => 'rootfile.php', 'diff' => '+line'],
                ['index' => 1, 'file' => 'app/Models/User.php', 'diff' => '+line'],
            ],
            timestamp: '2025-01-01 12:00:00',
        );

        $result = $renderer->render($reportData);

        // Tree order: app/ folder contents first, then root-level files
        $posApp = strpos($result, 'app/Models/User.php');
        $posRoot = strpos($result, 'rootfile.php');

        $this->assertNotFalse($posApp);
        $this->assertNotFalse($posRoot);
        $this->assertLessThan($posRoot, $posApp, 'Files inside folders should come before root-level files');
    }

    #[Test]
    public function renderSortsFoldersAndFilesAlphabetically(): void
    {
        $templatePath = $this->tempDir . '/main.html';
        file_put_contents($templatePath, '{{SIDEBAR_NAV}}{{FILES_CONTENT}}');
        file_put_contents(
            $this->tempDir . '/fragments/file-diff.html',
            '<section>{{FILENAME}}</section>',
        );

        $renderer = new TemplateRenderer($templatePath, new PlaceholderReplacer());

        // Input order is intentionally non-alphabetical
        $reportData = new ReportData(
            fileDiffs: [
                ['index' => 0, 'file' => 'app/Providers/AppServiceProvider.php', 'diff' => '+line'],
                ['index' => 1, 'file' => 'app/Models/User.php', 'diff' => '+line'],
                ['index' => 2, 'file' => 'app/Concerns/FooBarable.php', 'diff' => '+line'],
                ['index' => 3, 'file' => 'app/Http/Controllers/ListItemsController.php', 'diff' => '+line'],
            ],
            timestamp: '2025-01-01 12:00:00',
        );

        $result = $renderer->render($reportData);

        // Sub-folders under app/ should appear in alphabetical order: Concerns, Http, Models, Providers
        $posConcerns = strpos($result, 'FooBarable.php');
        $posHttp = strpos($result, 'ListItemsController.php');
        $posModels = strpos($result, 'User.php');
        $posProviders = strpos($result, 'AppServiceProvider.php');

        $this->assertNotFalse($posConcerns);
        $this->assertNotFalse($posHttp);
        $this->assertNotFalse($posModels);
        $this->assertNotFalse($posProviders);
        $this->assertLessThan($posHttp, $posConcerns, 'Concerns should come before Http (alphabetical)');
        $this->assertLessThan($posModels, $posHttp, 'Http should come before Models (alphabetical)');
        $this->assertLessThan($posProviders, $posModels, 'Models should come before Providers (alphabetical)');
    }

    #[Test]
    public function renderSortsFilesAlphabeticallyWithinSameFolder(): void
    {
        $templatePath = $this->tempDir . '/main.html';
        file_put_contents($templatePath, '{{FILES_CONTENT}}');
        file_put_contents(
            $this->tempDir . '/fragments/file-diff.html',
            '<section>{{FILENAME}}</section>',
        );

        $renderer = new TemplateRenderer($templatePath, new PlaceholderReplacer());

        // Files in non-alphabetical input order, all in same folder
        $reportData = new ReportData(
            fileDiffs: [
                ['index' => 0, 'file' => 'app/Providers/FortifyServiceProvider.php', 'diff' => '+line'],
                ['index' => 1, 'file' => 'app/Providers/AppServiceProvider.php', 'diff' => '+line'],
            ],
            timestamp: '2025-01-01 12:00:00',
        );

        $result = $renderer->render($reportData);

        $posApp = strpos($result, 'AppServiceProvider.php');
        $posFortify = strpos($result, 'FortifyServiceProvider.php');

        $this->assertNotFalse($posApp);
        $this->assertNotFalse($posFortify);
        $this->assertLessThan($posFortify, $posApp, 'AppServiceProvider should come before FortifyServiceProvider (alphabetical)');
    }

    #[Test]
    public function renderShowsEmptyStateWhenNoFiles(): void
    {
        $templatePath = $this->tempDir . '/main.html';
        file_put_contents($templatePath, '{{SIDEBAR_NAV}}');
        file_put_contents($this->tempDir . '/fragments/no-changes.html', '');

        $renderer = new TemplateRenderer($templatePath, new PlaceholderReplacer());

        $reportData = new ReportData(
            fileDiffs: [],
            timestamp: '2025-01-01 12:00:00',
        );

        $result = $renderer->render($reportData);

        $this->assertStringContainsString('No files changed', $result);
    }
}
