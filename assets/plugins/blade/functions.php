<?php
if (! function_exists('modxParser')) {
    function modxParser($content)
    {
        $modx = evolutionCMS();
        $modx->minParserPasses = 2;
        $modx->maxParserPasses = 10;

        $out = DLTemplate::getInstance($modx)->parseDocumentSource($content, $modx);

        $modx->minParserPasses = -1;
        $modx->maxParserPasses = -1;

        return $out;
    }
}
if (! function_exists('evoCoreLang')) {
    function evoCoreLang($key)
    {
        global $_lang;

        return isset($_lang[$key]) ? $_lang[$key] : $key;
    }
}

if (! function_exists('modxDocumentObject')) {
    function modxDocumentObject($id, $values = true)
    {
        $modx = evolutionCMS();
        if (is_array($modx->documentObject) && $id === $modx->documentObject['id']) {
            $documentObject = $modx->documentObject;
        } else {
            $documentObject = $modx->db->query("SELECT * FROM ".$modx->getFullTableName('site_content')." WHERE id = ".(int)$id);
            $documentObject = $modx->db->getRow($documentObject);
            
            if($documentObject === null) {
                $documentObject = array();
            } else {
                $rs = $modx->db->select("tv.*, IF(tvc.value!='',tvc.value,tv.default_text) as value", $modx->getFullTableName("site_tmplvars") . " tv
                        INNER JOIN " . $modx->getFullTableName("site_tmplvar_templates") . " tvtpl ON tvtpl.tmplvarid = tv.id
                        LEFT JOIN " . $modx->getFullTableName("site_tmplvar_contentvalues") . " tvc ON tvc.tmplvarid=tv.id AND tvc.contentid = '{$documentObject['id']}'", "tvtpl.templateid = '{$documentObject['template']}'");
                $tmplvars = array();
                while ($row = $modx->db->getRow($rs)) {
                    $tmplvars[$row['name']] = array(
                        $row['name'],
                        $row['value'],
                        $row['display'],
                        $row['display_params'],
                        $row['type']
                    );
                }
                $documentObject = array_merge($documentObject, $tmplvars);
            }
        }
        
        if ($values === true) {
            foreach ($documentObject as $key => $value) {
                if (is_array($value)) {
                    $documentObject[$key] = isset($value[1]) ? $value[1] : '';
                }
            }
        }
        return $documentObject;
    }
}
