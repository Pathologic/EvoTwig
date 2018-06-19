<?php
if (! function_exists('modxParser')) {
    function modxParser($content)
    {
        $modx = evolutionCMS();
        $modx->minParserPasses = 2;
        $modx->maxParserPasses = 10;

        $out = $modx->tpl->parseDocumentSource($content, $modx);

        $modx->minParserPasses = -1;
        $modx->maxParserPasses = -1;

        return $out;
    }
}
if (! function_exists('evoCoreLang')) {
    function evoCoreLang($key)
    {
        global $_lang;

        return get_by_key($_lang, $key, $key);
    }
}
