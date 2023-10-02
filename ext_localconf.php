<?php
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\AUS\AusDriverAmazonS3\Driver\AmazonS3Driver::class] = array(
   'className' => \Toumoro\TmS3xclass\Driver\AmazonS3Driver::class
);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\AUS\AusDriverAmazonS3\Signal\FileIndexRepository::class] = array(
   'className' => \Toumoro\TmS3xclass\Signal\FileIndexRepository::class
);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\AUS\AusDriverAmazonS3\Index\Extractor::class] = array(
   'className' => \Toumoro\TmS3xclass\Index\Extractor::class
);
