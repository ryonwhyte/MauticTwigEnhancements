<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTwigEnhancementsBundle\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use MauticPlugin\MauticTwigEnhancementsBundle\Helper\TwigProcessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EmailSubscriber implements EventSubscriberInterface
{
    private TwigProcessor $twigProcessor;
    private LoggerInterface $logger;

    public function __construct(TwigProcessor $twigProcessor, LoggerInterface $logger)
    {
        $this->twigProcessor = $twigProcessor;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Priority 0 - run after Mautic's token replacement
            EmailEvents::EMAIL_ON_SEND    => ['onEmailGenerate', 0],
            EmailEvents::EMAIL_ON_DISPLAY => ['onEmailGenerate', 0],
        ];
    }

    /**
     * Process email content through Twig template engine.
     */
    public function onEmailGenerate(EmailSendEvent $event): void
    {
        // Get tokens passed to the email (API tokens, etc.)
        $tokens = $event->getTokens();

        // Get lead/contact data
        $lead = $event->getLead();
        $leadData = is_array($lead) ? $lead : [];

        // Process HTML content
        $content = $event->getContent();
        if (!empty($content)) {
            $processedContent = $this->twigProcessor->process($content, $tokens, $leadData);
            $event->setContent($processedContent);
        }

        // Process plain text content
        $plainText = $event->getPlainText();
        if (!empty($plainText)) {
            $processedPlainText = $this->twigProcessor->process($plainText, $tokens, $leadData);
            $event->setPlainText($processedPlainText);
        }

        // Process subject line
        $subject = $event->getSubject();
        if (!empty($subject)) {
            $processedSubject = $this->twigProcessor->process($subject, $tokens, $leadData);
            $event->setSubject($processedSubject);
        }
    }
}
