<?php

declare(strict_types=1);

namespace Igeek\RectorHtmlOutput\Tests\Unit\Config;

use Igeek\RectorHtmlOutput\Config\HtmlOutputConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class HtmlOutputConfigTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/rector-test-' . uniqid();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    #[Test]
    public function constructorCreatesDirectoryIfMissing(): void
    {
        $this->assertDirectoryDoesNotExist($this->tempDir);

        $config = new HtmlOutputConfig(
            outputDirectory: $this->tempDir,
        );

        $this->assertDirectoryExists($this->tempDir);
        $this->assertSame($this->tempDir, $config->getOutputDirectory());
    }

    #[Test]
    public function constructorThrowsExceptionForEmptyDirectory(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Output directory cannot be empty');

        new HtmlOutputConfig(
            outputDirectory: '',
        );
    }

    #[Test]
    public function getFilenameReturnsDefault(): void
    {
        $config = new HtmlOutputConfig(
            outputDirectory: $this->tempDir,
        );

        $this->assertSame('rector-report', $config->getFilename());
    }

    #[Test]
    public function getFilenameReturnsCustom(): void
    {
        $config = new HtmlOutputConfig(
            outputDirectory: $this->tempDir,
            filename: 'my-report',
        );

        $this->assertSame('my-report', $config->getFilename());
    }

    #[Test]
    public function shouldAutoIncrementFilenameDefaultsToTrue(): void
    {
        $config = new HtmlOutputConfig(
            outputDirectory: $this->tempDir,
        );

        $this->assertTrue($config->shouldAutoIncrementFilename());
    }

    #[Test]
    public function shouldSkipEmptyReportsDefaultsToTrue(): void
    {
        $config = new HtmlOutputConfig(
            outputDirectory: $this->tempDir,
        );

        $this->assertTrue($config->shouldSkipEmptyReports());
    }

    #[Test]
    public function getTemplatePathReturnsDefault(): void
    {
        $config = new HtmlOutputConfig(
            outputDirectory: $this->tempDir,
        );

        $templatePath = $config->getTemplatePath();
        $this->assertStringEndsWith('templates/default/main.html', $templatePath);
    }
}
