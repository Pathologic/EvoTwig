<?php
if (! defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}
try {
    include_once 'functions.php';

    $debug = (isset($debug) && $debug === 'true');
    $modxcache = (isset($modxcache) && $modxcache === 'true');
    $conditional = (isset($conditional) && $conditional === 'true');
    $cachePath = 'assets/cache/blade/';
    if (! isset($tplFolder)) {
        $tplFolder = 'assets/templates/';
    }
    $modx->tpl = \DLTemplate::getInstance($modx);

    if (file_exists(__DIR__ . '/events/' . $modx->event->name . '.php')) {
        require_once __DIR__ . '/events/' . $modx->event->name . '.php';
    } else {
        throw new Exception(sprintf('Event %s can not triggered in this plugin', $modx->event->name));
    }
} catch (Exception $exception) {
    $modx->messageQuit($exception->getMessage());
}
