<?php
$debug = (isset($debug) && $debug == 'true') ? true : false;
$modxcache = (isset($modxcache) && $modxcache == 'true') ? true : false;
$conditional = (isset($conditional) && $conditional == 'true') ? true : false;
$cachePath = 'assets/cache/blade/';

$modx->tpl = \DLTemplate::getInstance($modx);
if (! function_exists('modxParser')) {
    function modxParser($content)
    {
        $modx = evolutionCMS();
        $modx->minParserPasses = 2;
        $modx->maxParserPasses = 10;

        $out = $modx->tpl->parseDocumentSource($content, $modx);

        $modx->minParserPasses = -1;
        $modx->maxParserPasses = -1;

        return $out;
    }
}

switch ($modx->event->name) {
    case 'OnWebPageInit':
    case 'OnManagerPageInit':
    case 'OnPageNotFound':
        if (class_exists(Jenssegers\Blade\Blade::class)) {
            if (! is_readable(MODX_BASE_PATH . $tplFolder)) {
                mkdir(MODX_BASE_PATH . $tplFolder);
            }
            if (isset($tplDevFolder) && ! is_readable(MODX_BASE_PATH . $tplDevFolder)) {
                mkdir(MODX_BASE_PATH . $tplDevFolder);
            }
            if (isset($tplDevFolder) && $modx->getLoginUserID('mgr')) {
                $tplFolder = $tplDevFolder;
            }
            $modx->blade = new Jenssegers\Blade\Blade(
                MODX_BASE_PATH . $tplFolder,
                MODX_BASE_PATH . $cachePath
            );
            $modx->blade->compiler()->directive('modxParser', function ($content) {
                return '<?php echo modxParser(' . $content . ');?>';
            });
        } else {
            include_once MODX_BASE_PATH . 'assets/snippets/DocLister/lib/xnop.class.php';
            $modx->blade = new xNop;
        }
        $modx->useConditional = $conditional && !$debug;
        break;
    case 'OnLoadWebDocument':
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
                if (0 === substr($content, '@FILE:')) {
                    $template = str_replace('@FILE:', '', trim($content));
                    if (!file_exists($dir . $template)) {
                        $modx->documentObject['template'] = 0;
                        $modx->documentContent = $documentObject['content'];
                    }
                }
        }
        if (! empty($template)) {
            if ($modx->blade instanceof xNop) {
                ob_start();
                include($dir . $template);
                $modx->documentContent = ob_get_contents();
                ob_end_clean();
            } elseif (isset($disableBlade) && $disableBlade === 'false') {
                $modx->minParserPasses = -1;
                $modx->maxParserPasses = -1;
                $tpl = $modx->blade->make($template, [
                    'modx'  => $modx,
                    'debug' => $debug,
                ]);
                $modx->documentContent = $tpl->render();
            }
        }
        break;
    case 'OnCacheUpdate':
    case 'OnSiteRefresh':
        if (class_exists(Illuminate\Filesystem\Filesystem::class)) {
            $file = new Illuminate\Filesystem\Filesystem;
            $file->cleanDirectory(MODX_BASE_PATH . $cachePath);
        } else {
            \Helpers\FS::getInstance()->rmDir($cachePath);
        }
        break;
    case 'OnWebPagePrerender':
        if ($debug || !$modxcache) {
            $modx->documentObject['cacheable'] = 0;
        }
        break;
}
