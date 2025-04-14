<?php

namespace Survos\GoogleSheetsBundle;

use Google\Service\Sheets;
use Google\Service\Sheets\Sheet;
use Survos\GoogleSheetsBundle\Command\GoogleSheetsApiCommand;
use Survos\GoogleSheetsBundle\Service\GoogleApiClientService;
use Survos\GoogleSheetsBundle\Service\GoogleDriveService;
use Survos\GoogleSheetsBundle\Service\GoogleSheetsApiService;
use Survos\GoogleSheetsBundle\Service\SheetService;
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

        $builder->register($apiClientServiceId = 'survos_google_sheets.api_client_service', GoogleApiClientService::class)
            ->setArgument('$applicationName', $config['application_name'])
            ->setArgument('$credentials', $config['credentials'])
            ->setArgument('$clientSecret', $config['client_secret'])
            ->setPublic(true)
            ->setAutowired(true)
            ;
        $container->services()->alias(GoogleApiClientService::class, $apiClientServiceId);
        $container->services()->alias(Sheets::class, 'google_sheets.sheets');

        $builder->register(GoogleDriveService::class)
            ->setPublic(true)
            ->setAutowired(true)
            ->setAutoconfigured(true);

        $builder->register($apiServiceId = 'google_sheets.sheets_api_service', GoogleSheetsApiService::class)
            ->setArgument('$clientService', new Reference($apiClientServiceId))
            ->setPublic(true)
            ->setAutowired(true);
        $container->services()->alias(GoogleSheetsApiService::class, $apiServiceId);

        $builder->register($sheetsServiceId = 'google_sheets.sheets', Sheets::class)
            ->setPublic(true)
            ->setAutowired(true);

        // for download
        $builder->autowire(SheetService::class)
            ->setAutowired(true)
            ->setPublic(true)
            ->setAutoconfigured(true)
            ->setArgument('$aliases', $config['aliases'])
        ;

        // GoogleSheetsApiCommand
        $builder->autowire(GoogleSheetsApiCommand::class)
            ->setAutowired(true)
            ->setPublic(true)
            ->setAutoconfigured(true)
            ->setArgument('$clientService', new Reference($apiClientServiceId))
            ->setArgument('$googleSheetsApiService', new Reference($apiClientServiceId))
            ->addTag('console.command')
        ;


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
            ->arrayNode('aliases')
                ->arrayPrototype()
                    ->children()
                    ->scalarNode('code')->end()
                    ->scalarNode('url')->end()
                    ->end()
                ->end()
            ->end()
        ->end()
        ;

    }



}
