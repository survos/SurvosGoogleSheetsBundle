<?php

namespace Survos\GoogleSheetsBundle\Service\Requests;

class GoogleSheetsRequests
{

    /**
     * Get add new sheet request 
     * 
     * @param string $title
     * @param string $type
     * @param array $gidProperties
     * @return array
     */
    public function getNewSheetRequest($title = '', $type = 'GRID', $gidProperties = null)
    {
        return [
            'addSheet' => [
                'properties' => [
                    "title" => $title,
                    "sheetType" => $type,
                    "gridProperties" => $this->getNewSheetRange($gidProperties)
                ]                
            ]
        ];
    }

    /**
     * Set the default grid size
     * 
     * @param array $gidProperties
     * @return array
     */
    public function getNewSheetRange($gidProperties = null)
    {
        if(empty($gidProperties)) {
            $gidProperties = [
                "rowCount" =>  1,
                "columnCount" => 26                
            ];
        }
        return $gidProperties;
    }
 
    /**
     * Get clear sheet request
     * 
     * @param int $sheetId
     * @param string $fieldOption
     * @return array
     */
    public function getClearSheetRequest($sheetId, $fieldOption = 'userEnteredValue')
    {
        return [
            'updateCells' => [
                'range' => [
                    "sheetId" => $sheetId
                ],
                "fields" => $fieldOption
            ]
        ];
    }

    /**
     * Get delete sheet request
     * 
     * @param int $sheetId
     * @return array
     */
    public function getDeleteSheetRequest($sheetId)
    {
        return [
            'deleteSheet' => [
                "sheetId" => $sheetId
            ]
        ];
    }
}
