<?php
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

// Configuration
// Note that you can set several directories where your templates are located
$pathsToTemplates = [
    realpath(MODX_BASE_PATH . $tplFolder)
];
$pathToCompiledTemplates = realpath(MODX_BASE_PATH . $cachePath);
// Dependencies
$filesystem = new Illuminate\Filesystem\Filesystem;
$eventDispatcher = new Illuminate\Events\Dispatcher(new Illuminate\Container\Container);
// Create View Factory capable of rendering PHP and Blade templates
$viewResolver = new Illuminate\View\Engines\EngineResolver;
$bladeCompiler = new Illuminate\View\Compilers\BladeCompiler($filesystem, $pathToCompiledTemplates);
$bladeCompiler->directive('modxParser', function ($content) {
    return '<?php echo modxParser(' . $content . ');?>';
});
$bladeCompiler->directive('lang', function ($content) use ($modx) {
    return '<?php echo evoCoreLang(' . $content . ');?>';
});

$viewResolver->register('blade', function () use ($bladeCompiler) {
    return new Illuminate\View\Engines\CompilerEngine($bladeCompiler);
});
$viewResolver->register('php', function () {
    return new Illuminate\View\Engines\PhpEngine;
});
$viewFinder = new Illuminate\View\FileViewFinder($filesystem, $pathsToTemplates);
$viewFactory = new Illuminate\View\Factory($viewResolver, $viewFinder, $eventDispatcher);
$viewFactory->addNamespace('cache', MODX_BASE_PATH . $cachePath);

if (class_exists(Illuminate\Pagination\Paginator::class)) {
    Illuminate\Pagination\Paginator::viewFactoryResolver(function () use ($modx) {
        return $modx->blade;
    });

    Illuminate\Pagination\Paginator::currentPathResolver(function () {
        return rtrim(preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']), '/');
    });

    Illuminate\Pagination\Paginator::currentPageResolver(function ($pageName = 'page') {
        $page = isset($_GET[$pageName]) && is_scalar($_GET[$pageName]) ? (int)$_GET[$pageName] : 1;
        if (filter_var($page, FILTER_VALIDATE_INT) !== false && $page >= 1) {
            return $page;
        }
        return 1;
    });
    $paginatorTpl = MODX_BASE_PATH . rtrim($tplFolder, '/') . '/' . 'pagination';
    if (is_readable($paginatorTpl) && is_dir($paginatorTpl)) {
        $viewFactory->addNamespace('pagination', $paginatorTpl);
    }
}
$modx->blade = $viewFactory;
