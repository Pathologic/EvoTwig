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
```
