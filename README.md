# EvoTwig
Set of plugins to replace Evo parser with Twig.

Install plugin, then go to /manager/composer/ and run:
```
composer install
```

Plugin searches for templates in the next order:
* tpl-3_doc-5.tpl - use this if resource id=5 and resource template=3;
* doc-5.tpl - use this if resource id=5;
* tpl-3.tpl - use this if resource template=3.

It's also possible to specify file name in template content:
```
@FILE:main.tpl
```
If there's no main.tpl in templates folder, template will be set to blank.

##Variables to use in templates
* _GET, _POST, _SESSION, _COOKIE;
* modx - DocumentParser object;
* documentObject - $modx->documentObject;
* config - $modx->config;
* resource - document fields and tvs;
* plh - $modx->placeholders.

##Caching
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