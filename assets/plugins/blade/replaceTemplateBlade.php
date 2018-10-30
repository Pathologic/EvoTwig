<?php
if (! defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}
try {
    include_once __DIR__ . '/functions.php';
    include_once MODX_BASE_PATH . 'assets/snippets/DocLister/lib/DLTemplate.class.php';

    $debug = (isset($debug) && $debug === 'true');
    $modxcache = (isset($modxcache) && $modxcache === 'true');
    $conditional = (isset($conditional) && $conditional === 'true');
    $cachePath = $modx->getCacheFolder() . 'blade/';
    if (! isset($tplFolder)) {
        $tplFolder = 'assets/templates/';
    }

    if (file_exists(__DIR__ . '/events/' . $modx->event->name . '.php')) {
        require_once __DIR__ . '/events/' . $modx->event->name . '.php';
    } else {
        throw new Exception(sprintf('Event %s can not triggered in this plugin', $modx->event->name));
    }
} catch (Exception $exception) {
    $modx->messageQuit($exception->getMessage());
}
