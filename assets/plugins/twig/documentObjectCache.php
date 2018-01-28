<?php
$e = $modx->event;
$key = $method.'_'.$identifier;
if ($e->name == 'OnBeforeLoadDocumentObject') {
    if ($modx->cache->contains($key)) {
        $documentObject = $modx->cache->fetch($key);
        $e->_output = $documentObject;
    }
}
if ($e->name == 'OnLoadWebDocument') {
    if (isset($modx->documentObject['_template'])) {
        $modx->documentObject['template'] = $modx->documentObject['_template'];
        $modx->documentContent = $modx->documentObject['_output'];
    }
}
if ($e->name == 'OnAfterLoadDocumentObject') {
    if (!$modx->cache->contains($key)) {
        if ($modx->documentObject['template']) {
            $modx->documentObject['_template'] = $modx->documentObject['template'];
            $modx->documentObject['template'] = 0;
            $q = $modx->db->select('content', $modx->getFullTableName("site_templates"), "id = '{$modx->documentObject['_template']}'");
            $modx->documentObject['_output'] = $modx->db->getValue($q);
        }
        $modx->cache->save($key,$modx->documentObject);
    }
    $e->_output = $modx->documentObject;
}
