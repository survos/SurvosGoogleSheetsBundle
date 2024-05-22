<?php

namespace Survos\GoogleSheetsBundle\Service;

use Google\Service\Sheets;
use Google\Service\Sheets\Spreadsheet;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;
use Google_Service_Sheets_BatchUpdateSpreadsheetRequest;
use Survos\GoogleSheetsBundle\Service\GoogleApiClientService;
use Survos\GoogleSheetsBundle\Service\Requests\GoogleSheetsRequests;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use \Exception;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Google Sheets sheet api service class
 *
 * @package Survos\GoogleSheetsBundle\Service
 */
class GoogleSheetsApiService extends GoogleSheetsRequests
{

    /**
     * Goggle Sreadsheets id
     *
     * @var string
     */
    protected $id;

    public function __construct(
        protected GoogleApiClientService $clientService,
        protected Sheets $googleSheetsService,
        private CacheInterface $cache
    )
    {
    }

    /**
     * Set google spi client service.
     * Set google spreadsheets id tobe used.
     *
     * @param string $id
     * @return boolean
     * @throws InvalidConfigurationException
     */
    public function setSheetServices(string $id): bool
    {
        if (empty($id)) {
            throw new InvalidConfigurationException("spreadsheets id can not be empty");
        }

        $this->id = $id;
        $client = $this->clientService->getClient('offline');   // get api clirent
        $client->setScopes(implode(' ', [Sheets::DRIVE]));   // set permission
//        $client = $this->clientService->setClientVerification($client); // set verification
        $this->googleSheetsService = new Google_Service_Sheets($client);
        return true;
    }

    public function getGoogleSpreadSheets(): ?Spreadsheet
    {

        return $this->googleSheetsService->spreadsheets->get($this->id);
//        try {
//        } catch (\Exception $ex) {
//            return json_decode($ex->getMessage());
//        }
    }

    public function getValueRange($range='A1:B2'): Sheets\ValueRange
    {
        return $this->googleSheetsService->spreadsheets_values->get($this->id, $range);
    }
    public function getValues($range='A1:B2', bool $refresh = false): array
    {
        // this is easily cachable.
        $key = md5($range);
        $values = $this->cache->get($key, function (CacheItem $item) use ($range) {
            $valueRange = $this->getValueRange($range);
            $values = $valueRange->getValues();
            return $values;
        });
        return $values;
//        dd(cachedValues: $values);

//        try {
//        } catch (\Exception $ex) {
//            return json_decode($ex->getMessage());
//        }
    }

    /**
     * Create the new sheet.
     *
     * @param string $title
     * @param array $data
     * @param int $header
     * @return mixed(int|boolean)
     */
    public function createNewSheet($title = '', $data = [], $header = 0)
    {
        if (empty($title)) {
            throw new InvalidConfigurationException("Sheet title can not be empty");
        }

        $addNewSheetResponse = $this->addNewSheet($title, $data);
        if ($addNewSheetResponse) {
            return $this->insertDataForNewSheet($title, $data, $header);
        }
        return false;
    }

    /**
     * Add the new spreadsheets sheet
     *
     * @param string $title
     * @param array $data
     * @return boolean
     */
    public function addNewSheet($title, $data = [])
    {
        if (empty($data)) {
            $gidProperties = ["rowCount" => 1000, "columnCount" => 26];
            return $this->addNewSheetWithoutData($title, $gidProperties);
        }
        return $this->addNewSheetWithData($title);
    }

    /**
     * Add the new sheet in the spreadsheets with minimum grid size
     * The grid size will be extended with data
     *
     * @param string $title
     * @return boolean
     */
    public function addNewSheetWithData($title)
    {
        try {
            $request = $this->getNewSheetRequest($title);
            $requestBody = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
            $requestBody->setRequests($request);
            $this->googleSheetsService->spreadsheets->batchUpdate($this->id, $requestBody);
            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Add the new sheet in the spreadsheets with the default grid size(26x1000)
     *
     * @param string $title
     * @return boolean
     */
    public function addNewSheetWithoutData($title, $gidProperties = null)
    {
        try {
            $request = $this->getNewSheetRequest($title, 'GRID', $gidProperties);
            $requestBody = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
            $requestBody->setRequests($request);
            $this->googleSheetsService->spreadsheets->batchUpdate($this->id, $requestBody);
            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Insert data to the new sheet
     *
     * @param string $title
     * @param array $data
     * @param int $header
     * @return int
     */
    public function insertDataForNewSheet(?string $title = '', $data = [], $header = 0)
    {
        if (is_array($data) && count($data) > 0) {
            $range = $this->getSheetRangeByData($title, $data, $header);
            return $this->InsertSheetData($range, $data);
        }
        return 0;
    }

    /**
     * Get the sheet range for the given data
     * Data must be a two dimensional array
     *
     * @param string $title
     * @param array $data
     * @param int $header "the header row number"
     * @param string $startCol
     * @return string
     * @throws InvalidConfigurationException
     */
    public function getSheetRangeByData(?string $title = '', $data = [], $header = 0, string $startCol = 'A'): string
    {
        if (!is_array($data) || empty($title)) {
            throw new InvalidConfigurationException("Sheet title is missing or incorrect data format");
        }

        $startRow = $header + 1;
        $rows = array_keys($data);
        $numCols = $this->getNumberOfDataCols($rows, $data);
        $endCol = $this->getEndColRange($startCol, $numCols);
        $endRow = $startRow + (count($rows) - 1);
        return $title . '!' . $startCol . $startRow . ':' . $endCol . $endRow;
    }

    /**
     * Get the last column character by the column size automatically up to 676 columns
     *
     * @param string $startCol
     * @param int $numCols
     * @return string
     * @throws InvalidConfigurationException
     */
    public function getEndColRange($startCol = 'A', $numCols = 0): string
    {
        if ($numCols > 676) {
            throw new InvalidConfigurationException("Out of range for number of columns, use InsertSheetData()");
        } elseif ($numCols > 26) {
            $repeat = (int)floor($numCols / 26) - 1;
            $leftOver = (int)($numCols % 26) - 1;
            $endCol = chr(ord($startCol) + $leftOver);
            $preCol = chr(ord($startCol) + $repeat);
            return $preCol . $endCol;
        }
        return chr(ord($startCol) + ($numCols - 1));
    }

    /**
     * Get the number of data columns
     *
     * @param array $rows
     * @param array $data
     * @return int
     * @throws InvalidConfigurationException
     */
    public function getNumberOfDataCols(array $rows, $data): int
    {
        assert(!empty($data), "missing data");
        if (isset($data[$rows[0]]) && is_array($data[$rows[0]])) {
            $cols = array_keys($data[$rows[0]]);
            return count($cols);
        }
        throw new InvalidConfigurationException("Data must be 2 dimensional array");
    }

    /**
     * Insert data grid to the sheet
     *
     * @param string $range
     * @param array $data
     * @return int
     */
    public function insertSheetData($range, $data)
    {
        if (!empty($range) && !empty($data)) {
            $inputOption = ['valueInputOption' => 'RAW'];
            $requestBody = new Google_Service_Sheets_ValueRange();
            $requestBody->setMajorDimension('ROWS');
            $requestBody->setRange($range);
            $requestBody->setValues($data);
            $response = $this->googleSheetsService->spreadsheets_values->update($this->id, $range, $requestBody, $inputOption);
            return $response->getUpdatedRows();
        }
        return 0;
    }

    /**
     * Clear all sheet contents by the id
     *
     * @param int $sheetId
     * @return boolean
     */
    public function clearSheetById($sheetId)
    {
        try {
            $request = $this->getClearSheetRequest($sheetId);
            $requestBody = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
            $requestBody->setRequests($request);
            $this->googleSheetsService->spreadsheets->batchUpdate($this->id, $requestBody);
            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Clear all sheet contents by the title
     *
     * @param string $title
     * @return boolean
     */
    public function clearSheetByTitle($title)
    {
        $sheetId = $this->getSheetIdByTitle($title);
        if ($sheetId) {
            return $this->clearSheetById($sheetId);
        }
        return false;
    }

    /**
     * Delete the sheet by the id
     *
     * @param int $sheetId
     * @return boolean
     */
    public function deleteSheetById($sheetId)
    {
        try {
            $request = $this->getDeleteSheetRequest($sheetId);
            $requestBody = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
            $requestBody->setRequests($request);
            $this->googleSheetsService->spreadsheets->batchUpdate($this->id, $requestBody);
            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Delete the sheet by the title
     *
     * @param string $title
     * @return boolean
     */
    public function deleteSheetByTitle($title)
    {
        $sheetId = $this->getSheetIdByTitle($title);
        if ($sheetId) {
            return $this->deleteSheetById($sheetId);
        }
        return false;
    }

    /**
     * Update data grid for the sheet
     *
     * @param string $title
     * @param array $data
     * @param int $header
     * @return mixed(int|boolean)
     */
    public function updateSheet(?string $title, $data, $header)
    {
        $range = $this->getSheetRangeByData($title, $data, $header);
        if ($range) {
            return $this->InsertSheetData($range, $data);
        }
        return false;
    }

    /**
     * Get the sheet id by the title
     *
     * @param string $title
     * @return boolean
     */
    public function getSheetIdByTitle($title)
    {
        $sheets = $this->getGoogleSpreadSheets();
        foreach ($sheets as $key => $sheet) {
            if (isset($sheet->properties->title) && $sheet->properties->title == $title) {
                return $sheet->properties->sheetId;
            }
        }
        return false;
    }
}
