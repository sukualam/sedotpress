<?php
/* SEDOTPRESS ENGINE
 * Version : 0.1.7
 * Source  : https://github.com/sukualam/sedotpress
 * News    : http://sedot.space
 * License : MIT
 */
 
session_start();

// comment to debug
error_reporting(0);

# Configuration
date_default_timezone_set("Asia/Jakarta");
#setlocale(LC_ALL,'en_US');
setlocale(LC_ALL,'id_ID');
define('ADMIN_NICKNM','admin');
define('ADMIN_PASSWD','root');
define('SITE_TITLE','Sedotpress');
define('SITE_DESC','my sedotpress blog');
define('GRAV_EMAIL','example@example.com');
define('SITE_URL','http://localhost');
define('COMMENT_SPLIT',35);
define('SEDOT_VER','v0.1.7');
define('THEME','sedot_bootstrap');

# Sedotpress URL Routing
$urlBase = rtrim(SITE_URL,'/') . '/';
$urlExplode = explode('/',$urlBase,4);
end($urlExplode);
$endUrlExplodeKey = key($urlExplode);
if($urlExplode[$endUrlExplodeKey] == ''){
	$req = $_SERVER['REQUEST_URI'];
}else{
	$getCutLength = strlen($urlExplode[$endUrlExplodeKey]);
	$req = substr($_SERVER['REQUEST_URI'],$getCutLength - 1);	
}
$getRequest = explode('/',strtolower($req));
array_shift($getRequest);
$trimPage = $getRequest;
$i = 0;
while($trimPage[$i]){
	if(strtolower($trimPage[$i]) == 'page'){
		unset($trimPage[$i]);
		$a = $i + 1;
		while($trimPage[$a]){
			unset($trimPage[$a]);
			$a++;
		}
	}
	$i++;
}
$actualUrl = $urlBase . implode('/',$trimPage);
$actualUrl = trim($actualUrl,'/');
define('ACTUAL_URL',$actualUrl);
# Sedotpress Class
class sedot{
function __construct(){
	if(!is_dir('sp_post')){
		mkdir('sp_post');
	}
	if(!is_dir('sp_static')){
		mkdir('sp_static');
		mkdir('sp_static/index');
		mkdir('sp_static/xml');
		mkdir('sp_static/comment');
		mkdir('sp_static/misc');
	}
	if(!is_dir('sp_theme')){
		mkdir('sp_theme');
	}
}
function _url($x = '/'){
	return rtrim(SITE_URL,'/').'/' .trim($x,'/');
}
function navi($a,$b){
	$this->total = $a;
	$this->page = $b;
}
function currentMethod($class){
    $allMethod = get_class_methods($class);
    if($parentClass = get_parent_class($class)){
        $parentMethod = get_class_methods($parentClass);
        $currentMethod = array_diff($allMethod, $parentMethod);
    }else{
        $currentMethod = $allMethod;
    }
    return($currentMethod);
}
function removeExistingFile($path){
	if(file_exists($path)){
		if(unlink($path)){
			return true;
		}else{
			return false;
		}
	}else{
		return false;
	}
}
function toJson($str){
	$jsonEncode = json_encode($str,
	JSON_HEX_TAG | JSON_HEX_APOS |
	JSON_HEX_QUOT | JSON_HEX_AMP |
	JSON_UNESCAPED_UNICODE);
	return $jsonEncode;
}
function createWidget($widgetId = 1){
	$this->primaryHTML[$widgetId] = array();
}
function addWidget($widgetId,$str,$order = 'after'){
	if($order == 'after'){
		array_push($this->primaryHTML[$widgetId],$str);
	}else{
		array_unshift($this->primaryHTML[$widgetId],$str);
	}
}
function widgetSection($opt = '1'){
	if(isset($opt)){
		foreach($this->primaryHTML[$opt] as $key => $widget){
			printf('%s',$widget);
		}
	}
}
function down($a,$x){
	if(count($a)-1 > $x){
		$b = array_slice($a,0,$x,true);
		$b[] = $a[$x+1];
		$b[] = $a[$x];
		$b += array_slice($a,$x+2,count($a),true);
		return($b);
	}else{
		return $a;
	}
}
function up($a,$x){
	if($x > 0 && $x < count($a)){
		$b = array_slice($a,0,($x-1),true);
		$b[] = $a[$x];
		$b[] = $a[$x-1];
		$b += array_slice($a,($x+1),count($a),true);
		return($b);
	}else{
		return $a;
	}
}
function enableWidget(){
	$widgetContainer = '<div class="widget widget-%1$s-%2$s col-md-12">
						%3$s
						%4$s
						</div>';
						
	$widgetTitle = '<h2 class="widget-title">%s</h2>';
	
	$templateWidget = self::loadJson('misc','_widget-'.THEME);
	$getConfig = parse_ini_file('sp_theme/'.THEME.'/config.ini');
	foreach($getConfig['area'] as $widget){
		self::createWidget($widget);
		foreach($templateWidget[$widget] as $widgetKey => $content){
			if($content['title'] != ''){
				$content['title'] = sprintf($widgetTitle,$content['title']);
			}
			if($content['type'] == 'html'){
				$code = $content['code'];
			}else{
				$code = widget::$content['type']();
			}
			self::addWidget($widget,sprintf(
			$widgetContainer,
			$widget,
			$widgetKey,
			$content['title'],
			$code
			));
		}
	}
	skipCreateWidget:
}
function toFile($path,$data,$truncate = false){
	if($truncate){
		self::removeExistingFile($path);
	}
	$openFile = fopen($path,"a+");
	if($openFile == false){
		return false;
	}else{
		$writeData = fwrite($openFile,$data);
		fclose($openFile);
		return true;
	}
}

function loadFile($path,$stream = false){
	@$openFile = fopen($path,"r");
	if(!$stream){
		@$readFile = fread($openFile,filesize($path));
	}else{
		@$readFile = fgets($openFile);
	}
	$containData = $readFile;
	@fclose($openFile);
	return $containData;
}
function loadPost($postnum){
	$loadPost = self::loadFile("sp_post/post{$postnum}",false);
	$this->postPart = json_decode($loadPost,true);
	return $this->postPart;
}
function formatPost($filename){
	$postPart = self::loadPost($filename);
	$postPart['tag'] = explode(',',$postPart['tag']);
	return $postPart;
}
function endKey($array, $increment = false, $trim = 'post'){
	if(is_null($array)){
		$key = 0;
	}else{
		end($array);
		$key = trim(key($array),$trim);
	}
	if($increment){
		$key += 1;
	}
	return $key;
}
function assignPostId($prefix = NULL){
	/*
	 * This actually used for assigning post Id's in "publish" and "draft" mode
	 * This function open 'sp_static/index/post.json'
	 */
	$postList = self::loadJson('index','post');
	$newKey = self::endKey(array_reverse($postList[0],true),true);
	if(is_null($newKey)){
		$newKey = 1;
		}
	if(is_null($prefix)){
		$prefix = 'post';
		}
	/* Skip existing file */
	while(file_exists(sprintf('sp_post/%s%s',$prefix,$newKey))){
		$newKey += 1;
	}
	$key = $prefix . $newKey;
	return $key;
}
function getImg($string){
	preg_match('/src="([^"]*)"/',$string,$matches);
	if(!isset($matches[1]) || $matches[1] == "" || empty($matches[1])){
		return false;
	}else{
		return $matches[1];
	}
}
function plainString($text){
	// Copyright (c) 2008, David R. Nadeau, NadeauSoftware.com.
	// All rights reserved.
	$text = preg_replace(
		array(
			'@<head[^>]*?>.*?</head>@siu',
			'@<style[^>]*?>.*?</style>@siu',
			'@<script[^>]*?.*?</script>@siu',
			'@<object[^>]*?.*?</object>@siu',
			'@<embed[^>]*?.*?</embed>@siu',
			'@<applet[^>]*?.*?</applet>@siu',
			'@<noframes[^>]*?.*?</noframes>@siu',
			'@<noscript[^>]*?.*?</noscript>@siu',
			'@<noembed[^>]*?.*?</noembed>@siu',
			'@<((br)|(hr))@iu',
			'@</?((address)|(blockquote)|(center)|(del))@iu',
			'@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
			'@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
			'@</?((table)|(th)|(td)|(caption))@iu',
			'@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
			'@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
			'@</?((frameset)|(frame)|(iframe))@iu',
		),
		array(
			' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
			"\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
			"\n\$0", "\n\$0",
		),
		$text);
	return strip_tags($text);
}
function getExcerpt($string,$length){
	$string 	 = self::plainString($string);
	$excerpt     = substr($string,0,$length);
	$cutlastword = strrpos($excerpt, ' ');
	$excerpt     = substr($excerpt, 0, $cutlastword);
	return $excerpt;
}
function splitMeta($str){
	$meta['date'] = $str[0];
	$meta['title'] = $str[1];
	$meta['permalink'] = $str[2];
	$meta['tag'] = $str[3];
	return $meta;
}
function create_index($itemPerIndex = 5){
	$sitemapContainer = '
	<?xml version="1.0" encoding="UTF-8"?>
	<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">%1$s
	</urlset>';
	$sitemapItem = '
	<url><loc>%1$s/%2$s</loc></url>';
	$rssContainer = '
	<?xml version="1.0" encoding="UTF-8" ?>
	<rss version="2.0">
	<channel>
	<title>%1$s</title>
	<link>%2$s</link>
	<description>%3$s</description>%4$s
	</channel>
	</rss>';
	$rssItem = '
	<item>
	<title>%1$s</title>
	<link>%2$s</link>
	<description>%3$s</description>
	</item>';
	$dir = "sp_post/";
	$archiveDate = '';
	$countFileSize = '';
	$scandir = array_diff(scandir($dir),array('..','.'));
	natsort($scandir);
	$reverseOrder = array_reverse($scandir);
	foreach($reverseOrder as $file){
		$countFileSize += filesize("sp_post/{$file}");
		$dec = self::loadPost(ltrim($file,'post'));
		$postDate = explode(" ",date('Y m d',strtotime($dec['date'])));
		$splitTag = explode(",",$dec['tag']);
		$postTag[] = $splitTag;
		@$meta = array($dec['date'],$dec['title'],$dec['permalink'],$splitTag);
		if($dec['status'] == "publish"){
			$container[trim($file,'post')] = $meta;
			$countAllPost += 1;
		}else{
			$draft_container[trim($file,'post')] = $meta;
		}
		@$archiveDate[$postDate[0]][$postDate[1]][$postDate[2]] += 1;
	}
	foreach($postTag as $many){
		foreach($many as $tag){
			$tags[] = $tag;
		}
	}
	// split the container
	@$chunk = array_chunk($container,$itemPerIndex,TRUE);
	@$chunk_draft = array_chunk($draft_container,$itemPerIndex,TRUE);
	## CREATE SITEMAP.XML
	foreach($container as $metadata){
		$xmlItems[] = sprintf(trim($sitemapItem,"\t\n\r\0\x0B"),SITE_URL,$metadata[2]);
	}
	$xml = sprintf(trim($sitemapContainer,"\t\n\r\0\x0B"),implode('',$xmlItems));
	## CREATE RSS.XML
	// limit to (2 x $itemPerIndex)
	for($i = 0;$i<= 1;$i++){
		foreach($chunk[$i] as $postid => $metadata){
			$readPost = self::loadPost($postid);
			$rssItems[] = sprintf(trim($rssItem,"\t\n\r\0\x0B"),$readPost['title'],
			SITE_URL . '/'. $readPost['permalink'],
			htmlentities($readPost['entry']));
		}
	}
	$rss = sprintf(trim($rssContainer,"\t\n\r\0\x0B"),SITE_TITLE,SITE_URL,
	SITE_DESC,implode('',$rssItems));
	// count post each tag
	$counttag = array_count_values($tags);
	$reverse_year = array_reverse($archiveDate,true);
	$indexJson   = self::toJson($chunk);
	$draftJson   = self::toJson($chunk_draft);
	$tagJson     = self::toJson($counttag);
	$archiveJson = self::toJson($reverse_year);
	## SPLIT INDEX TO FILE
	foreach($chunk as $keyy => $chunkval){
		$chunkpart = self::toJson($chunkval);
		self::toFile("sp_static/index/index-{$keyy}.json",$chunkpart,true);
	}
	## WRITE NEW DATA
	self::toFile("sp_static/index/totalpost.json",$countAllPost,true);
	self::toFile("sp_static/index/archive.json",$archiveJson,true);
	self::toFile("sp_static/index/draft.json",$draftJson,true);
	self::toFile("sp_static/index/post.json",$indexJson,true);
	self::toFile("sp_static/index/tag.json",$tagJson,true);
	self::toFile("sp_static/index/filesize.json",$countFileSize,true);
	self::toFile("sp_static/xml/rss.xml",$rss,true);
	self::toFile("sp_static/xml/sitemap.xml",$xml,true);
}
// load json file and decode it into associative array
function loadJson($type = NULL,$name = NULL){
	$str = self::loadFile("sp_static/{$type}/{$name}.json",true);
	$jsonDecoded = json_decode($str,true);
	return $jsonDecoded;
}
// add ip address to blacklist
function ipBlackList($specify = NULL){
	if(is_null($specify)){
		$ipBlackList = $_SERVER["REMOTE_ADDR"];
	}else{
		$ipBlackList = $specify;
	}
	$blackList = self::loadJson('misc','blacklist');
	$blackList[self::endKey($blackList,true)] = $ipBlackList;
	self::toFile('sp_static/misc/blacklist.json',self::toJson($blackList),true);
}
function isBlacklisted($specify = NULL){
	$blackList = self::loadJson('misc','blacklist');
	if(is_null($specify)){
		$ip = $_SERVER["REMOTE_ADDR"];
	}else{
		$ip = $specify;
	}
	if(@in_array($ip,$blackList)){
		return true;
	}else{
		return false;
	}
}
function totalPost(){
	$size = self::loadFile('sp_static/index/totalpost.json');
	return $size;
}
// create recent comment
function create_recent_comment($permalink,$post_title,$comments){
	// read/write
	$filename = "sp_static/misc/recent_comment.json";
	$fgets = self::loadFile($filename,true);
	$decode = json_decode($fgets,true);
	$endkey = self::endKey($decode,true);
	if($endkey > 5){
		array_shift($decode);
		$newKey = 5;
	}else{
		$newKey = $endkey;
	}
	$decode[$newKey] = array($permalink,$post_title,$comments);
	$encode = self::toJson($decode);
	self::toFile($filename,$encode,true);
}
function search($mode,$query){
	$index = self::loadJson('index','post');
	$s = explode('+',strtolower(rtrim($query,'+')));
	$count = count($s);
	if($count <= 1){$amount = 1;}
	else{$amount = $count;}
	for($i = 0;$i <= count($index);$i++){
		foreach($index[$i] as $key => $val){
			if($mode == 0){
				$tgl = date('Y m d',strtotime($val[$mode]));
				$y = explode(' ',$tgl);
				if($amount == 1){
					if($y[0] == $s[0]){
						$found[$key] = '';
					}
				}
				if($amount == 2){
					if($y[0] == $s[0] && $y[1] == $s[1]){
						$found[$key] = '';
					}
				}
				if($amount >= 3){
					if($y[0] == $s[0] && $y[1] == $s[1] && $y[2] == $s[2]){
						$found[$key] = '';
					}
				}
			}
			if($mode == 1){
				$p = explode(' ',strtolower($val[$mode]));
				for($o = 0;$o < $amount;$o++){	
					if(in_array($s[0],$p)){$found[$key] = '';}
				}
				unset($p);
			}
			if($mode == 2){
				$p = strtolower($val[$mode]);
				if($s[0] == $p){
					$found[$key] = '';
					break 2;
				}
			}
			if($mode == 3){
				$val[$mode] = array_map('strtolower',$val[$mode]);
				for($o = 0;$o < $amount;$o++){	
					if(in_array(strtolower(urldecode($query)),$val[$mode])){$found[$key] = '';}
				}
			}
			unset($val);
		}
	}
	return $found;
}
function index_page($post_array,$read_meta = false){
	foreach($post_array as $postid => $metadata){
		$dec = self::loadPost($postid);
		if($read_meta){
			$postmeta = array(
			'date' 		=> $dec['date'],
			'title' 	=> $dec['title'],
			'permalink' => $dec['permalink'],
			'tag' 		=> explode(',',$dec['tag'])
			);
		}else{
			$postmeta = self::splitMeta($metadata);
		}
		$thumbImg = self::getImg($dec['entry']);
		$konten = $dec['entry'];
		/* grouping */
		$group[$postid]["id"]    = $postid;
		$group[$postid]["image"] = $thumbImg;
		$group[$postid]["tag"]   = $postmeta['tag'];
		$group[$postid]["title"] = $postmeta['title'];
		$group[$postid]["url"]   = self::_url($postmeta['permalink']);
		$group[$postid]["date"]  = $postmeta['date'];
		$group[$postid]["entry"] = $konten;
	}
	return $group;
}
} # end sedot class

include 'sp_theme/'.THEME.'/widget.php';

### START LISTENING ###
$post = new sedot;

if(!isset($_FRONT_PAGE)){$_FRONT_PAGE = false;}
if(!isset($_SINGLE_POST)){$_SINGLE_POST = false;}
if(!isset($_PAGE_404)){$_PAGE_404 = false;}

if(empty($getRequest[0])){
	$_FRONT_PAGE = true;
	$pointer = $post->loadJson('index','index-0');
	$entries = $post->index_page($pointer,false);
	$post->navi($post->totalpost(),1);
}
elseif($getRequest[0] == "page"){
	$_FRONT_PAGE = true;
	$pagenum = $getRequest[1];
	$pointer = $post->loadJson('index','index-'.($pagenum - 1));
	$entries = $post->index_page($pointer,false);
	$post->navi($post->totalpost(),$pagenum);
}
elseif($getRequest[0] == "rss"){
	header("Content-Type: application/rss+xml; charset=ISO-8859-1");
	printf("%s",$post->loadFile("sp_static/xml/rss.xml"));
	exit;
}
elseif($getRequest[0] == "search" || $getRequest[0] == "tag" || $getRequest[0] == "timeline"){
	$_FRONT_PAGE = true;
	$_SEARCH_MODE = true;
	if($getRequest[0] == 'search'){$mode = 1;}
	elseif($getRequest[0] == 'tag'){$mode = 3;}
	elseif($getRequest[0] == 'timeline'){
		$mode = 0;
		$i = 1;
		while($getRequest[$i] != "page"){
			$timeline[] = $getRequest[$i];
			if($i > 6){break;}
			$i++;
		}
		$getRequest[1] = implode('+',$timeline);
		$getRequest[1] = rtrim($getRequest[1],'+');
		
		for($a = 2;$a < $i;$a++){
			unset($getRequest[$a]);
		}
		$getRequest = array_values($getRequest);
	}
	$find 	 = array_chunk($post->search($mode,$getRequest[1]),5,true);
	$pagenum = $getRequest[3];
	if($pagenum == ''){$pagenum = 1;}
	$pointer = $find[$pagenum - 1];
	$entries = $post->index_page($pointer,true);
	$post->navi(count($find),$pagenum);
}
elseif($getRequest[0] == "backstage"){
	$authUser = @$_SERVER['PHP_AUTH_USER'];
	$authPass = @$_SERVER['PHP_AUTH_PW'];
	if($post->isBlacklisted()){
		die("IP Address Blocked");
	}
	if($authUser == ADMIN_NICKNM && $authPass == ADMIN_PASSWD){
		if(isset($getRequest[1]) && $getRequest[1] == "logout"){
			$isValid = false;
		}else{
			$isValid = true;
		}
	}else{
		$_SESSION["ATTEMPT"] += 1;
		$isValid = false;
	}
	if(!$isValid){
		if($_SESSION["ATTEMPT"] >= 10){
			$post->ipBlackList();
			die("Max Attempt");
		}else{
			header('WWW-Authenticate: Basic realm="sedotpress"');
			header('HTTP/1.0 401 Unauthorized');
			die("Not authorized");
		}
	}
	die(include 'admin.php');
}
else{
	$_SINGLE_POST = true;
	$pointer = $post->search(2,$getRequest[0]);
	$pointer = key($pointer);
	if(! $pointer || $pointer == '' || is_null($pointer)){
		header('HTTP/1.0 404 Not Found');
		$_SINGLE_POST = false;
		$_PAGE_404 = true;
		goto bypass;
	}
	$entry  = $post->formatPost($pointer);
	bypass:
}
require 'sp_theme/'.THEME.'/template.php';
?>
