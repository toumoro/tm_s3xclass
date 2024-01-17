<?php

use AUS\AusDriverAmazonS3\Driver\AmazonS3Driver;
use AUS\AusDriverAmazonS3\Index\Extractor;
use AUS\AusDriverAmazonS3\Service\MetaDataUpdateService;

defined('TYPO3') or die;

$boot = static function (): void {

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][AmazonS3Driver::class] = array(
        'className' => \Toumoro\TmS3xclass\Driver\AmazonS3Driver::class
    );

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][MetaDataUpdateService::class] = [
        'className' => \Toumoro\TmS3xclass\Service\MetaDataUpdateService::class
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][Extractor::class] = array(
        'className' => \Toumoro\TmS3xclass\Index\Extractor::class
    );
};

$boot();
unset($boot);

