<?php

return [
    'name'        => 'Twig Enhancements',
    'description' => 'Enables Twig templating in emails for conditionals, loops, and filters with API token data',
    'version'     => '1.0.0',
    'author'      => 'Ryon Whyte',

    'services' => [
        'events' => [
            'mautic.twig_enhancements.email.subscriber' => [
                'class'     => \MauticPlugin\MauticTwigEnhancementsBundle\EventListener\EmailSubscriber::class,
                'arguments' => [
                    'mautic.twig_enhancements.helper.processor',
                    'monolog.logger.mautic',
                ],
            ],
        ],
        'other' => [
            'mautic.twig_enhancements.helper.processor' => [
                'class'     => \MauticPlugin\MauticTwigEnhancementsBundle\Helper\TwigProcessor::class,
                'arguments' => [
                    'twig',
                    'monolog.logger.mautic',
                ],
            ],
        ],
    ],
];
