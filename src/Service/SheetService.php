<?php

namespace Survos\GoogleSheetsBundle\Service;

use Google\Service\Sheets;
use Google_Client;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;
use Google_Service_Sheets;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

/**
 * SheetService Class
 *
 * @package Survos\GoogleSheetsBundle\Service
 */
class SheetService
{
    public function __construct(
        private GoogleSheetsApiService $googleSheetsApiService,
        private GoogleApiClientService $googleApiClientService,
        private CacheInterface $cache,
        public array $aliases=[]
    )
    {

    }

    public function getData(string $spreadsheetId, bool $refresh=false, ?callable $function=null): array
    {
        $client = $this->googleApiClientService->getClient();
        $client->setApplicationName('Google Sheets API');
        $client->setScopes([Sheets::SPREADSHEETS]);
        $client->setAccessType('offline');
// credentials.json is the key file we downloaded while setting up our Google Sheets API
//        $path = $this->dataDir . '/../google-credentials.json';
//        $client->setAuthConfig($path);

// configure the Sheets Service
        $service = new Sheets($client);

        foreach ($this->aliases as $alias) {
            $x[$alias['code']] = $alias['url'];
        }
//$testId = 'https://docs.google.com/spreadsheets/d/1mcOvia45gTzlMXlp9zF0o7ahbkKZa5AHfym7XiM5AL4/edit#gid=0';

//        $spreadsheetId = $x[$spreadsheetId];
//        $spreadsheetId = $testId;
//        $spreadsheetId = '1mcOvia45gTzlMXlp9zF0o7ahbkKZa5AHfym7XiM5AL4';
//        $spreadsheetId = '1osvCYhAahpZ3p1p_xT923MFzDXT2-NdF2qhlz91Btjs'; //chijal

//        $url = sprintf('https://docs.google.com/spreadsheets/d/%s/export?format=csv&gid=%s',
//            $spreadsheetId, '595401683');

        $spreadsheet = $service->spreadsheets->get($spreadsheetId);
        foreach ($spreadsheet->getSheets() as $sheet) {
            $sheetName = $sheet->getProperties()->getTitle();
            $range = $sheetName; // here we use the name of the Sheet to get all the rows
            $sheetId = $sheet->getProperties()->getSheetId();
            $url = sprintf('https://docs.google.com/spreadsheets/d/%s/export?format=csv&gid=%s',
                $spreadsheetId, $sheetId);
            if ($refresh) {
                $this->cache->delete($sheetId);
            }
            $data =  $this->cache->get($sheetId, function (CacheItem $item) use ($sheetName, $url) {
                $item->expiresAfter(3600);
                return file_get_contents($url);
            });
            $csv[$sheetName] = $data;
            // @todo: make this a parameter
            $options = [
//                'valueRenderOption' => 'FORMULA'
            ];
//            $response = $service->spreadsheets_values->get($spreadsheetId, $range, $options);
//            $values = $response->getValues();
//            dd($values, $sheetName);
            $function && $function($sheetName, $data);
//            return $values;
        }
//        dd($url);
//        dump($url);
//        dd(file_get_contents($url));
        dd($csv);
        return $csv;

    }

    public function getGoogleSpreadSheet(string $id)
    {
        $this->googleSheetsApiService->setSheetServices($id);
        return $this->googleSheetsApiService->getGoogleSpreadSheets($id);
    }

    public function downloadSheetToLocal(string $sheetId, string $localFilename): array
    {
        dd(get_defined_vars(), $this->aliases);
        $files = [];
        $dir = $this->dataDir . '/' . $project->getCode();
        if (!file_exists($dir)) {
            try {
                mkdir($dir, 0777, true);
            } catch (\Exception $exception) {
                throw new \Exception('Could not create directory ' . $dir . ' ' . $exception->getMessage()) ;
            }
        }


        $this->getData($sheetId,
            function(?array $values, SheetService $sheet)
            use (&$files, $dir, $project): void
            {
                file_put_contents($files[] = $dir .  sprintf("/%s.csv", $sheet->getProperties()->getTitle()),
                    $this->asCsv($values??[]));
            }
        );
        return $files;
    }


}
