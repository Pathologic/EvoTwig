<?php
if (!isset($modx->cache)) return;

$lifetime = isset($lifetime) ? $lifetime : null;
$out = '';
if (!empty($key) && isset($snippetName)) {
    if (isset($keyGenerator)) {
        if ((is_object($keyGenerator) && ($keyGenerator instanceof \Closure)) || is_callable($keyGenerator)) {
            $key = call_user_func_array($keyGenerator, ['modx' => $modx, 'key' => $key]);
        } else {
            $key = $this->modx->runSnippet($keyGenerator, ['key' => $key]);
        }
    }
    
    if ($modx->cache->contains($key)) {
        $out = $modx->cache->fetch($key);
    } else {
        $out = $modx->runSnippet($snippetName, $params);
        $modx->cache->save($key, $out, $lifetime);
    }
}

return $out;
