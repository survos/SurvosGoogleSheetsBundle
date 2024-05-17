<?php

namespace Survos\GoogleSheetsBundle;

use Survos\GoogleSheetsBundle\Service\GoogleApiClientService;
use Survos\GoogleSheetsBundle\Service\GoogleSheetsApiService;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class SurvosGoogleSheetsBundle
 *
 * @package Survos\GoogleSheetsBundle
 * services:
 * survos_google_sheets.api_client_service:
 * class: Survos\GoogleSheetsBundle\Service\GoogleApiClientService
 * arguments: ['%survos_google_sheets.application_name%', '%survos_google_sheets.credentials%', '%survos_google_sheets.client_secret%']
 * survos_google_sheets.sheets_service:
 * class: Survos\GoogleSheetsBundle\Service\GoogleSheetsApiService
 * arguments: ['@survos_google_sheets.api_client_service']
 */

class SurvosGoogleSheetsBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {

        $container->setParameter('survos_google_sheets.application_name', $config['application_name']);
        $container->setParameter('survos_google_sheets.credentials', $config['credentials']);
        $container->setParameter('survos_google_sheets.client_secret', $config['client_secret']);

        $builder->register('survos_google_sheets.api_client_service', GoogleApiClientService::class)
            ->setAutowired(true)
            ;

        $builder->register('survos_google_sheets.sheets_service', GoogleSheetsApiService::class)
            ->setAutowired(true);

    }

    public function configure(DefinitionConfigurator $definition): void
    {

        $definition->rootNode()
        ->children()
            ->scalarNode('application_name')
                ->isRequired()
                ->cannotBeEmpty()
            ->end()
            ->scalarNode('credentials')
                ->isRequired()
                ->cannotBeEmpty()
            ->end()
            ->scalarNode('client_secret')
                ->isRequired()
                ->cannotBeEmpty()
            ->end()
        ->end()
        ;

    }



}
