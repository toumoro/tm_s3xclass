<?php

namespace Toumoro\TmS3xclass\Service;

use ApacheSolrForTypo3\Tika\Service\Extractor\MetaDataExtractor;
use AUS\AusDriverAmazonS3\Driver\AmazonS3Driver;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Client\ClientExceptionInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InvalidUidException;

/**
 * Signals for metadata update
 *
 * @author Techno Québec <>
 *
 * @package Toumoro\TmS3xclass\Service
 */
class MetaDataUpdateService extends \AUS\AusDriverAmazonS3\Service\MetaDataUpdateService
{

    /**
     * @param array $fileProperties
     *
     * @return void
     * @throws ClientExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws InvalidUidException
     */
    public function updateMetadata(array $fileProperties): void
    {
        if ($fileProperties['type'] === AbstractFile::FILETYPE_IMAGE) {
            $storage = $this->getStorage((int)$fileProperties['storage']);

            // only process on our driver type where data was missing
            if ($storage->getDriverType() !== AmazonS3Driver::DRIVER_TYPE) {
                return;
            }

            $file = $storage->getFile($fileProperties['identifier']);
            $imageDimensions = $this->getExtractor()->getImageDimensionsOfRemoteFile($file);

            $metaDataRepository = $this->getMetaDataRepository();
            $metaData = $metaDataRepository->findByFileUid($fileProperties['uid']);

            $create = count($metaData) === 0;
            $metaData['width'] = $imageDimensions[0];
            $metaData['height'] = $imageDimensions[1];

            if ($create) {
                $metaDataRepository->createMetaDataRecord($fileProperties['uid'], $metaData);
            } else {
                $metaDataRepository->update($fileProperties['uid'], $metaData);
            }
        } else if (ExtensionManagementUtility::isLoaded('tika')) {
            if ($fileProperties['type'] > 0) {
                $storage = $this->getStorage($fileProperties['storage']);

                // only process on our driver type where data was missing
                if ($storage->getDriverType() !== AmazonS3Driver::DRIVER_TYPE) {
                    return;
                }

                $file = $storage->getFile($fileProperties['identifier']);
                $extractor = GeneralUtility::makeInstance(MetaDataExtractor::class);

                if ($extractor->canProcess($file)) {
                    $extractedMetadata = $extractor->extractMetaData($file);
                    if (!empty($extractedMetadata)) {
                        $metaDataRepository = $this->getMetaDataRepository();
                        $metaData = $metaDataRepository->findByFileUid($fileProperties['uid']);
                        if(count($metaData) === 0){
                            $metaDataRepository->createMetaDataRecord($fileProperties['uid'], $extractedMetadata);
                        }else{
                            $metaDataRepository->update($fileProperties['uid'], $extractedMetadata);
                        }
                    }
                }
            }
        }
    }
}
