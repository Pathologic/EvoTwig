<?php
include_once(MODX_MANAGER_PATH."/composer/vendor/autoload.php");

$modx->doc = new modResource($modx);
$modx->tpl = DLTemplate::getInstance($modx);
$modx->fs = \Helpers\FS::getInstance();