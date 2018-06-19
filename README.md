
# EvoBlade
Set of plugins to replace Evo parser with Blade.

The advantages are:
* fast and powerful template engine, say goodbye to quad brackets;
* ability to cache data by key or temporary in snippets and plugins;
* no need to sanitize MODX tags in user input;
* storing templates in files - use your favourite editor with syntax hightlight and GIT;
* redesigning of the whole site becomes very simple;

## Installation
Go to site root and run command:
```
composer require jenssegers/blade dev-master
```

If everything is finished successfully, then install plugin.
The order of plugins is important: replaceTemplateBlade must be the first.

## Blade usage
Plugin searches for templates in such order:
* tpl-3_doc-5.blade.php - use this if resource id=5 and resource template=3;
* doc-5.blade.php - use this if resource id=5;
* tpl-3.blade.php - use this if resource template=3.

It's also possible to specify file name in template content or in content field of resource with template _blank:
```
@FILE:main.blade.php
```
If there's no main.blade.php in templates folder, template will be set to blank.

### Variables to use in page templates
* modx - DocumentParser object;
* debug - true if debug mode is enabled in plugin settings;

### Evo Addons
```php
{{ $modx->runSnippet('SnippetName', [
        'param1' => 'value',
        'param2' => 'value'
    ]);
}}

{{ $modx->getChunk('chunkName')}}

@modxParser('[*pagetitle*] [(site_name)] [!snippet!] {{chunk}}')

<?php $chunk = 'test {{ $modx->getConfig("site_name") }} {{ $param1 }} {!! $param1 !!}'; ?>
{{ $modx->tpl->parseChunk('@B_CODE:'.$chunk, ['param1' => 'value with "quote"']) }}
```

### Usage with Laravel paginator
#### Step 1 / Install
```bash
composer require illuminate/pagination
```

#### Step 2 / Register paginator
```php
Illuminate\Pagination\Paginator::viewFactoryResolver(function () use($modx){
    return $modx->laravel->get('view');
});

Illuminate\Pagination\Paginator::currentPathResolver(function () {
    return rtrim(preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']), '/');
});

Illuminate\Pagination\Paginator::currentPageResolver(function ($pageName = 'page') {
    $page = get_by_key($_GET, $pageName, 1, 'is_scalar');
    if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int) $page >= 1) {
        return (int) $page;
    }
    return 1;
});
```

#### Step 3 / Views
Copy files from the [vendor/illuminate/pagination/resources/views](https://github.com/illuminate/pagination/tree/master/resources/views) to you templates folder and customization this is blade.

#### Step 4 / Usage with array
```php
$items = array_map(function ($value) {
    return (object)[
        'pagetitle' => 'Blog post #' . $value,
        'id' => $value,
    ];
}, range(1,1000));

// Get current page from query string
$currentPage  = isset($_GET['page']) ? (int) $_GET['page'] : 1;

// Items per page
$perPage      = 10;

// Get current items calculated with per page and current page
$currentItems = array_slice($items, $perPage * ($currentPage - 1), $perPage);
$paginator = new Illuminate\Pagination\Paginator($items, 10, $currentPage);
```

### Step 4 / Usage with DBAPI
```php
$query = "SELECT * FROM ". $modx->getDatabase()->getFullTableName('site_content') . " WHERE parent=0 ORDER BY pageitle";
$query = $modx->getDatabase()->query($query);
$data = $modx->getDatabase()->makeArray($data);

$currentPage  = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$perPage      = 10;
$currentItems = array_slice($items, $perPage * ($currentPage - 1), $perPage);
$paginator = new Illuminate\Pagination\Paginator($items, 10, $currentPage);
```

### Step 4 / Usage with agelxnash/modx-evo-database
```php
$paginator = $modx->getDatabase()->getDriver()->getCapsule()->table('site_content')
        ->where('parent', '=', 0)
        ->orderBy('pagetitle', 'DESC')
        ->paginate(10);
$paginator = Illuminate\Database\Capsule\Manager::table('site_content')
        ->where('parent', '=', 0)
        ->orderBy('pagetitle', 'DESC')
        ->paginate(10);
$paginator = AgelxNash\Modx\Evo\Database\Models\SiteContent::where('parent', '=', 0)
        ->orderBy('pagetitle', 'DESC')
        ->paginate(10);
```

### Step 5 / Render
```php
foreach ($paginator->items() as $blogPost) {
   echo '<strong>' . $blogPost->id . '</strong> / ' . $blogPost->pagetitle . '<br />';
}
echo $paginator->render();
```
You can read more about pagination in the [documentation](https://laravel.com/docs/5.6/pagination).