<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function()
    {

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('tm_s3xclass', 'Configuration/TypoScript', 'S3 driver xclass');

    }
);
