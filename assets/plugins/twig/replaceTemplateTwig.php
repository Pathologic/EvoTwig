<?php
$debug = (isset($debug) && $debug == 'true') ? true : false;
$modxcache = (isset($modxcache) && $modxcache == 'true') ? true : false;
$conditional = (isset($conditional) && $conditional == 'true') ? true : false;
$cachePath = 'assets/cache/template/';
$tplExt = isset($tplExt) ? $tplExt : 'tpl';

switch ($modx->event->name) {
    case 'OnWebPageInit':
    case 'OnManagerPageInit':
    case 'OnPageNotFound':
        {
            $modx->tpl = \DLTemplate::getInstance($modx);
            $modx->tpl->setTemplateExtension($tplExt);
            switch (true) {
                case $cacher == 'APC' && function_exists('apc_cache_info'):
                    {
                        $modx->cache = new \Doctrine\Common\Cache\ApcCache();
                        break;
                    }
                case $cacher == 'APCu' && function_exists('apcu_cache_info'):
                    {
                        $modx->cache = new \Doctrine\Common\Cache\ApcuCache();
                        break;
                    }
                case $cacher == 'Memcache' && class_exists('Memcache'):
                    {
                        $modx->cache = new \Doctrine\Common\Cache\MemcacheCache();
                        $memcache = new Memcache();
                        $memcache->connect('localhost', 11211);
                        $modx->cache->setMemcache($memcache);
                        break;
                    }
                case $cacher == 'Memcached' && class_exists('Memcached'):
                    {
                        $modx->cache = new \Doctrine\Common\Cache\MemcachedCache();
                        $memcached = new Memcached();
                        $memcached->addServer('memcache_host', 11211);
                        $modx->cache->setMemcached($memcached);
                        break;
                    }
                case $cacher == 'SQLite3' && class_exists('SQLite3'):
                    {
                        $modx->cache = new \Doctrine\Common\Cache\SQLite3Cache(
                            new SQLite3(MODX_BASE_PATH . 'assets/cache/sqlite.db'), 'cache'
                        );
                        break;
                    }
                default:
                    {
                        $modx->cache = new \Doctrine\Common\Cache\FilesystemCache(
                            MODX_BASE_PATH . 'assets/cache/data/'
                        );
                    }
            }
            if (!empty($modx->cache)) {
                $modx->cache->setNamespace($modx->getConfig('site_name'));
            }
            if (class_exists('Twig_Environment')) {
                if (!is_readable(MODX_BASE_PATH . $tplFolder)) {
                    mkdir(MODX_BASE_PATH . $tplFolder);
                }
                if (isset($tplDevFolder) && !is_readable(MODX_BASE_PATH . $tplDevFolder)) {
                    mkdir(MODX_BASE_PATH . $tplDevFolder);
                }
                if (isset($tplDevFolder) && $modx->getLoginUserID('mgr')) {
                    $tplFolder = $tplDevFolder;
                }
                $_loader = new Twig_Loader_Filesystem(MODX_BASE_PATH . $tplFolder);
                $modx->tpl->setTemplatePath($tplFolder);

                $loader = new Twig_Loader_Chain(array($_loader));

                $modx->twig = new Twig_Environment($loader, array(
                    'cache' => MODX_BASE_PATH . $cachePath,
                    'debug' => $debug
                ));
                $modx->twig->addExtension(new Twig_Extension_Debug());

                $cacheProvider = new Asm89\Twig\CacheExtension\CacheProvider\DoctrineCacheAdapter($modx->cache);

                $modx->twig->addExtension(new Asm89\Twig\CacheExtension\Extension(
                    new Asm89\Twig\CacheExtension\CacheStrategy\IndexedChainingCacheStrategy(array(
                        'time' => new Asm89\Twig\CacheExtension\CacheStrategy\LifetimeCacheStrategy($cacheProvider),
                        'key'  => new Asm89\Twig\CacheExtension\CacheStrategy\GenerationalCacheStrategy($cacheProvider,
                            new \AN\Twig\KeyGenerator(), 0)
                    ))
                ));

                if (isset($allowedFunctions)) {
                    $allowedFunctions = array_map('trim', explode(',', $allowedFunctions));
                    $PhpFunctionExtension = new Umpirsky\Twig\Extension\PhpFunctionExtension();
                    $PhpFunctionExtension->allowFunctions($allowedFunctions);
                    $modx->twig->addExtension($PhpFunctionExtension);
                }
                /**
                 * {{ runSnippet('example') | modxParser }}
                 * {{ '[*id*]' | modxParser }}
                 */
                $modx->twig->addFilter(new Twig_Filter('modxParser', function ($content) use ($modx) {
                    $modx->minParserPasses = 2;
                    $modx->maxParserPasses = 10;

                    $out = $modx->tpl->parseDocumentSource($content, $modx);

                    $modx->minParserPasses = -1;
                    $modx->maxParserPasses = -1;

                    return $out;
                }
                ));

                /**
                 * {{ makeUrl(20) }}
                 * {{ makeUrl(20, {page: 2}) }}
                 * {{ makeUrl(20, {}, false) }}
                 * {{ makeUrl(20, {page: 2}, false) }}
                 */
                $modx->twig->addFunction(
                    new Twig_Function('makeUrl', function ($id, array $args = array(), $absolute = true) use ($modx) {
                        return $modx->makeUrl($id, '', http_build_query($args), $absolute ? 'full' : '');
                    })
                );

                $modx->twig->addFunction(new Twig_Function('runSnippet', array($modx, 'runSnippet')));
                $modx->twig->addFunction(new Twig_Function('getChunk', array($modx->tpl, 'getChunk')));
                $modx->twig->addFunction(new Twig_Function('parseChunk', array($modx->tpl, 'parseChunk')));

                /**
                 * {{ ['Остался %d час', 'Осталось %d часа', 'Осталось %d часов']|plural(11) }}
                 * {{ count }} стат{{ ['ья','ьи','ей']|plural(count) }}
                 */
                $modx->twig->addFilter(new Twig_Filter('plural',
                    function ($endings, $number) {
                        $cases = [2, 0, 1, 1, 1, 2];
                        $n = $number;

                        return sprintf($endings[($n % 100 > 4 && $n % 100 < 20) ? 2 : $cases[min($n % 10, 5)]], $n);
                    }
                ));
                $modx->twig->getExtension('Twig_Extension_Core')->setNumberFormat(0, ",", " ");
            } else {
                include_once(MODX_BASE_PATH . "assets/snippets/DocLister/lib/xnop.class.php");
                $modx->twig = new xNop;
            }
            $modx->useConditional = $conditional && !$debug;
            break;
        }
    case 'OnLoadWebDocument':
        {
            $documentObject = $modx->documentObject;
            $template = '';
            $dir = MODX_BASE_PATH . $tplFolder;
            switch (true) {
                case file_exists($dir . 'tpl-' . $documentObject['template'] . '_doc-' . $documentObject['id'] . '.' . $tplExt):
                    {
                        $template = 'tpl-' . $documentObject['template'] . '_doc-' . $documentObject['id'] . '.' . $tplExt;
                        break;
                    }
                case file_exists($dir . 'doc-' . $documentObject['id'] . '.' . $tplExt):
                    {
                        $template = 'doc-' . $documentObject['id'] . '.' . $tplExt;
                        break;
                    }
                case file_exists($dir . 'tpl-' . $documentObject['template'] . '.' . $tplExt):
                    {
                        $template = 'tpl-' . $documentObject['template'] . '.' . $tplExt;
                        break;
                    }
                default:
                    {
                        $content = $documentObject['template'] ? $modx->documentContent : $documentObject['content'];
                        if (!$content) {
                            $content = $documentObject['content'];
                        }
                        if (substr($content, 0, 6) == '@FILE:') {
                            $template = str_replace('@FILE:', '', trim($content));
                            if (!file_exists($dir . $template)) {
                                $modx->documentObject['template'] = 0;
                                $modx->documentContent = $documentObject['content'];
                            }
                        };
                    }
            }
            $resource = array();
            foreach ($documentObject as $key => $value) {
                $resource[$key] = is_array($value) ? $value[1] : $value;
            }
            $twigTemplateVars = array(
                'modx'           => $modx,
                'documentObject' => &$documentObject,
                'resource'       => $resource,
                'debug'          => $debug,
                'config'         => &$modx->config,
                'plh'            => &$modx->placeholders,
                'debug'          => $debug,
                'ajax'           => isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest',
                '_GET'           => $_GET,
                '_POST'          => $_POST,
                '_COOKIE'        => $_COOKIE,
                '_SESSION'       => $_SESSION
            );
            if (method_exists($modx->tpl, 'setTwigTemplateVars')) {
                $modx->tpl->setTwigTemplateVars($twigTemplateVars);
            } else {
                $modx->tpl->setTemplateData($twigTemplateVars);
            }
            if (!empty($template)) {
                if ($disableTwig == 'true' || $modx->twig instanceof xNop) {
                    ob_start();
                    include($dir . $template);
                    $modx->documentContent = ob_get_contents();
                    ob_end_clean();
                } elseif ($disableTwig == 'false') {
                    $modx->minParserPasses = -1;
                    $modx->maxParserPasses = -1;
                    $tpl = $modx->twig->loadTemplate($template);
                    $modx->documentContent = $tpl->render($twigTemplateVars);
                }
            }
            break;
        }
    case 'OnCacheUpdate':
        {
            if (!empty($modx->cache)) {
                $modx->cache->flushAll();
            }
            break;
        }
    case 'OnSiteRefresh':
        {
            \Helpers\FS::getInstance()->rmDir($cachePath);
        }
    case 'OnWebPagePrerender':
        {
            if ($debug || !$modxcache) {
                $modx->documentObject['cacheable'] = 0;
            }
            break;
        }
}
