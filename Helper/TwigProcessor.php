<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTwigEnhancementsBundle\Helper;

use Psr\Log\LoggerInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class TwigProcessor
{
    private Environment $twig;
    private LoggerInterface $logger;
    private ?Environment $lenientTwig = null;

    public function __construct(Environment $twig, LoggerInterface $logger)
    {
        $this->twig = $twig;
        $this->logger = $logger;
    }

    /**
     * Get a lenient Twig environment with strict_variables disabled.
     * This allows undefined variables to be handled gracefully.
     */
    private function getLenientTwig(): Environment
    {
        if ($this->lenientTwig === null) {
            $this->lenientTwig = new Environment(new ArrayLoader(), [
                'strict_variables' => false,
                'autoescape'       => false,
            ]);

            // Copy extensions from main Twig environment
            foreach ($this->twig->getExtensions() as $extension) {
                $name = get_class($extension);
                if (!$this->lenientTwig->hasExtension($name)) {
                    try {
                        $this->lenientTwig->addExtension($extension);
                    } catch (\Throwable $e) {
                        // Some extensions may not be re-addable, skip them
                    }
                }
            }
        }

        return $this->lenientTwig;
    }

    /**
     * Process content through Twig template engine.
     *
     * @param string $content The email content (HTML or plain text)
     * @param array  $tokens  API tokens passed to the email (e.g., {tokenName} => value)
     * @param array  $lead    Lead/contact data
     *
     * @return string Processed content, or original content if processing fails
     */
    public function process(string $content, array $tokens, array $lead): string
    {
        // Quick check - skip processing if no Twig syntax detected
        if (!$this->hasTwigSyntax($content)) {
            return $content;
        }

        // Fix GrapeJS HTML entity encoding in Twig tags
        $content = $this->fixHtmlEntities($content);

        // Prepare variables for Twig context
        $twigVars = $this->prepareVariables($tokens, $lead);

        // Render through Twig with error handling
        try {
            // Use lenient Twig environment to allow undefined variables
            $twig = $this->getLenientTwig();
            $template = $twig->createTemplate($content);

            return $template->render($twigVars);
        } catch (\Throwable $e) {
            $this->logger->error('TwigEnhancements: Template processing failed', [
                'error'   => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            // Return original content so email still sends
            return $content;
        }
    }

    /**
     * Check if content contains Twig syntax.
     */
    private function hasTwigSyntax(string $content): bool
    {
        // Check for {{ }}, {% %}, or {# #} syntax
        return (bool) preg_match('/\{\{|\{%|\{#/', $content);
    }

    /**
     * Fix GrapeJS HTML entity encoding within Twig tags.
     *
     * GrapeJS converts < and > to &lt; and &gt; which breaks comparisons.
     * This converts them back within {% %} blocks.
     */
    private function fixHtmlEntities(string $content): string
    {
        // Fix entities inside {% ... %} blocks (control structures)
        $content = preg_replace_callback(
            '/\{%.*?%\}/s',
            fn (array $matches): string => html_entity_decode($matches[0], ENT_QUOTES | ENT_HTML5),
            $content
        );

        // Fix entities inside {{ ... }} blocks (output)
        $content = preg_replace_callback(
            '/\{\{.*?\}\}/s',
            fn (array $matches): string => html_entity_decode($matches[0], ENT_QUOTES | ENT_HTML5),
            $content
        );

        return $content;
    }

    /**
     * Prepare variables for Twig context.
     *
     * Makes tokens available as both direct variables and via 'tokens' object.
     * Lead data is available via 'lead' object.
     *
     * @param array $tokens API tokens in {name} => value format
     * @param array $lead   Lead/contact data
     *
     * @return array Variables for Twig context
     */
    private function prepareVariables(array $tokens, array $lead): array
    {
        $vars = [];

        // Process tokens - strip curly braces and make available as direct vars
        $cleanTokens = [];
        foreach ($tokens as $key => $value) {
            // Remove { and } from token name: {orderTotal} -> orderTotal
            $cleanKey = trim($key, '{}');
            $cleanTokens[$cleanKey] = $value;

            // Also make available as direct variable
            $vars[$cleanKey] = $value;
        }

        // Add tokens as a collection for {{ tokens.name }} access
        $vars['tokens'] = $cleanTokens;

        // Add lead data for {{ lead.firstname }} access
        $vars['lead'] = $lead;

        // Add contact as alias for lead (common terminology)
        $vars['contact'] = $lead;

        return $vars;
    }
}
