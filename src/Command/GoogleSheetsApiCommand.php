<?php

namespace Survos\GoogleSheetsBundle\Command;

use Survos\GoogleSheetsBundle\Service\GoogleApiClientService;
use Survos\GoogleSheetsBundle\Service\GoogleSheetsApiService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('googlesheets:execute',"Wrapper for console commands")]
class GoogleSheetsApiCommand extends Command
{

    public function __construct(
        private readonly GoogleApiClientService $clientService,
        private readonly GoogleSheetsApiService $googleSheetsApiService,
    )
    {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this
        ->addOption('function', null, InputOption::VALUE_OPTIONAL,'sheets api function to be executed')
        ->addOption('title', null, InputOption::VALUE_OPTIONAL,'sheet title in string')
        ->addOption('id', null, InputOption::VALUE_OPTIONAL,'spreadsheets id')
        ->addOption('header', null, InputOption::VALUE_OPTIONAL,'number of rows for the header', 0)
        ->addOption('data', null, InputOption::VALUE_OPTIONAL,'grid data in 2 dimensional array');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $function = $input->getOption('function');
        $id = $input->getOption('id');
        $sheetTitle = $input->getOption('title');
        $data = json_decode((string) $input->getOption('data'));
        $header = $input->getOption('header');

        $clientService = $this->clientService;
        $service = $this->googleSheetsApiService;

        $response = 'no action has been made';
        if($function == 'token') {
            $response = $clientService->createNewSheetApiAccessToken();
        } else {
            $service->setSheetServices($id);
        }

        if($function == 'get') {
            $service->setSheetServices($id);
            $response = $service->getGoogleSpreadSheets();
            dd($response);
        } elseif($function == 'create') {
            $response = $service->createNewSheet($sheetTitle, $data, $header);
        } elseif($function == 'update') {
            $response = $service->updateSheet($sheetTitle, $data, $header);
        } elseif($function == 'clear') {
            $response = $service->clearSheetByTitle($sheetTitle);
        } elseif($function == 'delete') {
            $response = $service->deleteSheetByTitle($sheetTitle);
        }

        $output->writeln($response);

        return self::SUCCESS;
    }
}
