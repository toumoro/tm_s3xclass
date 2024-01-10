<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die('Access denied.');

ExtensionManagementUtility::addStaticFile('tm_s3xclass', 'Configuration/TypoScript', 'S3 driver xclass');
