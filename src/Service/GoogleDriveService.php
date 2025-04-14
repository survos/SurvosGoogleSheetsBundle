<?php

namespace Survos\GoogleSheetsBundle\Service;
use Google_Service_Drive;

class GoogleDriveService
{
    public function __construct(private GoogleApiClientService $googleClientService)
    {
    }

    public function downloadFileFromUrl(string $driveUrl, string $destinationPath): void
    {
        $fileId = $this->extractFileIdFromUrl($driveUrl);
        if (!$fileId) {
            throw new \Exception("Invalid Drive URL $driveUrl");
        }

        $client = $this->googleClientService->getClient();
            $service = new Google_Service_Drive($client);

            $response = $service->files->get($fileId, [
                'alt' => 'media'
            ]);

            $content = $response->getBody()->getContents();

//        $fileHandler = fopen($destinationPath, 'w');
        // @todo: stream response instead of writing all at one
//        foreach ($client->stream($response) as $chunk) {
//            fwrite($fileHandler, $chunk->getContent());
//        }
//        fclose($fileHandler);

        // @todo: check the mimetype and rename the file accordingly, since drive file urls don't tell us

        try {
        } catch (\Exception $exception) {
//            dd($exception->getMessage(), $client);
        }

        file_put_contents($destinationPath, $content);
    }

    private function extractFileIdFromUrl(string $url): ?string
    {
        if (preg_match('/\/file\/d\/([^\/]+)/', $url, $matches)) {
            return $matches[1];
        }

        if (preg_match('/id=([^&]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
