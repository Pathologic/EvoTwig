<?php
/**
 * @props &debug=Debug;list;true,false;true &modxcache=MODX cache;list;true,false;false &conditional=Conditional;list;true,false;true
 */
$debug = (isset($debug) && $debug == 'true') ? true : false;
$modxcache = (isset($modxcache) && $modxcache == 'true') ? true : false;
$conditional = (isset($conditional) && $conditional == 'true') ? true : false;
$cachePath = 'assets/cache/template/';

switch($modx->event->name){
	case 'OnWebPageInit':
	case 'OnManagerPageInit':
	case 'OnPageNotFound':{
		if(class_exists('Twig_Autoloader')){
			Twig_Autoloader::register();
			$loader = new Twig_Loader_Filesystem(MODX_BASE_PATH.'/assets/element/template/');
			$modx->twig = new Twig_Environment($loader, array(
				'cache' => MODX_BASE_PATH.$cachePath,
				'debug' => $debug
			));
			$modx->twig->addExtension(new Twig_Extension_Debug());

			$cacheProvider = new Asm89\Twig\CacheExtension\CacheProvider\DoctrineCacheAdapter($modx->cache);

			$modx->twig->addExtension(new Asm89\Twig\CacheExtension\Extension(
				new Asm89\Twig\CacheExtension\CacheStrategy\IndexedChainingCacheStrategy(array(
					'time' => new Asm89\Twig\CacheExtension\CacheStrategy\LifetimeCacheStrategy($cacheProvider),
					'key' => new Asm89\Twig\CacheExtension\CacheStrategy\GenerationalCacheStrategy($cacheProvider, new \AN\Twig\KeyGenerator(), 0)
				))
			));

			$PhpFunctionExtension = new Umpirsky\Twig\Extension\PhpFunctionExtension();
			$PhpFunctionExtension->allowFunctions(array(
				'count',
				'get_included_files',
				'filesize',
				'get_key',
				'intval',
				'plural'
			));

			$modx->twig->addExtension($PhpFunctionExtension);
			/**
			* {{ runSnippet('example') | modxParser }}
			* {{ '[*id*]' | modxParser }}
			*/
			$modx->twig->addFilter('modxParser', new Twig_SimpleFilter('modxParser', function($content) use($modx){
					$modx->minParserPasses = 2;
					$modx->maxParserPasses = 10;

					$out = $modx->tpl->parseDocumentSource($content, $modx);

					$modx->minParserPasses = -1;
					$modx->maxParserPasses = -1;
					return $out;
				})
			);

			/**
			* {{ makeUrl(20) }}
			* {{ makeUrl(20, {page: 2}) }}
			* {{ makeUrl(20, {}, false) }}
			* {{ makeUrl(20, {page: 2}, false) }}
			*/
			$modx->twig->addFunction(
				new Twig_SimpleFunction('makeUrl', function($id, array $args = array(), $absolute = true) use($modx){
					return $modx->makeUrl($id, '', http_build_query($args), $absolute ? 'full' : '');
				})
			);

			$modx->twig->addFunction(new Twig_SimpleFunction('runSnippet', array($modx, 'runSnippet')));
			$modx->twig->addFunction(new Twig_SimpleFunction('getChunk', array($modx->tpl, 'getChunk')));
			$modx->twig->addFunction(new Twig_SimpleFunction('parseChunk', array($modx->tpl, 'parseChunk')));

			$modx->twig->getExtension('core')->setNumberFormat(0, ",", " ");
		}else{
			include_once(MODX_BASE_PATH."assets/snippets/DocLister/lib/xnop.class.php");
			$modx->twig = new xNop;
		}
		$modx->useConditional = $conditional && !$debug;
		break;
	}
	case 'OnAfterLoadDocumentObject':{
		$ext = ($modx->twig instanceof xNop) ? '.html' : '.twig.html';

		switch(true){
			case file_exists(MODX_BASE_PATH.'assets/element/template/'.'tpl-'.$documentObject['template'].'_doc-'.$documentObject['id'].$ext):{
				$modx->docTemplate = 'tpl-'.$documentObject['template'].'_doc-'.$documentObject['id'];
				$documentObject['oldTemplate'] = $documentObject['template'];
				$documentObject['template'] = 0;
				break;
			}
			case file_exists(MODX_BASE_PATH.'assets/element/template/doc-'.$documentObject['id'].$ext):{
				$modx->docTemplate = 'doc-'.$documentObject['id'];
				$documentObject['oldTemplate'] = $documentObject['template'];
				$documentObject['template'] = 0;
				break;
			}
			case file_exists(MODX_BASE_PATH.'assets/element/template/tpl-'.$documentObject['template'].$ext):{
				$modx->docTemplate = 'tpl-'.$documentObject['template'];
				$documentObject['oldTemplate'] = $documentObject['template'];
				$documentObject['template'] = 0;
				break;
			}
			default:{
				$modx->docTemplate = 0;
			}
		}
		$modx->event->_output = $documentObject;
		break;
	}
	case 'OnLoadWebDocument':{
		if(!empty($modx->docTemplate)){
			if($modx->twig instanceof xNop){
				ob_start();
				include(MODX_BASE_PATH.'assets/element/template/'.$modx->docTemplate.'.html');
				$modx->documentContent = ob_get_contents();
				ob_end_clean();
			}else{
				$modx->minParserPasses = -1;
				$modx->maxParserPasses = -1;
				$tpl = $modx->twig->loadTemplate($modx->docTemplate.'.twig.html');
				$modx->documentContent = $tpl->render(array(
					'modx' => $modx,
					'documentObject' => $modx->documentObject,
					'debug' => $debug,
					'_GET' => $_GET,
					'_POST' => $_POST,
					'_COOKIE' => $_COOKIE,
					'_SESSION' => $_SESSION
				));
			}
		}
		break;
	}
	case 'OnCacheUpdate':{
		$modx->cache->deleteAll();
		break;
	}
	case 'OnWebPagePrerender':{
		if($debug || !$modxcache){
			$modx->documentObject['cacheable'] = 0;
		}
		break;
	}
}