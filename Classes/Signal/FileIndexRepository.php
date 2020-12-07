<?php
namespace Toumoro\TmS3xclass\Signal;

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

use AUS\AusDriverAmazonS3\Driver\AmazonS3Driver;
use AUS\AusDriverAmazonS3\Index\Extractor;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Signals for metadata update
 *
 * @author Markus Hölzle <m.hoelzle@andersundsehr.com>
 * @author Stefan Lamm <s.lamm@andersundsehr.com>
 * @package AUS\AusDriverAmazonS3\Signal
 */
class FileIndexRepository extends \AUS\AusDriverAmazonS3\Signal\FileIndexRepository
{

    /**
     * @param array $data
     * @return void|null
     */
    public function recordUpdatedOrCreated($data)
    {

        if ($data['type'] === File::FILETYPE_IMAGE) {
            $storage = $this->getStorage($data['storage']);

            // only process on our driver type where data was missing
            if ($storage->getDriverType() !== AmazonS3Driver::DRIVER_TYPE) {
                return null;
            }

            $file = $storage->getFile($data['identifier']);

            $imageDimensions = $this->getExtractor()->getImageDimensionsOfRemoteFile($file);

            if ($imageDimensions !== null) {
                $metaDataRepository = $this->getMetaDataRepository();
                $metaData = $metaDataRepository->findByFileUid($data['uid']);

                $metaData['width'] = $imageDimensions[0];
                $metaData['height'] = $imageDimensions[1];
                $metaDataRepository->update($data['uid'], $metaData);
            }
        } else if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('tika')) {
          //exit("test");
          if ($data['type'] > 0) {
            $storage = $this->getStorage($data['storage']);

            // only process on our driver type where data was missing
            if ($storage->getDriverType() !== AmazonS3Driver::DRIVER_TYPE) {
                return null;
            }


            //var_dump($data);exit;
          //  if ($storage->fileExists($data['identifier'])) {
            $file = $storage->getFile($data['identifier']);
          //  var_dump($file);exit();
              $extractor = GeneralUtility::makeInstance(\ApacheSolrForTypo3\Tika\Service\Extractor\MetaDataExtractor::class);

                if ($extractor->canProcess($file)) {
                  $extractedMetadata = $extractor->extractMetaData($file);
                  if (!empty($extractedMetadata)) {
                    $metaDataRepository = $this->getMetaDataRepository();
                    $metaDataRepository->update($data['uid'], $extractedMetadata);
                  }
                }

          //  }
        }
      }
    }

}
