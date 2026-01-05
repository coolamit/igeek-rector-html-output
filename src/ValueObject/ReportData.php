<?php

declare(strict_types=1);

namespace Igeek\RectorHtmlOutput\ValueObject;

/**
 * Data transfer object for HTML report generation
 */
class ReportData
{
    private ?int $cachedLinesAdded = null;

    private ?int $cachedLinesRemoved = null;

    /**
     * @param  list<array{index: int, file: string, diff: string}>  $fileDiffs
     */
    public function __construct(
        public readonly array $fileDiffs,
        public readonly string $timestamp,
    ) {}

    public function getTotalLinesAdded(): int
    {
        $this->calculateLineCountsIfNeeded();

        return $this->cachedLinesAdded;
    }

    public function getTotalLinesRemoved(): int
    {
        $this->calculateLineCountsIfNeeded();

        return $this->cachedLinesRemoved;
    }

    public function hasChanges(): bool
    {
        return $this->fileDiffs !== [];
    }

    /**
     * Calculate both line counts in a single pass (lazy evaluation with caching)
     */
    private function calculateLineCountsIfNeeded(): void
    {
        if ($this->cachedLinesAdded !== null) {
            return;
        }

        $added = 0;
        $removed = 0;

        foreach ($this->fileDiffs as $fileData) {
            $lines = explode("\n", $fileData['diff']);

            foreach ($lines as $line) {
                if (str_starts_with($line, '+') && ! str_starts_with($line, '+++')) {
                    $added++;
                } elseif (str_starts_with($line, '-') && ! str_starts_with($line, '---')) {
                    $removed++;
                }
            }
        }

        $this->cachedLinesAdded = $added;
        $this->cachedLinesRemoved = $removed;
    }
}
