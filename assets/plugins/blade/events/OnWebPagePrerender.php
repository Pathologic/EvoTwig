<?php
if (! defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}

if ($debug || !$modxcache) {
    $modx->documentObject['cacheable'] = 0;
}
