# EvoTwig
Set of plugins to replace Evo parser with Twig.

**Not supported anymore. Use https://github.com/Pathologic/EvoTwig2**

The advantages are:
* fast and powerful template engine, say goodbye to quad brackets;
* ability to cache data by key or temporary in snippets and plugins;
* no need to sanitize MODX tags in user input;
* storing templates in files - use your favourite editor with syntax hightlight and GIT;
* redesigning of the whole site becomes very simple;

## Installation
Go to site root and run command:
```
composer require pathologic/evo-twig
```

If everything is finished successfully, then install plugin.
The order of plugins is important: replaceTemplateTwig must be the first.

## Twig usage
Plugin searches for templates in such order:
* tpl-3_doc-5.tpl - use this if resource id=5 and resource template=3;
* doc-5.tpl - use this if resource id=5;
* tpl-3.tpl - use this if resource template=3.

It's also possible to specify file name in template content or in content field of resource with template _blank:
```
@FILE:main.tpl
```
If there's no main.tpl in templates folder, template will be set to blank.

### Variables to use in page templates
* _GET, _POST, _SESSION, _COOKIE;
* modx - DocumentParser object;
* documentObject - $modx->documentObject;
* config - $modx->config;
* resource - document fields and tvs;
* plh - $modx->placeholders;
* debug - true if debug mode is enabled in plugin settings;
* ajax - true if page is requested with ajax.

### Evo Addons
```
{{ runSnippet('SnippetName',{
    'param1':'value',
    'param2':'value'
})
}}

{{ getChunk('chunkName')}}

{{ parseChunk('chunkName', {'foo':'bar','bar':'baz'}) }}

{{ parseChunk('@CODE:[+foo+] is bar, [+bar+] is baz', {'foo':'bar','bar':'baz'}) }}

{{ '[*pagetitle*] [(site_name)] [!snippet!] {{chunk}}' | modxParser }}
```

### Using Twig in output chunks
When EvoTwig is installed, DocLister (and components that use DLTemplate class for templating) allows to use Twig in output chunks since version of 2.3.0 by adding 'T_' to chunk name prefix:
```
[[DocLister?
&templatePath=`assets/templates/tpl`
&templateExtension=`tpl`
&tpl=`@T_FILE:chunks/news`
]]
```

In this example, DocLister uses file assets/templates/tpl/chunks/news.tpl as output chunk (available variables are data, modx, DocLister):
```
<div class="col-sm-6">
    <a class="mainlink" href="{{ data['url'] }}">
        <span class="title">{{ data['pagetitle'] }}</span>
        {% if data['introtext'] %}
            <span class="intro">{{ data['introtext'] }}</span>
        {% endif %}
        <img class="img-responsive" alt="{{ data['pagetitle'] }}" src="{% if data['tv.image'] %}
            {{ runSnippet('sgThumb',{'input':data['tv.image'],'options':'555x416'}) }}
        {% else %}
            data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7
        {% endif %}" width="555" height="416">
    </a>
</div>
```

## Caching

Temporary cache:
```
{% cache 'map' {time: 86400} %}
  Sitemap: {{ runSnippet('mapBuild') | raw }} 
{% endcache %}
```

Cache by key:
```
{% cache 'footer' {key: 'footer'} %}
  © {{ runSnippet('copyright', { 'date': 2014, 'sep' : '‐' }) | raw }}. {{ config.site_name }}.
{% endcache %}
```

Or:
```
{% cache 'menu' {key: 'page' ~ documentObject.id} %}
{{ runSnippet('DLBuildMenu', {
  'idType' : 'parents',
  'parents' : 0,
  'maxDepth' : 2,
  'TplMainOwner' : '@CODE: <div class="primary"><div class="bl_center"><ul>[+dl.wrap+]</ul></div></div>',
  'TplSubOwner' : '@CODE: <ul>[+dl.wrap+]</ul>',
  'TplOneItem' : '@CODE: <li class="epanded [+dl.class+]"><a href="[+url+]" title="[+e.title+]">[+title+]</a>[+dl.submenu+]</li>',
  'noChildrenRowTPL' : '@CODE: <li class="[+dl.class+]"><a href="[+url+]" title="[+e.title+]">[+title+]</a>[+dl.submenu+]</li>',
}) | raw }}
{% endcache %}
```

Cache provider is accessible via $modx->cache:
```
$modx->cache->save('cache_key', $data); //cache data with key
$modx->cache->save('cache_key', $data, 600); //cache for 600 seconds
$modx->cache->contains('cache_key'); //check for cached data with key
$modx->cache->fetch('cache_key'); // get cached data by key
```
