
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

#### Step 2 / Views
Copy files from the [vendor/illuminate/pagination/resources/views](https://github.com/illuminate/pagination/tree/master/resources/views) to you templates folder and customization this is blade.

#### Step 3.1 / Usage with array
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

### Step 3.2 / Usage with DBAPI
```php
$query = "SELECT * FROM ". $modx->getDatabase()->getFullTableName('site_content') . " WHERE parent=0 ORDER BY pageitle";
$query = $modx->getDatabase()->query($query);
$data = $modx->getDatabase()->makeArray($data);

$currentPage  = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$perPage      = 10;
$currentItems = array_slice($items, $perPage * ($currentPage - 1), $perPage);
$paginator = new Illuminate\Pagination\Paginator($items, 10, $currentPage);
```

### Step 3.3 / Usage with [agelxnash/modx-evo-database](https://github.com/AgelxNash/modx-evo-database)
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

### Step 4 / Render
```php
foreach ($paginator->items() as $blogPost) {
   echo '<strong>' . $blogPost->id . '</strong> / ' . $blogPost->pagetitle . '<br />';
}
echo $paginator->render();
```
You can read more about pagination in the [documentation](https://laravel.com/docs/5.6/pagination).

## Customization
### Create additional plugin
Support the [evoBabel](https://github.com/webber12/evobabel-0.2) directive
```php
/**
 * evoBabelBlade 
 *
 * evoBabel - additional blade directive
 *
 * @category    plugin
 * @version     1.0.0
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author      Agel_Nash
 * @internal    @events OnWebPageInit,OnManagerPageInit,OnPageNotFound
 * @internal    @installset base
 * @internal    @disabled 1
 */
if (! defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}
try {
	if(!function_exists('_evoBabel')) {
		function _evoBabel($str, $useEmpty = true){
			$out = evolutionCMS()->runSnippet('lang', array('a' => $str));
			if(empty($out) && $useEmpty !== false) {
				$out = $str;
			}

			return $out;
		}
	}

	$compiller = $modx->laravel->get('view.engine.resolver')->resolve('blade')->getCompiler();
	$compiller->directive('evoBabel', function ($content) use ($modx) {
        return '<?php echo  _evoBabel(' . $content . ');?>';
    });
} catch (Exception $exception) {
    $modx->messageQuit($exception->getMessage());
}
```
### Usage
```php
@evoBabel('Example text')
```