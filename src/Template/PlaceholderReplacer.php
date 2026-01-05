<?php

declare(strict_types=1);

namespace Igeek\RectorHtmlOutput\Template;

/**
 * Simple template placeholder replacement utility
 */
class PlaceholderReplacer implements PlaceholderReplacerInterface
{
    /**
     * Replace placeholders in template with values
     *
     * Placeholders format: {{VARIABLE_NAME}}
     *
     * @param  array<string, string|int>  $placeholders
     */
    public function replace(string $template, array $placeholders): string
    {
        if (empty($placeholders)) {
            return $template;
        }

        foreach ($placeholders as $key => $value) {
            $placeholder = sprintf('{{%s}}', strtoupper($key));
            $template = str_replace($placeholder, (string) $value, $template);
        }

        return $template;
    }
}
