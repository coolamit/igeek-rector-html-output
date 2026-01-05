<?php

declare(strict_types=1);

namespace Igeek\RectorHtmlOutput\Tests\Unit;

use Igeek\RectorHtmlOutput\Config\HtmlOutputConfig;
use Igeek\RectorHtmlOutput\HtmlOutputFormatter;
use Igeek\RectorHtmlOutput\Template\PlaceholderReplacer;
use Igeek\RectorHtmlOutput\Template\TemplateRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Rector\ValueObject\Configuration;
use Rector\ValueObject\ProcessResult;

final class HtmlOutputFormatterTest extends TestCase
{
    private string $tempDir;

    private string $templateDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/rector-formatter-test-' . uniqid();
        $this->templateDir = sys_get_temp_dir() . '/rector-templates-' . uniqid();

        mkdir($this->tempDir);
        mkdir($this->templateDir);
        mkdir($this->templateDir . '/fragments');

        // Create minimal test templates
        file_put_contents(
            $this->templateDir . '/main.html',
            '<html>{{FILE_COUNT}} files{{SIDEBAR_NAV}}{{FILES_CONTENT}}</html>',
        );
        file_put_contents(
            $this->templateDir . '/fragments/file-diff.html',
            '<div>{{FILENAME}}</div>',
        );
        file_put_contents(
            $this->templateDir . '/fragments/no-changes.html',
            '<div>No changes</div>',
        );
    }

    protected function tearDown(): void
    {
        // Clean temp dir
        array_map('unlink', glob($this->tempDir . '/*') ?: []);
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }

        // Clean template dir
        array_map('unlink', glob($this->templateDir . '/fragments/*') ?: []);
        if (is_dir($this->templateDir . '/fragments')) {
            rmdir($this->templateDir . '/fragments');
        }
        foreach (glob($this->templateDir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->templateDir)) {
            rmdir($this->templateDir);
        }
    }

    #[Test]
    public function getNameReturnsHtml(): void
    {
        $formatter = $this->createFormatter();

        $this->assertSame('html', $formatter->getName());
    }

    #[Test]
    public function reportGeneratesHtmlFile(): void
    {
        $formatter = $this->createFormatter(skipEmpty: false);

        // Use real instances - ProcessResult(systemErrors, fileDiffs, totalChanged)
        $processResult = new ProcessResult([], [], 0);
        $configuration = new Configuration();

        ob_start();
        $formatter->report($processResult, $configuration);
        ob_end_clean();

        $expectedFile = $this->tempDir . '/test-report.html';
        $this->assertFileExists($expectedFile);

        $content = file_get_contents($expectedFile);
        $this->assertIsString($content);
        $this->assertStringContainsString('<html>', $content);
        $this->assertStringContainsString('0 files', $content);
    }

    #[Test]
    public function reportSkipsEmptyReportsWhenConfigured(): void
    {
        $formatter = $this->createFormatter(skipEmpty: true);

        $processResult = new ProcessResult([], [], 0);
        $configuration = new Configuration();

        ob_start();
        $formatter->report($processResult, $configuration);
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('No changes detected', $output);
        $this->assertFileDoesNotExist($this->tempDir . '/test-report.html');
    }

    private function createFormatter(bool $skipEmpty = false): HtmlOutputFormatter
    {
        $config = new HtmlOutputConfig(
            outputDirectory: $this->tempDir,
            filename: 'test-report',
            skipEmptyReports: $skipEmpty,
        );

        $templateRenderer = new TemplateRenderer(
            $this->templateDir . '/main.html',
            new PlaceholderReplacer(),
        );

        return new HtmlOutputFormatter($templateRenderer, $config);
    }
}
