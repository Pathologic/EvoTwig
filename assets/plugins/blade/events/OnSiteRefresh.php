<?php
if (! defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}

if (class_exists(Illuminate\Filesystem\Filesystem::class)) {
    $file = new Illuminate\Filesystem\Filesystem;
    $file->cleanDirectory(MODX_BASE_PATH . $cachePath);
} else {
    \Helpers\FS::getInstance()->rmDir($cachePath);
}
