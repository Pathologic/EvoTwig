<?php
if (! defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}

if (class_exists(Jenssegers\Blade\Blade::class)) {
    if (! is_readable(MODX_BASE_PATH . $tplFolder)) {
        mkdir(MODX_BASE_PATH . $tplFolder);
    }
    if (isset($tplDevFolder) && !is_readable(MODX_BASE_PATH . $tplDevFolder)) {
        mkdir(MODX_BASE_PATH . $tplDevFolder);
    }
    if (isset($tplDevFolder) && $modx->getLoginUserID('mgr')) {
        $tplFolder = $tplDevFolder;
    }

    if (! is_readable(MODX_BASE_PATH . $cachePath)) {
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

    if (class_exists(Illuminate\Pagination\Paginator::class)) {
        Illuminate\Pagination\Paginator::viewFactoryResolver(function () use ($modx) {
            return $modx->laravel->get('view');
        });

        Illuminate\Pagination\Paginator::currentPathResolver(function () {
            return rtrim(preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']), '/');
        });

        Illuminate\Pagination\Paginator::currentPageResolver(function ($pageName = 'page') {
            $page = get_by_key($_GET, $pageName, 1, 'is_scalar');
            if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int)$page >= 1) {
                return (int)$page;
            }

            return 1;
        });
        $paginatorTpl = MODX_BASE_PATH . rtrim($tplFolder, '/') . '/' . 'pagination';
        if (is_readable($paginatorTpl) && is_dir($paginatorTpl)) {
            $blade->addNamespace('pagination', $paginatorTpl);
        }
    }
    $modx->tpl->setTemplateExtension('.blade.php');
    $modx->tpl->setTemplatePath($tplFolder, true);
}
$modx->useConditional = $conditional && !$debug;
