<?php

declare(strict_types=1);

namespace Igeek\RectorHtmlOutput\Tests\Unit\ValueObject;

use Igeek\RectorHtmlOutput\ValueObject\ReportData;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ReportDataTest extends TestCase
{
    #[Test]
    public function getTotalLinesAddedWithNoChanges(): void
    {
        $reportData = new ReportData(
            fileDiffs: [],
            timestamp: '2025-01-01 12:00:00',
        );

        $this->assertSame(0, $reportData->getTotalLinesAdded());
    }

    #[Test]
    public function getTotalLinesAddedWithChanges(): void
    {
        $reportData = new ReportData(
            fileDiffs: [
                [
                    'index' => 0,
                    'file' => 'test.php',
                    'diff' => "--- Original\n+++ New\n@@ -1,2 +1,3 @@\n line1\n+line2\n+line3",
                ],
            ],
            timestamp: '2025-01-01 12:00:00',
        );

        $this->assertSame(2, $reportData->getTotalLinesAdded());
    }

    #[Test]
    public function getTotalLinesRemovedWithChanges(): void
    {
        $reportData = new ReportData(
            fileDiffs: [
                [
                    'index' => 0,
                    'file' => 'test.php',
                    'diff' => "--- Original\n+++ New\n@@ -1,3 +1,1 @@\n-line1\n-line2\n line3",
                ],
            ],
            timestamp: '2025-01-01 12:00:00',
        );

        $this->assertSame(2, $reportData->getTotalLinesRemoved());
    }

    #[Test]
    public function hasChangesReturnsTrueWhenChangesExist(): void
    {
        $reportData = new ReportData(
            fileDiffs: [
                [
                    'index' => 0,
                    'file' => 'test.php',
                    'diff' => '+new line',
                ],
            ],
            timestamp: '2025-01-01 12:00:00',
        );

        $this->assertTrue($reportData->hasChanges());
    }

    #[Test]
    public function hasChangesReturnsFalseWhenEmpty(): void
    {
        $reportData = new ReportData(
            fileDiffs: [],
            timestamp: '2025-01-01 12:00:00',
        );

        $this->assertFalse($reportData->hasChanges());
    }

    #[Test]
    public function ignoresDiffHeadersWhenCountingLines(): void
    {
        $reportData = new ReportData(
            fileDiffs: [
                [
                    'index' => 0,
                    'file' => 'test.php',
                    'diff' => "--- a/test.php\n+++ b/test.php\n@@ -1,2 +1,2 @@\n-old\n+new",
                ],
            ],
            timestamp: '2025-01-01 12:00:00',
        );

        // Should count +new but not +++ b/test.php
        $this->assertSame(1, $reportData->getTotalLinesAdded());
        // Should count -old but not --- a/test.php
        $this->assertSame(1, $reportData->getTotalLinesRemoved());
    }
}
