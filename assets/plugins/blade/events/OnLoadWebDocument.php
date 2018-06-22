<?php
if (! defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}

$documentObject = $modx->documentObject;
$template = '';
$dir = MODX_BASE_PATH . $tplFolder;
$tplExt = 'blade.php';
switch (true) {
    case file_exists($dir . 'tpl-' . $documentObject['template'] . '_doc-' . $documentObject['id'] . '.' . $tplExt):
        $template = 'tpl-' . $documentObject['template'] . '_doc-' . $documentObject['id'];
        break;
    case file_exists($dir . 'doc-' . $documentObject['id'] . '.' . $tplExt):
        $template = 'doc-' . $documentObject['id'];
        break;
    case file_exists($dir . 'tpl-' . $documentObject['template'] . '.' . $tplExt):
        $template = 'tpl-' . $documentObject['template'];
        break;
    default:
        $content = $documentObject['template'] ? $modx->documentContent : $documentObject['content'];
        if (!$content) {
            $content = $documentObject['content'];
        }
        if (0 === strpos($content, '@FILE:')) {
            $template = str_replace('@FILE:', '', trim($content));
            if (!file_exists($dir . $template)) {
                $modx->documentObject['template'] = 0;
                $modx->documentContent = $documentObject['content'];
            }
        }
}
if (!empty($template)) {
    if (!isset($disableBlade) || $disableBlade === 'false') {
        $modx->minParserPasses = -1;
        $modx->maxParserPasses = -1;
        $tpl = $modx->laravel->get('view')->make($template, [
            'modx'     => $modx,
            'documentObject' => isset($modx->documentObject['id']) ? modxDocumentObject($modx->documentObject['id']) : []
        ]);
        $modx->documentContent = $tpl->render();
    }
}