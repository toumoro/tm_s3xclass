<?php
namespace Toumoro\TmS3xclass\Driver;

use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use AUS\AusDriverAmazonS3\Event\GetFileForLocalProcessingEvent;

/**
 * Class AmazonS3Driver
 * Driver for Amazon Simple Storage Service (S3)
 *
 * @author Markus Hölzle <m.hoelzle@andersundsehr.com>
 * @package AUS\AusDriverAmazonS3\Driver
 */
class AmazonS3Driver extends \AUS\AusDriverAmazonS3\Driver\AmazonS3Driver
{

    /**
     * @param string $identifier
     * @return string
     */
    public function getPublicUrl($identifier)
    {
        $this->getStorage();
        $uriParts = GeneralUtility::trimExplode('/', ltrim($identifier, '/'), true);
        $uriParts = array_map('rawurlencode', $uriParts);
        return $this->baseUrl . '/' . implode('/', $uriParts);
    }

    /**
     * Checks if a folder exists
     *
     * @param string $identifier
     * @return bool
     */
    public function folderExists($identifier)
    {
        if ($identifier === self::ROOT_FOLDER_IDENTIFIER) {
            return true;
        }
        if (substr($identifier, -1) !== '/') {
            $identifier .= '/';
        }
        return $this->prefixExists($identifier);
    }

    /**
     * @param string $localFilePath (within PATH_site)
     * @param string $targetFolderIdentifier
     * @param string $newFileName optional, if not given original name is used
     * @param bool $removeOriginal if set the original file will be removed
     *                                after successful operation
     * @return string the identifier of the new file
     * @throws \Exception
     */
    public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = true)
    {
        $newFileName = $this->sanitizeFileName($newFileName !== '' ? $newFileName : PathUtility::basename($localFilePath));
        $targetIdentifier = $targetFolderIdentifier . $newFileName;
        $localIdentifier = $localFilePath;
        $this->normalizeIdentifier($localIdentifier);

        // if the source file is also in this driver
        if (!is_uploaded_file($localFilePath) && $this->objectExists($localIdentifier)) {
            if ($removeOriginal) {
                rename($this->getStreamWrapperPath($localIdentifier), $this->getStreamWrapperPath($targetIdentifier));
            } else {
                copy($this->getStreamWrapperPath($localIdentifier), $this->getStreamWrapperPath($targetIdentifier));
            }
        } else { // upload local file
            // Toumoro: check file extension for OOXML macro-enabled files
            $contentType = $this->getFileContentType($localFilePath, $newFileName);
            $this->createObject($targetIdentifier, file_get_contents($localFilePath), [
                'ContentType' => $contentType,
                'CacheControl' => $this->getCacheControl($targetIdentifier),
            ]);

            if ($removeOriginal) {
                unlink($localFilePath);
            }
        }
        $this->flushMetaInfoCache($targetIdentifier);

        return $targetIdentifier;
    }

    /**
     * Returns a path to a local copy of a file for processing it. When changing the
     * file, you have to take care of replacing the current version yourself!
     * The file will be removed by the driver automatically on destruction.
     *
     * @param string $fileIdentifier
     * @param bool $writable Set this to FALSE if you only need the file for read
     *                         operations. This might speed up things, e.g. by using
     *                         a cached local version. Never modify the file if you
     *                         have set this flag!
     * @return string The path to the file on the local disk
     * @throws \RuntimeException
     * @todo take care of replacing the file on change
     */
    public function getFileForLocalProcessing($fileIdentifier, $writable = true)
    {
        $temporaryPath = $this->getTemporaryPathForFile($fileIdentifier);
        $this->normalizeIdentifier($fileIdentifier);
        $this->s3Client->getObject([
            'Bucket' => $this->configuration['bucket'],
            'Key' => $fileIdentifier,
            'SaveAs' => $temporaryPath,
        ]);
        if (!is_file($temporaryPath)) {
            throw new \RuntimeException('Copying file ' . $fileIdentifier . ' to temporary path failed.', 1320577649);
        }
        /** @var GetFileForLocalProcessingEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new GetFileForLocalProcessingEvent($fileIdentifier, $temporaryPath, $writable)
        );
        $temporaryPath = $event->getTemporaryPath();

        if (!isset($this->temporaryPaths[$temporaryPath])) {
            $this->temporaryPaths[$temporaryPath] = $temporaryPath;
        }
        return $temporaryPath;
    }

   /**
     * Checks if a file exists
     *
     * @param string $identifier
     * @return bool
     */
    public function fileExists($identifier)
    {
        if ($identifier === "" || substr($identifier, -1) === '/') {
            return false;
        }
        return $this->objectExists($identifier);
    }

   /**
     * Get a file content-type
     *
     * @param string $localFilePath (within PATH_site)
     * @param string $newFileName
     * @return string the file content-type
     */
    protected function getFileContentType($localFilePath, $newFileName) {
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $contentType = finfo_file($fileInfo, $localFilePath);
        finfo_close($fileInfo);
        $fileExtension = pathinfo($newFileName, PATHINFO_EXTENSION);
        switch ($contentType) {
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                if (strcasecmp($fileExtension, 'dotx') == 0) {
                    $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.template';
                } elseif (strcasecmp($fileExtension, 'docm') == 0) {
                    $contentType = 'application/vnd.ms-word.document.macroEnabled.12';
                } elseif (strcasecmp($fileExtension, 'dotm') == 0) {
                    $contentType = 'application/vnd.ms-word.template.macroEnabled.12';
                }
                break;
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                if (strcasecmp($fileExtension, 'xltx') == 0) {
                    $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.template';
                } elseif (strcasecmp($fileExtension, 'xlsm') == 0) {
                    $contentType = 'application/vnd.ms-excel.sheet.macroEnabled.12';
                } elseif (strcasecmp($fileExtension, 'xltm') == 0) {
                    $contentType = 'application/vnd.ms-excel.template.macroEnabled.12';
                }
                break;
            case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
                if (strcasecmp($fileExtension, 'potx') == 0) {
                    $contentType = 'application/vnd.openxmlformats-officedocument.presentationml.template';
                } elseif (strcasecmp($fileExtension, 'ppsx') == 0) {
                    $contentType = 'application/vnd.openxmlformats-officedocument.presentationml.slideshow';
                } elseif (strcasecmp($fileExtension, 'pptm') == 0) {
                    $contentType = 'application/vnd.ms-powerpoint.presentation.macroEnabled.12';
                } elseif (strcasecmp($fileExtension, 'potm') == 0) {
                    $contentType = 'application/vnd.ms-powerpoint.template.macroEnabled.12';
                }
                break;
            case 'application/vnd.ms-powerpoint':
                if (strcasecmp($fileExtension, 'ppt') == 0) {
                    $contentType = '';
                }
                break;
        }
        return $contentType;
    }

}


