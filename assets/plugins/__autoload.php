<?php
include_once(MODX_MANAGER_PATH."/composer/vendor/autoload.php");

$modx->doc = new modResource($modx);
$modx->tpl = DLTemplate::getInstance($modx);
$modx->fs = \Helpers\FS::getInstance();

switch(true){
	case function_exists('apc_cache_info'):{
		$modx->cache = new \Doctrine\Common\Cache\ApcCache();
		break;
	}
	case class_exists('Memcache'):{
		$modx->cache = new \Doctrine\Common\Cache\MemcacheCache();

		$memcache = new Memcache();
		$memcache->connect('localhost', 11211);
		$modx->cache->setMemcache($memcache);
		break;
	}
	case class_exists('Memcached'):{
		$modx->cache = new \Doctrine\Common\Cache\MemcachedCache();

		$memcached = new Memcached();
		$memcached->connect('localhost', 11211);
		$modx->cache->setMemcache($memcached);
		break;
	}
	case class_exists('SQLite3'):{
		$modx->cache = new \Doctrine\Common\Cache\SQLite3Cache(
			new SQLite3(MODX_BASE_PATH.'assets/cache/sqlite.db'), 'cache'
		);
		break;
	}
	default:{
		$modx->cache = new Doctrine\Common\Cache\FilesystemCache(
			MODX_BASE_PATH.'assets/cache/template/'
		);
	}
}
$modx->cache->setNamespace($modx->getConfig('site_name'));