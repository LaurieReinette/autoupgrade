<?php

namespace PrestaShop\Module\AutoUpgrade\Services;

use Exception;

class LocalVersionFilesService
{
    const TYPE_ZIP = 'zip';
    const TYPE_XML = 'xml';

    /** @var PrestashopVersionService */
    private $prestashopVersionService;
    /** @var string */
    private $downloadPath;
    /** @var string */
    private $currentVersion;

    public function __construct(
        PrestashopVersionService $prestashopVersionService,
        string $downloadPath,
        string $currentVersion
    ) {
        $this->prestashopVersionService = $prestashopVersionService;
        $this->downloadPath = $downloadPath;
        $this->currentVersion = $currentVersion;
    }

    /**
     * @param string $folderPath
     * @param string $extension
     *
     * @return string[]
     */
    public function getAllFilesFromFolder(string $folderPath, string $extension): array
    {
        if (!is_dir($folderPath)) {
            return [];
        }

        return glob($folderPath . DIRECTORY_SEPARATOR . '*.' . $extension);
    }

    /**
     * @return array<string, array{'zip': string[], 'xml': string[]}>
     *
     * @throws Exception
     */
    public function getLocalVersionsFiles(): array
    {
        $zipFiles = $this->getAllFilesFromFolder($this->downloadPath, self::TYPE_ZIP);

        $zipFiles = array_map(function ($zip) {
            $version = $this->prestashopVersionService->extractPrestashopVersionFromZip($zip);

            return ['filename' => basename($zip), 'version' => $version];
        }, $zipFiles);

        $xmlFiles = $this->getAllFilesFromFolder($this->downloadPath, self::TYPE_XML);

        $xmlFiles = array_map(function ($xml) {
            $version = $this->prestashopVersionService->extractPrestashopVersionFromXml($xml);

            return ['filename' => basename($xml), 'version' => $version];
        }, $xmlFiles);

        $groupedByVersion = [];

        $this->groupFilesByVersion($zipFiles, self::TYPE_ZIP, $groupedByVersion);
        $this->groupFilesByVersion($xmlFiles, self::TYPE_XML, $groupedByVersion);

        return array_filter($groupedByVersion, function ($files, $version) {
            $isRequiredFilesNotPresent = !empty($files[self::TYPE_XML]) && !empty($files[self::TYPE_ZIP]);
            $isInferiorVersion = version_compare($this->currentVersion, $version) === -1;

            return $isRequiredFilesNotPresent && $isInferiorVersion;
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * @param array<int, array{'version': string, 'filename': string}> $files
     * @param array<string, array{'zip': string, 'xml': string}> $groupedByVersion
     *
     * @return void
     */
    private function groupFilesByVersion(array $files, string $type, array &$groupedByVersion): void
    {
        foreach ($files as $file) {
            $version = $file['version'];
            $filename = $file['filename'];

            if (!isset($groupedByVersion[$version])) {
                $groupedByVersion[$version] = [
                    'zip' => [],
                    'xml' => [],
                ];
            }

            $groupedByVersion[$version][$type][] = $filename;
        }
    }
}
