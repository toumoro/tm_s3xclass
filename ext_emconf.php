<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "tm_s3xclass"
 *
 * Auto generated by Extension Builder 2018-08-15
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'AWS S3 driver xclass',
    'description' => 'This extension is an AWS S3 TYPO3 driver based on aus_driver_amazon_s3 that provides xclasses for metadata extraction, fileExists verification and it prefixes a slash on all objects.',
    'category' => 'plugin',
    'author' => 'Simon Ouellet',
    'author_email' => 'simon.ouellet@toumoro.com',
    'state' => 'alpha',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '1.7.2',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-11.5.99',
            'aus_driver_amazon_s3' => '*'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
