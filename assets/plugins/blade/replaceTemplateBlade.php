<?php
$debug = (isset($debug) && $debug == 'true') ? true : false;
$modxcache = (isset($modxcache) && $modxcache == 'true') ? true : false;
$conditional = (isset($conditional) && $conditional == 'true') ? true : false;
$cachePath = 'assets/cache/blade/';
if (!isset($tplFolder)) $tplFolder = 'assets/templates/';

$modx->tpl = \DLTemplate::getInstance($modx);
if (!function_exists('modxParser')) {
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
if (!function_exists('evoCoreLang')) {
    function evoCoreLang($key)
    {
        global $_lang;

        return get_by_key($_lang, $key, $key);
    }
}

switch ($modx->event->name) {
    case 'OnWebPageInit':
    case 'OnManagerPageInit':
    case 'OnPageNotFound':
        if (class_exists(Jenssegers\Blade\Blade::class)) {
            try {
                if (!is_readable(MODX_BASE_PATH . $tplFolder)) {
                    mkdir(MODX_BASE_PATH . $tplFolder);
                }
                if (isset($tplDevFolder) && !is_readable(MODX_BASE_PATH . $tplDevFolder)) {
                    mkdir(MODX_BASE_PATH . $tplDevFolder);
                }
                if (isset($tplDevFolder) && $modx->getLoginUserID('mgr')) {
                    $tplFolder = $tplDevFolder;
                }

                if (!is_readable(MODX_BASE_PATH . $cachePath)) {
                    mkdir(MODX_BASE_PATH . $cachePath);
                }
                if (! isset($modx->laravel)) {
                    $modx->laravel = new Illuminate\Container\Container;
                }
                $blade = new Jenssegers\Blade\Blade(
                    realpath(MODX_BASE_PATH . $tplFolder),
                    realpath(MODX_BASE_PATH . $cachePath),
                    $modx->laravel
                );
                /**
                 * @var Illuminate\View\Compilers\BladeCompiler $compiler
                 */
                $compiler = $blade->compiler();
                $compiler->directive('modxParser', function ($content) {
                    return '<?php echo modxParser(' . $content . ');?>';
                });
                $blade->compiler()->directive('lang', function ($content) use ($modx) {
                    return '<?php echo evoCoreLang(' . $content . ');?>';
                });
                /**
                 * @var Illuminate\View\Factory $blade
                 */
                $blade->addNamespace('cache', $cachePath);
                $paginatorTpl = MODX_BASE_PATH . rtrim($tplFolder, '/') . '/' . 'pagination';
                if (is_readable($paginatorTpl) && is_dir($paginatorTpl)) {
                    $blade->addNamespace('pagination', $paginatorTpl);
                }
                $modx->tpl->setTemplateExtension('.blade.php');
                $modx->tpl->setTemplatePath($tplFolder, true);
            } catch (Exception $exception) {
                $modx->messageQuit($exception->getMessage());
            }
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
                try {
                    $tpl = $modx->laravel->get('view')->make($template, [
                        'modx'     => $modx,
                        'document' => $modx->documentObject
                    ]);
                    $modx->documentContent = $tpl->render();
                } catch (Exception $exception) {
                    $modx->messageQuit($exception->getMessage());
                }
            }
        }
        break;
    case 'OnCacheUpdate':
    case 'OnSiteRefresh':
        try {
            if (class_exists(Illuminate\Filesystem\Filesystem::class)) {
                $file = new Illuminate\Filesystem\Filesystem;
                $file->cleanDirectory(MODX_BASE_PATH . $cachePath);
            } else {
                \Helpers\FS::getInstance()->rmDir($cachePath);
            }
        } catch (Exception $exception) {
            $modx->messageQuit($exception->getMessage());
        }
        break;
    case 'OnWebPagePrerender':
        if ($debug || !$modxcache) {
            $modx->documentObject['cacheable'] = 0;
        }
        break;
}
