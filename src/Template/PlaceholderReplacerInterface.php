<?php

declare(strict_types=1);

namespace Igeek\RectorHtmlOutput\Template;

/**
 * Interface for template placeholder replacement
 *
 * Allows users to provide custom implementations for placeholder replacement logic.
 */
interface PlaceholderReplacerInterface
{
    /**
     * Replace placeholders in template with values
     *
     * Placeholders format: {{VARIABLE_NAME}}
     *
     * @param  array<string, string|int>  $placeholders
     */
    public function replace(string $template, array $placeholders): string;
}
