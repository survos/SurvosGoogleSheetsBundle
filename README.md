# credits

Inspired by / Forked from https://github.com/Gabb1995/EXS-GoogleSheetsBundle

## Useful references

* https://mzonline.com/blog/2020-06/pushing-data-google-sheets-sheets-yes-multiples
* https://developers.google.com/sheets/api/guides/values
* https://developers.google.com/sheets/api/quickstart/js
* https://www.lido.app/tutorials/google-sheets-group-tabs

# EXS-GoogleSheetsBundle
Very simple wrapper for Google sheets integration

### What is this bundle doing ?
This bundle provides basic Google API SpreadSheets Sheets Service methods as Symfony services.<br>
Methods: Get|Create|Update|Clear|Delete


### Usage

#### Configuration

1. Set your Google project and get the client secret file.<br>[Click here to obtain your client secret and set project application name](https://developers.google.com/sheets/api/quickstart/php)

2. Save your client secret file as 'client_secret.json' in your project.

3. Add the client secret file location, project application name and credential location in the Symfony config file
``` php
survos_google_sheets:
    application_name: 'Google Sheets API'
    credentials: '%kernel.root_dir%/config/sheets.googleapis.com.json'
    client_secret: '%kernel.root_dir%/config/client_secret.json'
```
Your credentials will be created by the bundle once you set the file location in the bundle.<br>
Default location: '/Credentials/sheets.googleapis.com.json'

#### Create the access token

Create the access token for google api.

1. Execute the service via the command line.<br>
The service will provide you the link to get a verification code.
``` shell
bin/console googlesheets:execute --function=token
```

2. Copy the verification code from the link then enter it in the command line.


#### Inputs
id: Spreadsheets id<br>
title: sheet(tab) title<br> 
header: number of rows for header.<br>
data: 2 dimensional array for grid data.
``` php
$data = [
    [ COL1_HEADER, COL2_HEADER, ...],
    [ ROW1COL1_CELL_VALUE, ROW1COL2_CELL_VALUE, ...],
    [ ROW1COL2_CELL_VALUE, ROW2COL2_CELL_VALUE, ...],
    ....
];
```


#### Methods

##### SETUP(Common for all methods).
Inject GoogleSheetsApiService or obtain it from the container.

ex) Set up an api client with the spreadsheets id that you want to manage. 

``` php
    public function __construct(
        private readonly GoogleApiClientService $clientService,
        private readonly GoogleSheetsApiService $sheetService,
    )

$sheetService->setSheetServices(YOUR_SPREADSHEETS_ID_HERE);
```

##### GET
Get an existing spreadsheets

``` php
$spreadsheets = $service->getGoogleSpreadSheets(); 
```

##### CREATE
Create the new sheet in Google Spreadsheets.<br>
Return: Number of data rows that are inserted to the new sheet.<br>
If you call the function without data, it will create an empty sheet.

ex) Create the new sheet with data
``` php
$sheetTitle = 'my test sheet';
$data = [
    [ COL1_HEADER, COL2_HEADER, ...],
    [ ROW1COL1_CELL_VALUE, ROW1COL2_CELL_VALUE, ...],
    ....
];
$response = $service->createNewSheet($sheetTitle, $data);
```

##### UPDATE
Update the existing spreadsheets sheet.<br>
Return: Number of data rows that are updated to the sheet.

If you wants to update only cell values, not the header, define number of rows for the header.<br>
ex) Update grid data values only.
``` php
$header = 1;
$sheetTitle = 'my test sheet';
$data = [
    [ ROW1COL1_CELL_VALUE, ROW1COL2_CELL_VALUE, ...],
    [ ROW2COL1_CELL_VALUE, ROW2COL2_CELL_VALUE, ...],
    ....
];
$response = $service->updateSheet($sheetTitle, $data, $header);
```


##### CLEAR
Clear the entire sheet values.<br>
Return: number of rows that are cleared.

``` php
$sheetTitle = 'my test sheet';
$response = $service->clearSheetByTitle($sheetTitle);
```

##### DELETE
Delete the existing sheet in the spreadsheets.<br>
Return: Boolean


``` php
$sheetTitle = 'my test sheet';
$response = $service->deleteSheetByTitle($sheetTitle); 
```
<br><br>


###### Example ######
Create the new sheet with header then update it with data
``` php
// setup the service
$service = $this->getContainer()->get('survos_google_sheets.sheets_service');
$service->setSheetServices(YOUR_SPREADSHEETS_ID_HERE);

// create the sheet
$sheetTitle = 'my test sheet';
$data = [
    [ COL1_HEADER, COL2_HEADER, COL3_HEADER]
];
$service->createNewSheet($sheetTitle, $data);

// update grid data
$header = 1;
$data = [
    [ ROW1COL1_CELL_VALUE, ROW1COL2_CELL_VALUE, ROW1COL3_CELL_VALUE],
    [ ROW2COL1_CELL_VALUE, ROW2COL2_CELL_VALUE, ROW2COL3_CELL_VALUE],
    [ ROW3COL1_CELL_VALUE, ROW3COL2_CELL_VALUE, ROW3COL3_CELL_VALUE],
    [ ROW4COL1_CELL_VALUE, ROW4COL2_CELL_VALUE, ROW4COL3_CELL_VALUE]
];
$service->updateSheet($sheetTitle, $data, $header);

```
