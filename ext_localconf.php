<?php
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['AUS\\AusDriverAmazonS3\\Driver\\AmazonS3Driver'] = array(
   'className' => 'Toumoro\\TmS3xclass\\Driver\\AmazonS3Driver'
);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['AUS\\AusDriverAmazonS3\\Signal\\FileIndexRepository'] = array(
   'className' => \Toumoro\TmS3xclass\Signal\FileIndexRepository::class
);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['AUS\\AusDriverAmazonS3\\Index\\Extractor'] = array(
   'className' => 'Toumoro\\TmS3xclass\\Index\\Extractor'
);
