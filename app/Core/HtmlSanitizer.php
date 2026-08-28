<?php
namespace App\Core;
final class HtmlSanitizer
{
    private const TAGS=['div','p','br','h2','h3','h4','strong','b','em','i','u','ul','ol','li','blockquote','a','code','pre','hr','table','thead','tbody','tr','th','td'];
    public static function clean(string $html): string
    {
        if(trim($html)==='')return '';$doc=new \DOMDocument('1.0','UTF-8');$previous=libxml_use_internal_errors(true);$doc->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);libxml_clear_errors();libxml_use_internal_errors($previous);self::sanitizeNode($doc);$wrapper=$doc->getElementsByTagName('div')->item(0);$result='';if($wrapper)foreach($wrapper->childNodes as $child)$result.=$doc->saveHTML($child);return $result;
    }
    private static function sanitizeNode(\DOMNode $node): void
    {
        foreach(iterator_to_array($node->childNodes) as $child){if($child instanceof \DOMElement){$tag=strtolower($child->tagName);if(!in_array($tag,self::TAGS,true)){while($child->firstChild)$child->parentNode?->insertBefore($child->firstChild,$child);$child->parentNode?->removeChild($child);continue;}foreach(iterator_to_array($child->attributes) as $attribute){$name=strtolower($attribute->name);if($tag==='a'&&$name==='href'){if(!preg_match('~^(https?://|mailto:|/)~i',$attribute->value))$child->removeAttribute($name);}else $child->removeAttribute($name);}if($tag==='a'){$child->setAttribute('target','_blank');$child->setAttribute('rel','noopener');}}self::sanitizeNode($child);}
    }
}
