<?php

    define('PATH', $_SERVER['DOCUMENT_ROOT']);

    if(!defined('PATH')) { die('ACCESS DENIED'); }
    Error_Reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    header('Content-Type: text/html; charset=utf-8');

    session_start();

    define("VALID_CMS", 1);

    include(PATH.'/core/cms.php');
    $inCore = cmsCore::getInstance();

    cmsCore::loadClass('page');
    cmsCore::loadClass('user');

    $inDB   = cmsDatabase::getInstance();
    $inConf = cmsConfig::getInstance();
    $inUser = cmsUser::getInstance();
    $inPage = cmsPage::getInstance();
    
    if (!$inUser->update()) { cmsCore::halt(); }

    if (!$inUser->is_admin){ cmsCore::halt(); }
    
    @set_time_limit(0);
    
    $sql = "SELECT id, content FROM cms_user_wall LIMIT 1250";
    $result = $inDB->query($sql);
    
    if ($inDB->num_rows($result)){
        $items = array();
        while($item = $inDB->fetch_assoc($result)){
            $items[] = $item;
        }
    } else {
        cmsCore::halt();
    }
    
    if($items){
        
        $rules = array();
        $rules[] = array('search'=>"'<div\sclass=\"bb_quote\"><strong>(.*?)<\/strong><br>(.*?)<div\sclass=\"quote\">(.*?)<\/div>(.*?)<\/div>'si" , 'replace'=>"[quote=$1]$3[/quote]");

        $rules[] = array('search'=>"'<div\sclass=\"bb_tag_code\">(.*?)background:none;\svertical-align:top;\">(.*?)<\/div>(.*?)<\/li>(.*?)<\/ol>(.*?)<\/pre>(.*?)<\/div>'si" , 'replace'=>"[code=php]$2[/code]");
        $rules[] = array('search'=>"'<div\sclass=\"bb_tag_spoiler\">(.*?)<strong>(.*?)<\/strong>(.*?)display:none\">(.*?)<\/div>(.*?)<\/div>'si" , 'replace'=>"[spoiler=$2]$4[/spoiler]");

        $rules[] = array('search'=>"'<font\sface=\"(.*?)\">(.*?)</font>'si" , 'replace'=>"[font='$1']$2[/font]");
        $rules[] = array('search'=>"'<div\sstyle=\"background-color:(.*?)\">(.*?)</div>'si", 'replace'=>"[bgcolor=$1]$2[/bgcolor]");
        $rules[] = array('search'=>"'<div\sstyle=\"background-color:(.*?)\">(.*?)</div>'si", 'replace'=>"[bgcolor=$1]$2[/bgcolor]");
        $rules[] = array('search'=>"'<strong>(.*?)</strong>'si" , 'replace'=>"[b]$1[/b]");
        $rules[] = array('search'=>"'<i>(.*?)</i>'si" , 'replace'=>"[i]$1[/i]");
        $rules[] = array('search'=>"'<u>(.*?)</u>'si" , 'replace'=>"[u]$1[/u]");
        $rules[] = array('search'=>"'<s>(.*?)</s>'si" , 'replace'=>"[s]$1[/s]");
        $rules[] = array('search'=>"'<div\salign=\"(.*?)\">(.*?)</div>'si" , 'replace'=>"[align=$1]$2[/align]");
        $rules[] = array('search'=>"'<h2\sclass=\"bb_tag_h2\">(.*?)</h2>'si" , 'replace'=>"[h2]$1[/h2]");
        $rules[] = array('search'=>"'<h3\sclass=\"bb_tag_h3\">(.*?)</h3>'si" , 'replace'=>"[h3]$1[/h3]");
        $rules[] = array('search'=>"'<a\shref=\"mailto:(.*?)\">(.*?)<\/a>'si" , 'replace'=>"[email]$1[/email]");
        $rules[] = array('search'=>"'<img\ssrc=\"\/images\/smilies/(.*?).gif\"(.*?)>'si", 'replace'=>":$1:");
        $rules[] = array('search'=>"'<div\sclass=\"bb_tag_video\">(.*?)<\/div>'si" , 'replace'=>"[video]$1[/video]");

        $rules[] = array('search'=>"'<div\sclass=\"forum_lostimg\">(.*?)\"(.*?)\"(.*?)<\/div>'si" , 'replace'=>"[IMG]$2[/IMG]");
        $rules[] = array('search'=>"'<div\sclass=\"forum_zoom\"(.*?)href=\"(.*?)\"(.*?)forum_zoom_text(.*?)<\/div>(.*?)<\/div>'si" , 'replace'=>"[IMG]$2[/IMG]");
        $rules[] = array('search'=>"'<div\sclass=\"bb_img\"(.*?)src=\"(.*?)\"(.*?)<\/div>'si" , 'replace'=>"[IMG]$2[/IMG]");
        
        foreach($items as $item){
            
            $html = '';
            $html = $item['content'];
            foreach($rules as $rule) {
                $html = str_replace("<br />", "\n", $html);
            }
            
            $html = preg_replace_callback("'<a\shref=\"\/go\/url=(.*?)\"(.*?)>(.*?)<\/a>'si", "decodeUrl", $html);

            foreach($rules as $rule) {
                $html = preg_replace($rule['search'], $rule['replace'], $html);
            }
            
            $inDB->update('cms_user_wall', array('content_bbcode'=>$inDB->escape_string($html)), $item['id']);
            
        }
        
        cmsCore::halt('Работа успешно завершена');
        
    }
    
function decodeUrl($matches){
    $url = $matches[1];

    $url = str_replace('--q--', '?', $url);
    if (!$url) { return $url; }

    $url = (mb_strpos($url, '-') === 0) ? htmlspecialchars_decode(base64_decode(ltrim($url, '-'))) : $url;

    if(mb_strstr($url, '..')){ return $url; }

    if (mb_strstr($url, 'http:/')){
            if (!mb_strstr($url, 'http://')){ $url = str_replace('http:/', 'http://', $url); }
    }
    if (mb_strstr($url, 'https:/')){
            if (!mb_strstr($url, 'https://')){ $url = str_replace('https:/', 'https://', $url); }
    }

    $str = '[url='.$url.']'.$matches[3].'[/url]';

    return $str;
}

?>
