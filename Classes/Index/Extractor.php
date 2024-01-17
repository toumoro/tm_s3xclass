<?php

namespace Toumoro\TmS3xclass\Index;

/***
 *
 * This file is part of an "anders und sehr" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2017 Markus Hölzle <m.hoelzle@andersundsehr.com>, anders und sehr GmbH
 * Stefan Lamm <s.lamm@andersundsehr.com>, anders und sehr GmbH
 *
 ***/

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use ApacheSolrForTypo3\Tika\Service\Extractor\MetaDataExtractor;
use AUS\AusDriverAmazonS3\Driver\AmazonS3Driver;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Client\ClientExceptionInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;

/**
 * Extractor for image files
 *
 * @author  Markus Hölzle <m.hoelzle@andersundsehr.com>
 * @author  Stefan Lamm <s.lamm@andersundsehr.com>
 * @package AUS\AusDriverAmazonS3\Index
 */
class Extractor extends \AUS\AusDriverAmazonS3\Index\Extractor
{

    /**
     * Returns an array of supported file types;
     * An empty array indicates all filetypes
     *
     * @return array
     */
    public function getFileTypeRestrictions()
    {
        return [AbstractFile::FILETYPE_IMAGE, AbstractFile::FILETYPE_APPLICATION];
    }


    /**
     * Checks if the given file can be processed by this Extractor
     *
     * @param File $file
     *
     * @return boolean
     */
    public function canProcess(File $file)
    {
        if (!ExtensionManagementUtility::isLoaded('tika')) {
            return $file->getType() == AbstractFile::FILETYPE_IMAGE && $file->getStorage()->getDriverType() === AmazonS3Driver::DRIVER_TYPE;
        } else {
            $extractor = GeneralUtility::makeInstance(MetaDataExtractor::class);
            return $extractor->canProcess($file);
        }
    }


    /**
     *  The actual processing TASK
     *
     *  Should return an array with database properties for sys_file_metadata to write
     *
     * @param File      $file
     * @param array     $previousExtractedData optional, contains the array of already extracted data
     *
     * @return array|null
     * @throws ClientExceptionInterface
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public function extractMetaData(File $file, array $previousExtractedData = [])
    {
        if ($file->getType() == AbstractFile::FILETYPE_IMAGE) {
            if (empty($previousExtractedData['width']) || empty($previousExtractedData['height'])) {
                $imageDimensions = $this->getImageDimensionsOfRemoteFile($file);
                if ($imageDimensions !== null) {
                    $previousExtractedData['width'] = $imageDimensions[0];
                    $previousExtractedData['height'] = $imageDimensions[1];
                }
            }
        } else if (ExtensionManagementUtility::isLoaded('tika')) {
            if ($file->getType() > 0) {
                $storage = $file->getStorage();

                // only process on our driver type where data was missing
                if ($storage->getDriverType() !== AmazonS3Driver::DRIVER_TYPE) {
                    return null;
                }
                $extractor = GeneralUtility::makeInstance(MetaDataExtractor::class);

                if ($extractor->canProcess($file)) {
                    $extractedMetadata = $extractor->extractMetaData($file);
                    if (!empty($extractedMetadata)) {
                        $previousExtractedData = $extractedMetadata;
                    }
                }
            }
        }

        return $previousExtractedData;
    }
}
