<?php
/*

The MIT License (MIT)

Copyright (c) [2015] [sukualam]

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

 */

#[ SEDOTPRESS VERSION: 0.1.5 ]#
#[ visit: github.com/sukualam/sedotpress ]#


/* ------------------------*/
/* EDIT THIS CONFIGURATION */
/* ------------------------*/

### START CONFIG ###

// language
// possible: "en" or "id" or create own language! (see sp_lang)
define("SITE_LANG","en");

// admin username, default is "admin"
define("ADMIN_NICKNM","admin");

// admin password, default is "root"
define("ADMIN_PASSWD","root");

// your rocks blog title
define("SITE_TITLE","Sedotpress");

// your rocks blog description
define("SITE_DESC","my sedotpress blog");

// valid admin email (for gravatar)
define("GRAV_EMAIL","example@example.com");

// your blog url (sub-directory is supported)
//(without "/" at end)
define("SITE_URL","http://localhost");

// comment to debugging
error_reporting(0);

### END CONFIG ###


/*
* THE MAGIC IS BELOW HERE
* just leave it alone if you feel comfort
* feel some bugs? go hack this
* THANK YOU
*/

# current version
define("SEDOT_VER","v0.1.5");

# start session ....
session_start();

# sedotpress url routing
// remove / on SITE_URL (if you add it on end)
$find_base = explode("/",rtrim(SITE_URL,"/"),4);
if(! isset($find_base[3]) || $find_base[3] == ""){
	$requested = $_SERVER["REQUEST_URI"];
}
else{
	$requested = str_replace("/{$find_base[3]}","",$_SERVER["REQUEST_URI"]);
}

$get_request = explode("/",$requested);
array_shift($get_request);

# this is just for WYSIWYG editor (summernote)
$extracss = "
<link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.3.0/css/font-awesome.min.css\">
<link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/summernote/0.6.2/summernote.min.css\">
";
$extrajs = "
<script src=\"https://cdnjs.cloudflare.com/ajax/libs/summernote/0.6.2/summernote.min.js\"></script>
<script>$(document).ready(function(){\$('#konten').summernote();});</script>";

## toggle admin session
if(isset($_SESSION["LOGIN"]) && $_SESSION["LOGIN"] == $_SESSION["KEY"]){
	$is_admin = true;
}
else{
	$is_admin = false;
}

# -------------------------------------
# THIS IS SEDOT CLASS
# The most essential part of sedotpress
# -------------------------------------
class sedot{

function __construct(){
	/* create directory */
	if(! is_dir("sp_post")){
		mkdir("sp_post");
	}
	if(! is_dir("sp_comment")){
		mkdir("sp_comment");
	}
	if(! is_dir("sp_feed")){
		mkdir("sp_feed");
	}
	if(! is_dir("sp_json")){
		mkdir("sp_json");
	}
	if(! is_dir("sp_lang")){
		mkdir("sp_lang");
	}
	/* global language */
	include_once("sp_lang/".SITE_LANG.".php");
	$this->lang = $bh;
}
/*
function: create_index()
description:
[this is the most essential function in sedotpress]
it create static cache (index.json, archive.json, tags.json) in json/
and create a sitemap.xml and rss.xml in feed/
*/
function create_index(){
	
	## --------------------
	## SCAN THE /sp_post
	## --------------------
	
	// first, we scan all files on "sp_post/" first
	$dir = "sp_post/";
	$scandir = array_diff(scandir($dir), array('..', '.'));
	// we sort the order
	natsort($scandir); # life saver for caching..
	// then reverse the order
	$reversed = array_reverse($scandir);
	// then, read a metadata one by one and save to container
	foreach($reversed as $filename){
		$handle = fopen($dir.$filename,"r");
		$read = fread($handle,filesize($dir.$filename));
		// decode json in every post
		$dec = json_decode($read,true);
		$metadata = $dec['date']."|".$dec['title']."|".$dec['url']."|".$dec['tag'];
		if($dec["status"] == "publish"){
			
			$container[$filename] = $metadata;
		}
		else{
			$draft_container[$filename] = $metadata;
		}

		unset($dec);
		fclose($handle);
	}
	
	## ------------------
	## CREATE SITEMAP.XML
	## ------------------
	
	// delete old sitemap.xml
	if(file_exists("sp_feed/sitemap.xml")){
		unlink("sp_feed/sitemap.xml");
	}
	// create a new sitemap.xml
	if(true){
		$create_sitemap = fopen("sp_feed/sitemap.xml","a");
		$xml_sitemap = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
		<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">";
		fwrite($create_sitemap,$xml_sitemap);
		foreach($container as $postid => $metadata){
			$meta_explode = explode("|",$metadata);
			$write_node = "
			<url>
				<loc>".SITE_URL."/{$meta_explode[2]}</loc>
			</url>";
			fwrite($create_sitemap,$write_node);
		}
		$xml_sitemap = "
		</urlset>
		";
		// ok
		fwrite($create_sitemap,$xml_sitemap);
		fclose($create_sitemap);
	}
	// split the container
	$chunk = array_chunk($container,5,TRUE);
	$chunk_draft = array_chunk($draft_container,5,TRUE);
	
	## --------------
	## CREATE RSS.XML
	## --------------
	
	// delete old rss.xml
	if(file_exists("sp_feed/rss.xml")){
		unlink("sp_feed/rss.xml");
	}
	// create a new rss.xml
	if(true){
		// limit to newest 10 post
		for($i = 0;$i<= 1;$i++){
			foreach($chunk[$i] as $postid => $metadata){
			$explode = explode("|",$metadata);
			$title = $explode[1];
			$url = SITE_URL."/{$explode[2]}";
			$open_file = fopen("sp_post/{$postid}","r");
			$read_file = fread($open_file,filesize("sp_post/{$postid}"));
			$dec = json_decode($read_file,true);
			$read_file = htmlentities($dec["konten"]);
			fclose($open_file);
			$temp_data[] = "
			<item>
				<title>{$title}</title>
				<link>{$url}</link>
				<description>{$read_file}</description>
			</item>";
			}
		}
		// write a new data to rss.xml
		$create_rss = fopen("sp_feed/rss.xml","a+");
		$rss_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
		<rss version=\"2.0\">
			<channel>
				<title>".SITE_TITLE."</title>
				<link>".SITE_URL."</link>
				<description>".SITE_DESC."</description>";
		fwrite($create_rss,$rss_xml);
		fwrite($create_rss,implode("\n",$temp_data));
		$rss_xml = "
			</channel>\n</rss>";
		fwrite($create_rss,$rss_xml);
		fclose($create_rss);
	}
	// now, the splitted container encoded to json
	$json = json_encode($chunk,JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
	// meanwhile, we extract the metadata again for tags and archive
	
	## -----------------
	## CREATE TAGS INDEX
	## -----------------
	
	for($i=0;$i<=count($chunk);$i++){
		foreach($chunk[$i] as $key => $val){
			$expl = explode("|",$val);
			$date = $expl[0];
			$tagpl[] = explode(",",$expl[3]);
			$datx = explode(" ",$date);
			// counting purpose
			$hier[$datx[2]][$datx[1]][$datx[0]] .= $key.",";
			$exploud = explode(",",$hier[$datx[2]][$datx[1]][$datx[0]]);
			$hierx[$datx[2]][$datx[1]][$datx[0]] = count($exploud) - 1;
		}
	}
	// grouping the tags
	foreach($tagpl as $val){
		// recursive
		foreach($val as $vall){
			// final tags
			$tags[] = $vall;
		}
	}
	// count post each tag
	$counttag = array_count_values($tags);
	// delete old tags.json
	if(file_exists("sp_json/tags.json")){
		unlink("sp_json/tags.json");
	}
	// create a new tags.json
	$tag_index = fopen("sp_json/tags.json","a+");
	$tag_encode_json = json_encode($counttag,JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
	$tgwrite = fwrite($tag_index,$tag_encode_json);
	fclose($tag_index);
	
	## --------------------
	## CREATE ARCHIVE INDEX
	## --------------------
	
	// delete old archive.json
	if(file_exists("sp_json/archive.json")){
		unlink("sp_json/archive.json");
	}
	// create a new archive.json
	// reverse..
	$reverse_year = array_reverse($hierx,true);
	$year_index = fopen("sp_json/archive.json","a+");
	$encode_json = json_encode($reverse_year,JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
	$write = fwrite($year_index,$encode_json);
	fclose($year_index);
	
	## ------------------
	## CREATE POSTS INDEX
	## ------------------
	
	// delete old index.json
	if(file_exists("sp_json/index.json")){
		unlink("sp_json/index.json");
	}
	// create a new index.json
	$json_filename = "sp_json/index.json";
	$save = fopen($json_filename,"a+");
	// ok
	fwrite($save,$json);
	fclose($save);
	
	## ------------------
	## CREATE DRAFT INDEX
	## ------------------
	$json_draft = json_encode($chunk_draft,JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
	// delete old index.json
	if(file_exists("sp_json/draft.json")){
		unlink("sp_json/draft.json");
	}
	// create a new index.json
	$json_filename_draft = "sp_json/draft.json";
	$save_draft = fopen($json_filename_draft,"a+");
	// ok
	fwrite($save_draft,$json_draft);
	fclose($save_draft);
}

// load json file and decode it into associative array
function load_json($param,$post){
	if($param == 'COMMENT'){
		$read = fopen("sp_comment/{$post}.json","r");
		$str = fgets($read);
	}
	else{
		$read = fopen("sp_json/{$param}","r");
		$str = fgets($read);
	}
	fclose($read);
	// decode json -> assoc. array
	$x = json_decode($str,true);
	return $x;
}
// tag widget
function tag_cloud(){
	$x = self::load_json('tags.json');
	$lst .= "<h3>{$this->lang['TAG_CLOUD']}</h3>";
	$lst .= "<ul>";
	foreach($x as $key => $val){
		$lst .= sprintf("<li>
		<a title=\"{$this->lang['TAG_COUNT']}\" ",$key,$val)."href=\"".SITE_URL."/tag/{$key}\">{$key}</a> <span title=\"{$this->lang['POST_COUNT']}\">({$val})</span></li>
		";
	}
	$lst .= "
	</ul>
	<h3>{$this->lang['WIDGET_LINK_TITLE']}</h3>
	<ul>
	<li><a title=\"".SITE_TITLE." RSS\" href=\"".SITE_URL."/rss\">{$this->lang['RSS_FEED']}</a></li>
	</ul>";
	return $lst;
}
// blog archive widget
function arsip(){
	$x = self::load_json('archive.json');
	$lst .= "
	<h3>{$this->lang['BLOG_ARCHIVE']}</h3><ul>";
	foreach($x as $year => $month){
		$lst .= sprintf("<li><a title=\"{$this->lang['POST_ARCHIVES_YEAR']}\"",$year)." href=\"".SITE_URL."/timeline/{$year}\">{$year}</a><ul>";
		foreach($month as $key => $post){
			foreach($post as $val){
				$count += $val;
			}
			$lst .= "<li>";
			$lst .= sprintf("<a title=\"{$this->lang['POST_ARCHIVES_MONTH']}\" ",$key,$year);
			$lst .= "href=\"".SITE_URL."/timeline/{$year}/";
			$lst .= strtolower($key)."\">{$key}</a> ";
			$lst .= "<span>({$count})</span></li>";
			$count = 0;
		}
		$lst .= "</ul></li>
		";
	}
	$lst .= "</ul>";
	return $lst;
}
// format the page
function index_page($post_array,$read_meta,$is_admin){

	foreach($post_array as $postid => $metadata){	
		$open_file = fopen("sp_post/".$postid,"r");
		$read_file = fgets($open_file);
		fclose($open_file);
		#$read_file = fread($open_file,filesize("sp_post/".$postid));
		$dec = json_decode($read_file,true);
		if($read_meta == true){
			$postmeta = $dec['date']."|".$dec['title']."|".$dec['url']."|".$dec['tag'];
		}
		else{
			$postmeta = $metadata;
		}
		if($is_admin == true){
			$editurl = "<a class=\"label label-warning\" href=\"".SITE_URL."/backstage/edit/?id={$postid}\">EDIT</a>";
		}
		else{
			$editurl = "";
		}
		$split_meta = explode("|",$postmeta);
		$meta_date = $split_meta[0];
		$meta_title = $split_meta[1];
		$meta_url = $split_meta[2];
		$meta_tags = explode(",",$split_meta[3]);
		foreach($meta_tags as $val){
			$y .= "<a title=\"{$this->lang['RESULT_POST_TAGGED']} {$val}\" class=\"label label-primary\" href=\"".SITE_URL."/tag/".strtolower($val)."\">{$val}</a> ";
		}
		$tag = $y;
		unset($y);
		$pattern = '/src="([^"]*)"/';
		preg_match($pattern, $dec["konten"], $matches);
		$src = $matches[1];
		$konten = self::strip_html_tags($dec["konten"]);
		$konten = substr($konten,0,270);
		$cutlastword = strrpos($konten, ' ');
		$konten = substr($konten, 0, $cutlastword);
		if($src == ""){
			$konten = substr($konten, 0, $cutlastword);
			$konten = "
			<div class=\"row\">
			   <p>{$konten} ...
			  <span><a title=\"{$this->lang['READ_ARTICLE']}\" href=\"".SITE_URL."/{$meta_url}\">{$this->lang['READMORE']}</a></span>
			  </p>
			</div>";
		}
		else{
			$konten = substr($konten, 0, $cutlastword);
			$konten = "
			<div class=\"row\">
			  <div class=\"col-xs-6 col-md-3\">
				<a href=\"#\" class=\"thumbnail\">
				  <img title=\"{$meta_title}\" alt=\"{$meta_title}\" src=\"{$src}\"/>
				</a>
			  </div>
			  <p>{$konten} ...
			  <span><a title=\"{$this->lang['READ_ARTICLE']}\" href=\"".SITE_URL."/{$meta_url}\">{$this->lang['READMORE']}</a></span>
			  </p>
			</div>
			";	
		}
		$group .= "
		  <div class=\"col-md-12\">
			<div style=\"margin-bottom:25px\" class=\"row\">
			<h2><a title=\"{$meta_title}\" rel=\"bookmark\" href=\"".SITE_URL."/{$meta_url}\">{$meta_title}</a></h2>
			<time title=\"{$this->lang['DATE_POSTED']}\" class=\"label label-success\" datetime=\"".date('d-m-Y', strtotime($meta_date))."\">{$meta_date}</time>
			<span>{$tag} {$editurl}</span>
			</div>
			{$konten}
		  </div>
		  ";
	}
	return $group;
}

// required for single post query or to find filename by permalink
// it seems radical :)
function get_filename($array,$permalink){
	foreach($array as $indexnum => $files){
		foreach($array[$indexnum] as $filename => $filemeta){
			$expl = explode("|",$filemeta);
			if($expl[2] == $permalink){
				$target_filename = $filename;
				$found = 1;
				return $target_filename;
				break 2;
			}
			else{
				$found = 0;
			}
		}
	}
	if($found == 0){
		return false;
	}
}

// the basic page navigation with 3 parameter
// param 1: usually total of result or posts (it will divided later)
// param 2: because value in param 1 is divided, param 2 is pointer
// param 3: (optional) custom url for page link
// param 4: (optional) toggle nofollow
function page_navi($count,$page,$prefix_url,$nofollow){
	if(strlen($prefix_url) <= 1){
		$prefix = SITE_URL."/page/";
	}
	else{
		$prefix = SITE_URL.$prefix_url;
	}
	if(isset($nofollow) && $nofollow != ""){
		$nf = "rel=\"nofollow\" ";
	}
	else{
		$nf = "";
	}
	$row = 5;
	$current = $page;
	$total_item = $count;
	for($i = 0;$i < $total_item;$i++){
		$raw[$i] = $i + 1;
	}
	$stack = array_chunk($raw,$row);
	$stack_count = count($stack);
	$out .= "<nav>";
	$out .= "<ul class=\"pagination\">";
	for($i = 0;$i <= $stack_count;$i++){     
		if(in_array($current,$stack[$i])){  
			if($i > 0){
				$out .= "<li>";
				$out .= "<a {$nf}title=\"{$this->lang['PAGE_NAVI_PREV']}\" ";
				$out .= "class=\"page-numbers\" ";
				$out .= "href=\"{$prefix}".($current - 1)."\">&laquo;</a>";
				$out .= "</li>";
			}
			foreach($stack[$i] as $raw){
			if($raw == $current){
				$out .= "<li class=\"active\">";
				$out .= "<span title=\"{$this->lang['PAGE_NAVI_CURRENT']}\" ";
				$out .= "class=\"page-numbers current\">{$raw}</span>";
				$out .= "</li>";
			}else{
				$out .= "<li>";
				$out .= "<a {$nf}title=\"{$this->lang['PAGE_NAVI_GOTO']}{$raw}\" ";
				$out .= "class=\"page-numbers\" ";
				$out .= "href=\"{$prefix}".$raw."\">{$raw}</a>";
				$out .= "</li>";
			}
			}
			if($current < $total_item){
				$out .= "<li>";
				$out .= "<a {$nf}title=\"{$this->lang['PAGE_NAVI_NEXT']}\" ";
				$out .= "class=\"page-numbers\" ";
				$out .= "href=\"{$prefix}".($current + 1)."\">&raquo;</a>";
				$out .= "</li>";
			}
			break 1;
		}
	}
	$out .= "</ul>";
	$out .= "</nav>";
	return $out;
}

/* used in single post */
function format_post($filename){
	$handle = fopen("sp_post/".$filename,"r");
	$read_meta = fread($handle,filesize("sp_post/".$filename));
	$dec = json_decode($read_meta,true);
	$metadata = $dec['date']."|".$dec['title']."|".$dec['url']."|".$dec['tag'];
	$split_meta = explode("|",$metadata);
	$meta_tags = explode(",",$split_meta[3]);
	foreach($meta_tags as $val){
		$tag .= "<a title=\"{$this->lang['RESULT_POST_TAGGED']}{$val}\" ";
		$tag .= "class=\"label label-primary\" ";
		$tag .= "href=\"".SITE_URL."/tag/".strtolower($val)."\">{$val}</a> ";
	}
	$konten = $dec["konten"];
	// insert to property
	$this->post->title = $split_meta[1];
	$this->post->permalink = $split_meta[2];
	$this->post->datetime = $split_meta[0];
	$this->post->tag = $tag;
	$this->post->content = $konten;
}

function _header($a,$b,$c,$d){
echo "
<!DOCTYPE html>
<html lang=\"id\">
<head>
<meta charset=\"utf-8\">
<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">
<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
<meta name=\"generator\" content=\"sedotpress ".SEDOT_VER."\">
{$b}
<title>{$a}</title>
<link href=\"".SITE_URL."/rss\" rel=\"alternate\" type=\"application/rss+xml\"/>
<link rel=\"stylesheet\" href=\"https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css\">
<link rel=\"icon\" type=\"image/x-icon\" href=\"".SITE_URL."/favicon.ico\">
<!--[if lt IE 9]>
<script src=\"https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js\"></script>
<script src=\"https://oss.maxcdn.com/respond/1.4.2/respond.min.js\"></script>
<![endif]-->
{$c}
</head>
<body>
<!-- Fixed navbar -->
<nav class=\"navbar navbar-default\">
<div class=\"container\">
<div class=\"navbar-header\">
<button type=\"button\" class=\"navbar-toggle collapsed\" data-toggle=\"collapse\" data-target=\"#navbar\" aria-expanded=\"false\" aria-controls=\"navbar\">
<span class=\"sr-only\">Toggle navigation</span>
<span class=\"icon-bar\"></span>
<span class=\"icon-bar\"></span>
<span class=\"icon-bar\"></span>
</button>
<!-- <a class=\"navbar-brand\" href=\"#\">Project name</a> -->
</div>
<div id=\"navbar\" class=\"navbar-collapse collapse\">
<ul class=\"nav navbar-nav\">";
// load menu json..
$menus = self::load_json('menu.json');
if(! file_exists('sp_json/menu.json')){
	$dum = fopen('sp_json/menu.json','a+');
	$dum_text = '{"1":["Sample Menu",""],"2":["Sample Dropdown",[["Item 1",""],["Item 2",""],["Bro! you can edit this!",""]]]}';
	fwrite($dum,$dum_text);
	fclose($dum);
}
foreach($menus as $k1 => $item){
	if(is_array($item[1])){
		$mnu .= "<li class=\"dropdown\">
		<a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\" role=\"button\" aria-expanded=\"false\">{$item[0]} <span class=\"caret\"></span></a>
		<ul class=\"dropdown-menu\" role=\"menu\">";
		foreach($item[1] as $k2 => $sub){
			$mnu .= "<li><a href=\"{$sub[1]}\">{$sub[0]}</a></li>";
		}
		$mnu .= "</ul></li>";
	}
	else{
		$mnu .= "<li><a href=\"{$item[1]}\">{$item[0]}</a></li>";
	}
}
echo $mnu;
echo "
</ul>
<form method=\"post\" action=\"". SITE_URL ."/search/\" class=\"navbar-form navbar-right\" role=\"search\">
	<div class=\"form-group\">
		<input type=\"text\" name=\"q\" class=\"form-control\" placeholder=\"Search\">
	</div>
</form>
</div>
</div>
</nav>
<div style=\"margin-bottom:10px;\" class=\"container\">{$d}";

}
function _body($a,$b,$c,$d){
	echo $a;
	echo $b;
	echo $c;
	echo $d;
}
function _foot($a,$b,$c,$d){
	echo "<script src=\"https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js\"></script>
<script src=\"https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js\"></script>
{$c}
<div>{$b}</div>
<footer style=\"padding:10px\" class=\"footer\">{$a}
<br>
<p style=\"text-align:center\">
&copy; 2015 ".SITE_TITLE." | {$this->lang['POWERED_BY']} <a href=\"https://github.com/sukualam/sedotpress\">Sedotpress</a></p>
</footer>
</div>
</body>
</html>";
}
function strip_html_tags($text){
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
} // endclass


/* Now, it time to process the requested url
 * It will listen $_SERVER["REQUEST_URI"] and parse it
 * It will render the page depending requested url
 */

// deprecated
$copyright = "";
$search_form = "";


if(count($get_request) == 1 || $get_request[0] == "backstage" || isset($_GET["com"])){
	if($get_request[0] != ""){
		if($get_request[0] == "rss"){
			## -----------
			## THIS IS RSS
			## -----------
			header("Content-Type: application/rss+xml; charset=ISO-8859-1");
			$open_rss = fopen("sp_feed/rss.xml","r");
			$read_rss = fread($open_rss,filesize("sp_feed/rss.xml"));
			fclose($open_rss);
			echo $read_rss;
		}
		elseif($get_request[0] == "backstage"){
			## -----------------
			## THIS IS BACKSTAGE
			## -----------------
			$post = new sedot;
			$auth = $_SESSION["AUTHENTIC"];
			if($auth == "" || $_SESSION["TIMES"] == 5){
				$_SESSION["AUTHENTIC"] = "nobody";
				$_SESSION["KEY"] = substr(crc32(md5(microtime(true))),1,7);
				$_SESSION["TIMES"] = 0;
				header("Location: ".SITE_URL."/backstage");
			}
			if($auth == "nobody" && strlen($_SESSION["LOGIN"]) <= 1){
				$_SESSION["TIMES"] += 1;
				$msg_1 = "<!DOCTYPE html>
				<html>
				<head>
					<title>[Backstage] - ".SITE_TITLE."</title>
					<style>body{background:#eee;text-align:center}</style>
				</head>
				<body>
					Enter this code <b>{$_SESSION["KEY"]}</b>
					<form action=\"".SITE_URL."/backstage\" method=\"post\">
					<input type=\"text\" name=\"key\"><input type=\"submit\">
					</form>
					<p>Back to <a href=\"".SITE_URL."\">".SITE_TITLE."</a></p>
					<br>
					<hr>
					{$copyright}
				</body>
				</html>";
				if($_POST["key"] == $_SESSION["KEY"]){
					$_SESSION["TIMES"] = 0;
					$_SESSION["LOGIN"] = "almost";
					$msg_1 = "Its correct!, you can continue...<br>
					<a href=\"".SITE_URL."/backstage\" class=\"btn btn-primary\">Continue</a>
					or back to <a href=\"".SITE_URL."\">".SITE_TITLE."</a>
					<br>
					<hr>
					{$copyright}
					";
				}
				echo $msg_1;
				break;
			}
			if($_SESSION["LOGIN"] == "almost"){
				$_SESSION["TIMES"]++;
				$msg_2 = "<h1>".SITE_TITLE." &middot;
				<small>Backstage Area</small></h1>";
				$msg_3 = "
				{$msg_2}
				<div style=\"margin-top:20px\" class=\"row\">
				<div class=\"col-md-4\">
				</div>
				<div class=\"col-md-4\">
				<form action=\"".SITE_URL."/backstage\" method=\"post\">
				<div class=\"form-group\">
				<label>Username</label>
				<input name=\"u\" class=\"form-control\" type=\"text\"/>
				</div>
				<div class=\"form-group\">
				<label>Password</label>
				<input name=\"p\" class=\"form-control\" type=\"password\"/>
				</div>
				<div class=\"form-group\">
				<input class=\"form-control btn btn-success\" type=\"submit\"/>
				</div>
				</form>
				Back to <a href=\"".SITE_URL."\">".SITE_TITLE."</a>
				</div>
				<div class=\"col-md-4\">
				</div>
				</div>";
				if($_SESSION["TIMES"] == 5){
					session_destroy();
					header("Location: ".SITE_URL."/backstage");
				}
				if($_POST["u"] == ADMIN_NICKNM && $_POST["p"] == ADMIN_PASSWD){
					// this
					$_SESSION["LOGIN"] = $_SESSION["KEY"];
					header("Location: ".SITE_URL."/backstage");
				}
				$render_head = $post->_header("Login into backstage..");
				$render_body = $post->_body($msg_3);
				$render_foot = $post->_foot($copyright);
				break;
			}
			$request = $get_request[1];
			$menu = "
				<div class=\"well\">
				<b>Menu</b> : [<a href=\"".SITE_URL."/backstage/\">Backstage</a>]
				[<a href=\"".SITE_URL."/backstage/create\">{$post->lang['BS_CREATE']}</a>]
				[<a href=\"".SITE_URL."/backstage/manage\">{$post->lang['BS_MANAGE']}</a>]
				[<a href=\"".SITE_URL."/backstage/draft\">{$post->lang['BS_DRAFT']}</a>]
				[<a href=\"".SITE_URL."/backstage/menu\">{$post->lang['BS_EDIT_MENU']}</a>]
				[<a target=\"_blank\" href=\"".SITE_URL."\"><b>{$post->lang['BS_VISIT_BLOG']}</b></a>]
				</div>";
			if($request == ""){
				$h = "
				<div class=\"header\">
				<h1>Welcome, ".ADMIN_NICKNM."!</h1>
				</div><div class=\"row\">
				<div class=\"col-md-4\">
				<h2><a href=\"".SITE_URL."\">{$post->lang['BS_VISIT_BLOG']}</a></h2>
				<p>".SITE_TITLE." - ".SITE_DESC."</p>
				</div>
				<div class=\"col-md-4\">
				<h2><a href=\"".SITE_URL."/backstage/create\">{$post->lang['BS_CREATE']}</a></h2>
				<p>{$post->lang['BS_CREATE_LABEL']}</p>
				</div>
				<div class=\"col-md-4\">
				<h2><a href=\"".SITE_URL."/backstage/manage\">{$post->lang['BS_MANAGE']}</a></h2>
				<p>{$post->lang['BS_MANAGE_LABEL']}</p>
				</div>
				<div class=\"col-md-4\">
				<h2><a href=\"".SITE_URL."/backstage/draft\">{$post->lang['BS_DRAFT']}</a></h2>
				<p>{$post->lang['BS_DRAFT_LABEL']}</p>
				</div>
				<div class=\"col-md-4\">
				<h2><a href=\"".SITE_URL."/backstage/menu\">{$post->lang['BS_EDIT_MENU']}</a></h2>
				<p>{$post->lang['BS_EDIT_MENU_LABEL']}</p>
				</div>
				<div class=\"col-md-4\">
				<h2><a href=\"".SITE_URL."/backstage/build\">{$post->lang['BS_REBUILD_INDEX']}</a></h2>
				<p>{$post->lang['BS_REBUILD_INDEX_LABEL']}</p>
				</div>
				<div class=\"col-md-4\">
				<h2><a href=\"".SITE_URL."/backstage/logout\">{$post->lang['BS_LOGOUT']}</a></h2>
				<p>{$post->lang['BS_LOGOUT_LABEL']}</p>
				</div>
				</div>
				";
				$render_head = $post->_header("[Backstage] - ".SITE_TITLE);
				$render_body = $post->_body($h);
				$render_foot = $post->_foot($copyright);
			}
			elseif($request == "build"){
				## -------------
				## REBUILD INDEX
				## -------------
				$post = new sedot;
				$build = $post->create_index();
				echo "REBUILD INDEX SUCCESS! - <a href=\"".SITE_URL."/backstage\">BACK</a>";
			}
			elseif($request == "logout"){
				## --------------
				## THIS IS LOGOUT
				## --------------
				session_destroy();
				header("Location: ".SITE_URL);
			}
			elseif($request == "manage" || $request == "draft"){
				## --------------
				## THIS IS MANAGE
				## --------------
				
				if($request == "manage"){
					$index = $post->load_json('index.json');
					$sec_name = $post->lang['BS_PUBLISHED'];
				}
				elseif($request == "draft"){
					$index = $post->load_json('draft.json');
					$sec_name = $post->lang['BS_DRAFT'];
				}
				else{
					exit;
				}
				
				if(isset($_GET["hal"])){
					$x = $_GET["hal"];
				}else{
					$x = 1;
				}
				$list = "
				<div class=\"table-responsive\">
				<table class=\"table\"><tr>
				<th>{$post->lang['DATE']}</th>
				<th>{$post->lang['TITLE']}</th>
				<th>{$post->lang['PERMALINK']}</th>
				<th>{$post->lang['ACTION']}</th>
				</tr>";
				foreach($index[$x - 1] as $key => $meta){
					$exp = explode("|",$meta);
					$list .= "<tr>
					<td>{$exp[0]}</td>
					<td>{$exp[1]}</td>
					<td>{$exp[3]}</td>
					<td><a href=\"".SITE_URL."/backstage/edit/?id={$key}\">Edit</a>
					<a title=\"{$post->lang['DEL_NO_CONFIRM']}\" href=\"".SITE_URL."/backstage/delete/?id={$key}\">Del</a>
					</td>
					</tr>";
				}
				$list .= "</table></div>";
				$lay_00 = "<div class=\"row\">
				<div class=\"col-md-6\"><h1>{$post->lang['MANAGE_POST']} ({$sec_name})</h1></div>
				</div>";
				$lay_01 = "<div class=\"row\">
				<div class=\"col-md-8\">{$list}</div>
				</div>";
				$render_head = $post->_header("[{$sec_name}] Page {$x} - ".SITE_TITLE);
				$render_body = $post->_body($lay_00,$menu,$lay_01);
				$render_foot = $post->_foot($copyright,$post->page_navi(count($index),$x,"/backstage/{$request}/?hal="));
			}
			elseif($request == "menu"){
				## -------------------
				## THIS IS MENU EDITOR
				## -------------------
				$decode = $post->load_json('menu.json');
				if(isset($_GET["del"]) && !isset($_GET["add"]) || $_GET["add"] == ""){
					if(isset($_GET["sub"])){
						unset($decode[$_GET['del']][1][$_GET['sub']]);
					}
					else{
						unset($decode[$_GET['del']]);
					}
					if(file_exists('sp_json/menu.json')){
						if(unlink('sp_json/menu.json')){
							$save_menu = fopen('sp_json/menu.json','a+');
							$encodeback = json_encode($decode,JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
							if(fwrite($save_menu,$encodeback)){
								$msg = "";
							}
							fclose($save_menu);
						}
					}
				}
				elseif(isset($_GET["add"]) && !isset($_GET["del"]) || $_GET["del"] == ""){
					if(isset($_POST["m_title"]) && isset($_POST["m_url"])){
						if(isset($_GET["sub"])){
							if(isset($_GET["dropdown"])){
								$decode[$_GET['add']][0] = $_POST["d_title"];
							}
							$decode[$_GET['add']][1][$_GET['sub']][0] = $_POST["m_title"];
							$decode[$_GET['add']][1][$_GET['sub']][1] = $_POST["m_url"];
						}
						else{
							$decode[$_GET['add']][0] = $_POST["m_title"];
							$decode[$_GET['add']][1] = $_POST["m_url"];
						}
						if(file_exists('sp_json/menu.json')){
							if(unlink('sp_json/menu.json')){
							$save_menu = fopen('sp_json/menu.json','a+');
							$encodeback = json_encode($decode,JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
							if(fwrite($save_menu,$encodeback)){
								$msg = "<div style=\"margin-top:30px;\" class=\"alert alert-success alert-dismissible\" role=\"alert\">";
								$msg .= "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">";
								$msg .= "<span aria-hidden=\"true\">&times;</span></button>";
								$msg .= "{$post->lang['BS_ITEM_UPDATED']}";
								$msg .= "</div>";
							}
							fclose($save_menu);
							}
						}
					}
					else{
						if(isset($_GET["sub"])){
							$q = "add={$_GET['add']}&sub={$_GET['sub']}";
						}
						else{
							$q = "add={$_GET['add']}";
						}
						if(isset($_GET["dropdown"])){
							$q = "add={$_GET['add']}&sub=0&dropdown=yes";
							$form = "<form action=\"?{$q}\" method=\"post\">
							<div class=\"form-group\">
							<fieldset>
							<legend>{$post->lang['BS_NEW_DROPDOWN']}</legend>
							<label>{$post->lang['BS_DROPDOWN_NAME']}</label>
							<input class=\"form-control\" type=\"text\" name=\"d_title\"><br>
							<fieldset>
							<legend><small>{$post->lang['BS_ADD_ITEM']}</small></legend>
							<label>{$post->lang['LABEL_TITLE']}</label>
							<input class=\"form-control\" type=\"text\" name=\"m_title\">
							<label>Url</label>
							<input class=\"form-control\" type=\"text\" name=\"m_url\"><br>
							<input class=\"btn btn-success\" type=\"submit\">
							</fieldset>
							</fieldset>
							</div>
							</form>";
						}
						else{
							$form = "<form action=\"?{$q}\" method=\"post\">
							<div class=\"form-group\">
							<fieldset>
							<legend>{$decode[$_GET['add']][0]} &rarr; {$post->lang['BS_ADD_ITEM']}</legend>
							<label>{$post->lang['LABEL_TITLE']}</label>
							<input class=\"form-control\" type=\"text\" name=\"m_title\">
							<label>Url</label>
							<input class=\"form-control\" type=\"text\" name=\"m_url\"><br>
							<input class=\"btn btn-success\" type=\"submit\">
							</fieldset>
							</div>
							</form>";
						}

					}
				}
				
				$mnu .= "<ul>";
				foreach($decode as $k1 => $item){
					if(is_array($item[1])){
						$mnu .= "<li><span class=\"label label-success\">{$item[0]}</span> [<a href=\"".SITE_URL."/backstage/menu/?del={$k1}\">x</a>]";
						$mnu .= "<ul>";
						foreach($item[1] as $k2 => $sub){
							$mnu .= "<li><a class=\"label label-warning\" href=\"{$sub[1]}\">{$sub[0]}</a> [<a href=\"".SITE_URL."/backstage/menu/?del={$k1}&sub={$k2}\">x</a>]</li>";
						}
						$mnu .= "<li>[<a href=\"".SITE_URL."/backstage/menu/?add={$k1}&sub=".($k2 + 1)."\">+</a>]</li></ul>";
						$mnu .= "</li>";
					}
					else{
						$mnu .= "<li><a class=\"label label-success\" href=\"{$item[1]}\">{$item[0]}</a> [<a href=\"".SITE_URL."/backstage/menu/?del={$k1}\">x</a>]</li>";
					}
				}
				$mnu .= "<li><a class=\"label label-primary\" href=\"".SITE_URL."/backstage/menu/?add=".($k1 + 1)."\">+ Menu</a></li>";
				$mnu .= "<li><a class=\"label label-primary\" href=\"".SITE_URL."/backstage/menu/?add=".($k1 + 1)."&dropdown=yes\">+ Dropdown</a></li>";
				$mnu .= "</ul>";
				
				
				$lay_00 = "
				<div class=\"row\">
				<div class=\"col-md-12\">
				<h1>{$post->lang['BS_MENU_EDITOR']}</h1>
				{$menu}
				{$msg}
				</div>
				</div>";
				if($form == ""){
					$form = "<fieldset><legend>{$post->lang['BS_MENU_EDITOR']}</legend>
					<p>{$post->lang['BS_MENU_EDITOR_LABEL']}</p></fieldset>";
				}
				$lay_01 = "<div class=\"row\">
				<div class=\"col-md-4\">
				{$form}
				</div>
				<div class=\"col-md-8\">
				<fieldset>
				<legend>{$post->lang['BS_MY_MENU']}</legend>
				{$mnu}
				</fieldset>
				</div>
				</div>";
				$render_head = $post->_header("[Menu Editor] - ".SITE_TITLE);
				$render_body = $post->_body($lay_00,$lay_01);
				$render_foot = $post->_foot($copyright,$post->page_navi(count($index),$x,"/backstage/manage/?hal="));
			}
			elseif($request == "delete"){
				if(isset($_GET["id"])){
					if(unlink("sp_post/{$_GET["id"]}")){
						$post->create_index();
						header("Location: ".SITE_URL."/backstage/manage");
					}
				}
			}
			elseif($request == "edit"){
				if(isset($_GET["id"])){
					$handle = fopen("sp_post/{$_GET['id']}","r");
					$readfile = fread($handle,filesize("sp_post/{$_GET['id']}"));
					$dec = json_decode($readfile,true);
					fclose($handle);
					$layout = "<div class=\"header\">
					<h1>{$post->lang['LABEL_EDITING']}{$dec['title']}</h1>{$menu}
					</div><form action=\"".SITE_URL."/backstage/create\" method=\"post\">
					<div class=\"form-group\">
					<label>{$post->lang['LABEL_TITLE']}</label>
					<input value=\"{$dec['title']}\" class=\"form-control\" type=\"text\" name=\"title\">
					</div>
					<div class=\"form-group\">
					<label>{$post->lang['LABEL_ENTRY']}</label>
					<textarea id=\"konten\" class=\"form-control\" name=\"konten\">{$dec['konten']}</textarea>
					</div>
					<div class=\"form-group\">
					<label>{$post->lang['LABEL_TAG']}</label>
					<input value=\"{$dec['tag']}\" class=\"form-control\" type=\"text\" name=\"tag\">
					</div><div class=\"form-group\">
					<label>{$post->lang['LABEL_PERMALINK']}</label>
					<input value=\"{$dec['url']}\" class=\"form-control\" type=\"text\" name=\"url\">
					</div>
					<label>Saving Options</label>
					<div class=\"radio\">
					<label>
					<input type=\"radio\" name=\"saveopt\" id=\"optionsRadios1\" value=\"publish\" checked>
					Publish
					</label>
					</div>
					<div class=\"radio\">
					<label>
					<input type=\"radio\" name=\"saveopt\" id=\"optionsRadios2\" value=\"draft\">
					Draft
					</label>
					</div>
					<div class=\"form-group\">
					<input type=\"hidden\" name=\"_revise\" value=\"1\">
					<input type=\"hidden\" name=\"_postname\" value=\"{$_GET['id']}\">
					</div><div class=\"form-group\">
					<input class=\"btn btn-success\" type=\"submit\">
					</div></form>
					";
					// render
					
					$render_head = $post->_header("[Edit] {$dec['title']} - ".SITE_TITLE,$extracss);
					$render_body = $post->_body($msg,$layout);
					$render_foot = $post->_foot($copyright,NULL,$extrajs);
				}
			}
			elseif($request == "create"){
				if(isset($_POST["_create"]) || isset($_POST["_revise"])){
					$index = $post->load_json('index.json');
					if(isset($_POST["_revise"])){
						$latest = $_POST["_postname"];
						$latest = ltrim($latest,"post");
					}
					else{
						$latest = key($index[0]);
						$latest = ltrim($latest,"post");
						$latest += 1;
					}
					$date = date("d F Y");
					$title = $_POST["title"];
					$title = $post->strip_html_tags($title);
					$url = $_POST["url"];
					$tag = $_POST["tag"];
					$status = $_POST["saveopt"];
					
					if($status == "publish"){
						$status = "publish";
					}
					elseif($status == "draft"){
						$status = "draft";
					}
					else{
						$status = "publish";
					}
					
					if(!isset($title) || $title == ""){
						$title = "Untitled-{$latest}";
					}
					if(!isset($url) || $url == ""){
						$url = str_replace(" ","-",strtolower($title));	
					}
					if(!isset($tag) || $tag == ""){
						$tag = "out-topic";
					}
					$konten = $_POST["konten"];
					$meta = array("date" => $date,"title" => $title,"url" => $url,"tag" => $tag, "status" => $status, "konten" => $konten);
					$data = json_encode($meta,JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
					if(in_array("",$meta)){
						$_SESSION["msg"] = "{$post->lang['BS_MSG_NOT_EMPTY']}";
						header("Location: ".SITE_URL."/backstage/create");
						exit;
					}
					//$meta = $post->fill_string(implode("|",$meta),128);
					//$data = $meta . $konten;
					$filename = "post{$latest}";
					if(isset($_POST["_revise"])){
						unlink("sp_post/{$filename}");
					}
					$handle = fopen("sp_post/{$filename}","a+");
					$write = fwrite($handle,$data);
					if($write != false){
						$msg = "<div style=\"margin-top:30px;\" class=\"alert alert-success alert-dismissible\" role=\"alert\">";
						$msg .= "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">";
						$msg .= "<span aria-hidden=\"true\">&times;</span></button>";
						$msg .= "Post saved with filename <b>{$filename}</b> (".strlen($data)."bytes)";
						$msg .= " <b><a target=\"_blank\" href=\"".SITE_URL."/{$url}\">View Post</a></b>";
						$msg .= "</div>";
						fclose($handle);
						$post->create_index();
					}
					
				}
				if(isset($_SESSION["msg"])){
					$msg_err = $_SESSION["msg"];
					unset($_SESSION["msg"]);
				}
				else{
					$msg_err = "";
				}
				$layout = "<div class=\"header\">
				<h1>{$post->lang['CREATE_POST']}</h1>
				</div>
				{$msg_err}
				{$menu}
				<form action=\"".SITE_URL."/backstage/create\" method=\"post\">
				<div class=\"form-group\">
				<label>{$post->lang['LABEL_TITLE']}</label>
				<input class=\"form-control\" type=\"text\" name=\"title\">
				</div>
				<div class=\"form-group\">
				<label>{$post->lang['LABEL_ENTRY']}</label>
				<textarea id=\"konten\" class=\"form-control\" name=\"konten\"></textarea>
				</div>
				<div class=\"form-group\">
				<label>{$post->lang['LABEL_TAG']}</label>
				<input class=\"form-control\" type=\"text\" name=\"tag\">
				</div><div class=\"form-group\">
				<label>{$post->lang['LABEL_PERMALINK']}</label>
				<input class=\"form-control\" type=\"text\" name=\"url\">
				</div>
				<label>Saving Options</label>
				<div class=\"radio\">
				<label>
				<input type=\"radio\" name=\"saveopt\" id=\"optionsRadios1\" value=\"publish\" checked>
				Publish
				</label>
				</div>
				<div class=\"radio\">
				<label>
				<input type=\"radio\" name=\"saveopt\" id=\"optionsRadios2\" value=\"draft\">
				Draft
				</label>
				</div>
				<div class=\"form-group\">
				<input type=\"hidden\" name=\"_create\" value=\"1\">
				</div><div class=\"form-group\">
				<input class=\"btn btn-success\" type=\"submit\">
				</div></form>
				";
				// render
				$render_head = $post->_header("[Create] ".date("d F Y")." - ".SITE_TITLE,$extracss);
				$render_body = $post->_body($msg,$layout);
				$render_foot = $post->_foot($copyright,NULL,$extrajs);
			}
			elseif($request == "backup"){
				echo 1;
			}
		}
		else{	
			## -------------------
			## THIS IS SINGLE POST
			## -------------------
			$post = new sedot;
			// load index
			$index = $post->load_json('index.json');
			// [ESSENTIAL] get file pointer
			$file_pointer = $post->get_filename($index,$get_request[0]);
			
			if(! $file_pointer){
				header('HTTP/1.0 404 Not Found');
				$msg = "<div class=\"row\">
				<div class=\"col-md-12\">
				<h1>404 Not Found</h1><hr>
				<p>Sorry, the page you requested is not found on this blog.</p></div>
				</div>";
				$render_head = $post->_header("[404] - ".SITE_TITLE);
				$render_body = $post->_body($msg);
				$render_foot = $post->_foot();
				exit;
			}
			// format the post
			$post->format_post($file_pointer);
			
			## ------------------
			## COMMENT HANDLING
			##-------------------
			
			// load comments index
			$index_com = $post->load_json('COMMENT',$file_pointer);

			// assign capcay (code challenge)
			if(isset($_SESSION["celeng"])){
				$capcay_old = $_SESSION["celeng"];
			}
			// define capcay
			$_SESSION["celeng"] = substr(crc32(md5(microtime(true))),1,7);
			$capcay_new = $_SESSION["celeng"];
			// capcay alert
			$capcay_alert = "
			<div class=\"alert alert-%s alert-dismissible\" role=\"alert\">
			<span class=\"close\"><a href=\"".SITE_URL."/{$get_request[0]}\">&times;</a></span>
			%s
			</div>";
			
			// delete comment (warning: no confirmation)
			if(isset($_GET["del"])){
				if($is_admin){
					if(isset($_GET["sub"])){
						if(isset($_GET["sub2"])){
							unset($index_com[$_GET["del"]][1][$_GET["sub"]][1][$_GET["sub2"]]);
						}
						else{
							unset($index_com[$_GET["del"]][1][$_GET["sub"]]);
						}
					}
					else{
						unset($index_com[$_GET["del"]]);
					}
					$filename = "sp_comment/{$file_pointer}.json";
					if(file_exists($filename)){
						unlink($filename);
					}
					$wr_com = fopen($filename,"a+");
					$comment_json_encode = json_encode($index_com, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
					$wrcom = fwrite($wr_com,$comment_json_encode);
					fclose($wr_com);
				}
			}
			// comment pagination
			if(isset($_GET["page"]) && $_GET["page"] != ""){
				$comment_page = $_GET["page"];
			}
			else{
				$comment_page = 1;
			}
			// pre process external url
			if(isset($_GET["url"]) && $_GET["url"] != ""){
				$ex_url = $_GET["url"];
				if(isset($index_com[$ex_url][0]["website"])){		
					if(isset($index_com[$ex_url][1][$_GET["sub"]][0]["website"])){
						if(isset($index_com[$ex_url][1][$_GET["sub"]][1][$_GET["sub2"]]["website"])){
							$ex_query = "?com=1&url={$_GET['url']}&sub={$_GET['sub']}&sub2={$_GET['sub2']}";
							$ex_url = $index_com[$ex_url][1][$_GET["sub"]][1][$_GET["sub2"]]["website"];
							$just_redirect = true;							
						}
						else{
							$ex_query = "?com=1&url={$_GET['url']}&sub={$_GET['sub']}";
							$ex_url = $index_com[$ex_url][1][$_GET["sub"]][0]["website"];
							$just_redirect = true;
						}
					}
					else{
						$ex_query = "?com=1&url={$_GET['url']}";
						$ex_url = $index_com[$ex_url][0]["website"];
						$just_redirect = true;
					}
				}
				else{
					$just_redirect = false;
				}
			}
			else{
				$just_redirect = false;
			}
			// comment pointer for postid
			$pointer_comment = $_POST["pointer"];
			// some toggles
			if($is_admin){
				$is_admin = true;
				// static styling
				$nick = "<span class=\"label label-primary\">Admin</span>";
				$comm_text = $post->strip_html_tags($_POST["comment"]);	
				$com_email = GRAV_EMAIL;
			}
			else{
				$nick = $post->strip_html_tags($_POST["usernick"]);
				$comm_text = $post->strip_html_tags($_POST["comment"]);
				$com_email = $post->strip_html_tags($_POST["email"]);
			}
			// fiter email
			if(filter_var($com_email, FILTER_VALIDATE_EMAIL)){
				$email = $com_email;
			}
			else{
				$email = "";
			}
			// website field handling
			if(isset($_POST["website"]) && $_POST["website"] != ""){
				$website = $_POST["website"];
			}
			else{
				$website = "";
			}

			# VALIDATING THE COMMENT DATA

			if(isset($pointer_comment) && $pointer_comment != ""){
				// validate 1: check empty fields
				if($nick !== "" && $comm_text !== "" && $email != ""){
					// validate 2: check capcay code
					if($_POST["capcay"] == $capcay_old || isset($_SESSION["LOGIN"])){
						// format structure
						$comment_json = array("date" => date("d F Y H:i:s"),"nick" => $nick, "website" => $website, "email" => $email, "comment" => $comm_text);
						// move array pointer to end
						if(isset($_GET["reply"])){
							// validate 3: check the validity of $_GET["reply"]
							if(isset($index_com[$_GET["reply"]])){
								if(isset($_GET["sub"])){
									// validate 4: check the validity of $_GET["sub"]
									if(isset($index_com[$_GET["reply"]][1][$_GET["sub"]])){
										end($index_com[$_GET["reply"]][1][$_GET["sub"]][1]);
										// assign to pointer
										$new_key = key($index_com[$_GET["reply"]][1][$_GET["sub"]][1]) + 1;
										$index_com[$_GET["reply"]][1][$_GET["sub"]][1][$new_key] = $comment_json;
										$valid = true;
									}
									else{
										// suspicius user
										$valid = false;
									}
								}
								else{
									end($index_com[$_GET["reply"]][1]);
									// assign to pointer
									$new_key = key($index_com[$_GET["reply"]][1]) + 1;
									$index_com[$_GET["reply"]][1][$new_key][0] = $comment_json;
									$valid = true;
								}
							}
							else{
								// suspicius user
								$valid = false;
							}
						}
						else{
							end($index_com);
							// assign to pointer
							$new_key = key($index_com) + 1;
							$index_com[$new_key][0] = $comment_json;
							$valid = true;
						}
						
						// fetch the $valid status
						if($valid){
							// existing filename (overwriting)
							$filename = "sp_comment/{$file_pointer}.json";
							if(file_exists($filename)){
								unlink($filename);
							}
							// ready for writing
							$wr_com = fopen($filename,"a+");
							// encode back the new data to JSON
							$comment_json_encode = json_encode($index_com,JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
							// write started
							$wrcom = fwrite($wr_com,$comment_json_encode);
							// write end
							fclose($wr_com);
							// format alert
							$nocapcay = sprintf($capcay_alert,"success",$post->lang['COMMENT_PUBLISHED']);
						}
						else{
							// format alert
							$nocapcay = sprintf($capcay_alert,"danger",$post->lang['INVALID_OPERATION']);
						}
					}
					else{
						// format alert
						$nocapcay = sprintf($capcay_alert,"danger",$post->lang['CAPCAY_FAILURE']);
					}
				}
			}
			else{
				$nocapcay = "";
			}
			## COMMENTS
			$gravatar_link = 'http://www.gravatar.com/avatar/%s?s=64';
			$comment_page_split = array_chunk($index_com,5,true);
			$delbut_link = "<a href=\"".SITE_URL."/{$get_request[0]}/?com=1&del=%s\">&times;</a>";
			foreach($comment_page_split[$comment_page - 1] as $com_key => $key){
				
				if($is_admin){
					$delbut = sprintf($delbut_link,"{$com_key}");
				}
				else{
					$delbut = "";
				}
				
				$cf .= "<li class=\"media\">
						<a class=\"media-left\" href=\"#\">
						<img data-holder-rendered=\"true\" src=\"".sprintf($gravatar_link,md5($key[0]['email']))."\" style=\"width: 64px; height: 64px;\" alt=\"64x64\">
						</a>
						<div class=\"media-body\">";
				$cf .= "<h4 class=\"media-heading\">";
				if($key[0]['website'] == ""){
					$url = "{$key[0]['nick']}";
				}
				else{
					$url = "<a rel=\"nofollow\" href=\"".SITE_URL."/{$get_request[0]}/?com=1&url={$com_key}\">{$key[0]['nick']}</a>";
				}
				$cf .= "{$url} <small>{$key[0]['date']}</small> {$delbut}</h4>";
				$cf .= "<p>{$key[0]['comment']}</p>";
				$cf .= "<a rel=\"nofollow\" href=\"".SITE_URL."/{$get_request[0]}/?com=1&reply={$com_key}\">Reply</a>";
				## NESTED COMMENTS
				foreach($key[1] as $nestkey => $nest_lv1){
					
						if($is_admin){
							$delbut = sprintf($delbut_link,"{$com_key}&sub={$nestkey}");
						}
						else{
							$delbut = "";
						}
					
						$cf .= "<div class=\"media\">
						<a class=\"media-left\" href=\"#\">
						<img data-holder-rendered=\"true\" src=\"".sprintf($gravatar_link,md5($nest_lv1[0]['email']))."\" style=\"width: 64px; height: 64px;\" alt=\"64x64\">
						</a>
						<div class=\"media-body\">";
						$cf .= "<h4 class=\"media-heading\">";
						if($nest_lv1[0]['website'] == ""){
							$url2 = "{$nest_lv1[0]['nick']}";
						}
						else{
							$url2 = "<a rel=\"nofollow\" href=\"".SITE_URL."/{$get_request[0]}/?com=1&url={$com_key}&sub={$nestkey}\">{$nest_lv1[0]['nick']}</a>";
						}
						$cf .= "{$url2} <small>{$nest_lv1[0]['date']}</small> {$delbut}</h4>";
						$cf .= "<p>{$nest_lv1[0]['comment']}</p>";
						$cf .= "<a rel=\"nofollow\" href=\"".SITE_URL."/{$get_request[0]}/?com=1&reply={$com_key}&sub={$nestkey}\">Reply</a>";
						
						foreach($nest_lv1[1] as $lv2 => $nest_lv2){
							
							if($is_admin){
								$delbut = sprintf($delbut_link,"{$com_key}&sub={$nestkey}&sub2={$lv2}");
							}
							else{
								$delbut = "";
							}
							
							$cf .= "<div class=\"media\">
							<a class=\"media-left\" href=\"#\">
							<img data-holder-rendered=\"true\" src=\"".sprintf($gravatar_link,md5($nest_lv2['email']))."\" style=\"width: 64px; height: 64px;\" alt=\"64x64\">
							</a>
							<div class=\"media-body\">";
							$cf .= "<h4 class=\"media-heading\">";
							if($nest_lv2['website'] == ""){
								$url2 = "{$nest_lv2['nick']}";
							}
							else{
								$url2 = "<a rel=\"nofollow\" href=\"".SITE_URL."/{$get_request[0]}/?com=1&url={$com_key}&sub={$nestkey}&sub2={$lv2}\">{$nest_lv2['nick']}</a>";
							}
							$cf .= "{$url2} <small>{$nest_lv2['date']}</small> {$delbut}</h4>";
							$cf .= "<p>{$nest_lv2['comment']}</p>";
							$cf .= "</div></div>";
							
						}
						
						$cf .= "</div></div>";
					
					
				}
				## END OF NESTED
				$cf .= "</div></li>";
				
			}
			
			
			## Handling the user website
			if($just_redirect == false){
				$post_konten = $post->post->content;
				$the_comment = "<ul class=\"media-list\">".$cf."</ul>";
			}
			else{
				$the_comment = "";
				if(isset($_SESSION["celeng2"])){
					$capcay_url_old = $_SESSION["celeng2"];
				}
				$_SESSION["celeng2"] = substr(crc32(md5(microtime(true))),1,4);
				$capcay_url_new = $_SESSION["celeng2"];
				if($_POST["capcay2"] == $capcay_url_old){
					header("Location: {$ex_url}");
				}
				else{
					$post_konten = "
					<form action=\"".SITE_URL."/{$get_request[0]}/{$ex_query}\" method=\"post\">
					<fieldset>
					<legend>{$post->lang['EXTERNAL_LINK']} [{$ex_url}]</legend>
					<p>{$post->lang['MSG_EXTERNAL_LINK']}</p>
					<label>{$post->lang['MSG_CAPCAY_INPUT']} {$capcay_url_new}</label>
					<input style=\"width:150px\" class=\"form-control\" type=\"text\" name=\"capcay2\"><br>
					<input class=\"btn btn-sm btn-primary\" type=\"submit\"><br><br>
					{$post->lang['MSG_CONTINUE_READ']} <a href=\"".SITE_URL."/{$get_request[0]}\">{$post->post->title}</a> &rarr;
					</fieldset>
					</form>";
				}
			}
			## -----------------------
			## END OF COMMENT HANDLING
			## -----------------------
			
			
			// Rendering the page ...
			$cut_desc = $post->strip_html_tags($post->post->content);
			$meta_desc = "<meta name=\"description\" content=\"".$post->strip_html_tags(substr($cut_desc,0,150))."\">";
			$contain = "
			<div class=\"page-header\">
			<h1><a href=\"".SITE_URL."\">".SITE_TITLE."</a></h1>
			<h2>".SITE_DESC."</h2>
			</div>
			<div class=\"row\">
				<div class=\"col-md-7\">
				{$nocapcay}
				<h2 title=\"{$post->post->title}\">{$post->post->title}</h2>
				<time title=\"{$post->post->datetime}\" class=\"label label-success\" datetime=\"".date('d-m-Y', strtotime($post->post->datetime))."\">{$post->post->datetime}</time>
				<span>{$post->post->tag}
				<a class=\"label label-warning\" href=\"".SITE_URL."/{$post->post->permalink}\" title=\"{$post->lang['POST_PERMALINK_FOR']}{$post->post->title}\">{$post->lang['POST_PERMALINK']}</a>
				</span><br><br>
				{$post_konten}
				<div class=\"row comment\">";
					if($just_redirect == false){
						$contain .= "
						<div class=\"col-md-12\"><h3>";
						$count_comment = count($index_com);
						if($count_comment < 1){
							$contain .= $post->lang['NO_COMMENT'];
						}
						elseif($count_comment == 1){
							$contain .= $post->lang['ONE_COMMENT'];
						}
						else{
							$contain .= sprintf($post->lang['COMMENT_COUNT'],$count_comment);
						}
						$contain .= "</h3>
						<div class=\"row\"><div class=\"col-md-12\">";
						$contain .= $the_comment;
						if(count($comment_page_split) > 1){
							$contain .= "<div class=\"col-md-12\">";
							$contain .= $post->page_navi(count($comment_page_split),$comment_page,"/".$get_request[0]."/?com=1&page=","nofollow");
							$contain .= "</div>";
						}
						$contain .= "</div>
						</div>
						</div>
						<div class=\"col-md-12\">";
						
						if(isset($_GET['reply'])){
							$contain .= "<h3>{$post->lang['WRITE_REPLY']}</h3>";
							$contain .= "<div class=\"alert alert-info\">
							<span class=\"close\" aria-label=\"Close\">
							<b><a href=\"".SITE_URL."/{$get_request[0]}\">CANCEL</a></b>
							</span>";
							if(isset($_GET['sub'])){
								$query = "/?com=1&reply={$_GET['reply']}&sub={$_GET['sub']}";
								$contain .= "Reply to ";
								$contain .= "<b>{$index_com[$_GET['reply']][0]['nick']}</b> &rarr; ";
								$contain .= "<b>{$index_com[$_GET['reply']][1][$_GET['sub']][0]['nick']}</b> : <i>";
								$contain .= $index_com[$_GET['reply']][1][$_GET['sub']][0]["comment"];
								
							}
							else{
								$query = "/?com=1&reply={$_GET['reply']}";
								$contain .= "Reply to <b>{$index_com[$_GET['reply']][0]['nick']}</b> : <i>";
								$contain .= $index_com[$_GET['reply']][0]["comment"];
							}
							
							$contain .= "</i></div>";
						}
						else{
							$contain .= "<h3>{$post->lang['WRITE_COMMENT']}</h3>";
							$query = "";
						}
						$contain .= "
						<form action=\"".SITE_URL."/{$get_request[0]}{$query}\" method=\"post\">
						<div class=\"form-group\">
						";
						
						if(! $is_admin){
							$contain .= "<label>{$post->lang['NICKNAME']}</label>
							<input class=\"form-control\" type=\"text\" name=\"usernick\">
							<label>{$post->lang['EMAIL']}</label>
							<input class=\"form-control\" type=\"text\" name=\"email\">
							<label>{$post->lang['WEBSITE']}</label>
							<input class=\"form-control\" type=\"text\" value=\"".SITE_URL."\" name=\"website\">";
						}
						
						$contain .= "
						<label>{$post->lang['WRITE_COMMENT_LABEL']}</label>
						<textarea style=\"width:100%\" class=\"form-control\" name=\"comment\"></textarea>";
						
						if(! $is_admin){
							$contain .= "<label>{$post->lang['MSG_CAPCAY_INPUT']} {$capcay_new}</label>
							<input style=\"width:150px\" class=\"form-control\" type=\"text\" name=\"capcay\">";
						}
						
						$contain .= "<input type=\"hidden\" name=\"pointer\" value=\"".md5($file_pointer)."\"/><br>
						<input class=\"btn btn-sm btn-primary\" type=\"submit\">
						</div>
						</form>
						</div>";
					}
					$contain .= "
				</div>
				</div>
			<div class=\"col-md-2\">
			{$search_form}
			</div>
			<div class=\"col-md-3\">
			{$post->arsip()}
			{$post->tag_cloud()}
			</div>
			</div>"
			;
			// render
			$render_head = $post->_header($post->post->title . " - ".SITE_TITLE,$meta_desc);
			$render_body = $post->_body($contain);
			$render_foot = $post->_foot($copyright);
		}
	}
	else{
		## ------------------
		## THIS IS FRONT PAGE
		## ------------------
		$post = new sedot;
		$index = $post->load_json('index.json');
		
		$pagenum = 1;
		$pointer = $index[$pagenum - 1];
		//custom template
		$body1 = "
		<div class=\"page-header\">
		<h1>".SITE_TITLE."</h1>
		<h2>".SITE_DESC."</h2>
		</div>
		<div class=\"row\">
			<div class=\"col-md-7\">
			{$post->index_page($pointer,false,$is_admin)}
			</div>
			<div class=\"col-md-2\">
			{$search_form}
			</div>
			<div class=\"col-md-3\">
			{$post->arsip()}
			{$post->tag_cloud()}
			</div>
		</div>"
		;
		$body2 = "
		<div class=\"row\">
		<div class=\"col-md-12\">
		{$post->page_navi(count($index),$pagenum)}
		</div>
		</div>";
		// $render
		$render_head = $post->_header(SITE_TITLE . " - Homepage");
		$render_body = $post->_body($body1,$body2);
		$render_foot = $post->_foot($copyright);
	}
}
elseif(count($get_request >= 2)){
	if($get_request[0] == "page"){
		## ----------------------
		## THIS IS FRONT PAGE > 1
		## ----------------------
		$post = new sedot;
		$index = $post->load_json('index.json');
		
		$pagenum = $get_request[1];
		$pointer = $index[$pagenum - 1];
		$contain = "
		<div class=\"page-header\">
		<h1><a href=\"".SITE_URL."\">".SITE_TITLE."</a></h1>
		<h2>".SITE_DESC."</h2>
		</div>
		<div class=\"header\">
		<h2><small>{$post->lang['PAGE']} {$pagenum}</small></h2>
		</div>
		<div class=\"row\">
			<div class=\"col-md-7\">
			{$post->index_page($pointer,false,$is_admin)}
			</div>
			<div class=\"col-md-2\">
			{$search_form}
			</div>
			<div class=\"col-md-3\">
			{$post->arsip()}
			{$post->tag_cloud()}
			</div>
		</div>"
		;
		// $render
		$render_head = $post->_header(SITE_TITLE." - {$post->lang['PAGE']} {$pagenum}");
		$render_body = $post->_body($contain);
		$render_foot = $post->_foot($copyright,$post->page_navi(count($index),$pagenum));
	}
	elseif($get_request[0] == "search" || $get_request[0] == "tag" || $get_request[0] == "timeline"){
		## ---------------------------------
		## THIS IS SEARCH || TAG || TIMELINE
		## ---------------------------------
		if(isset($_POST["q"])){
			$filter = str_replace(" ","+",$_POST["q"]);
			header("Location: ".SITE_URL."/{$get_request[0]}/{$filter}");
		}
		$keyword = strtolower($get_request[1]);
		$multi_key = explode("+",$keyword);
		if($get_request[0] == "timeline"){
			if(isset($get_request[1])){
				$query = $get_request[1];
				$keyword = $get_request[1];
				if(isset($get_request[2])){
					if(substr($get_request[2],0,5) == "?page"){
						$query = $get_request[1];
						$keyword = $get_request[1];
					}
					else{
						$query = $get_request[2]." ".$get_request[1];
						$keyword = $get_request[1]."/".$get_request[2];
					}
					if(isset($get_request[3])){
						if(substr($get_request[3],0,5) == "?page"){
						$query = $get_request[2]." ".$get_request[1];
						$keyword = $get_request[1]."/".$get_request[2];
						}
						else{
						$query = $get_request[3]." ".$get_request[2]." ".$get_request[1];
						$keyword = $get_request[1]."/".$get_request[2]."/".$get_request[3];
						}
						if(isset($get_request[4])){
							if(substr($get_request[4],0,5) == "?page"){
								$query = $get_request[3]." ".$get_request[2]." ".$get_request[1];
								$keyword = $get_request[1]."/".$get_request[2]."/".$get_request[3];;
							}
						}
					}
				}
			}
			$page = $_GET["page"];
			if(! isset($page)){
				$page = 1;
			}
			$keyword = strtolower($keyword);
			$keyword = rtrim($keyword,"/");
			$is_multi = false;
		}
		elseif(count($multi_key) < 2){
			if(@$get_request[2] == ""){
				$page = 1;
			}
			else{
				$page = $get_request[2];
			}
			$query = $keyword;
			unset($multi_key);
			$is_multi = false;
		}
		else{
			if(@$get_request[2] == ""){
				$page = 1;
			}
			else{
				$page = $get_request[2];
			}
			$query = $multi_key;
			$count_multi = count($query);
			$is_multi = true;
		}
		$post = new sedot;
		$index = $post->load_json('index.json');
		$count = count($index);
		for($i = 0;$i <= $count;$i++){
			foreach($index[$i] as $key => $value){
				$split = explode("|",$value);
				if($get_request[0] == "search"){
					$c_title = "{$post->lang['TITLE_SEARCH_RESULT']} ".rtrim($keyword,"+");
					$title = $value;
				}
				elseif($get_request[0] == "timeline"){
					$title = strtolower($split[0]);
					$c_title = "{$post->lang['RESULT_BLOG_ARCHIVE']} {$query}";
				}
				else{
					$title = strtolower($split[3]);
					$c_title = "{$post->lang['RESULT_POST_TAGGED']} {$query}";
				}
				if($is_multi){
					for($m = 0;$m <= $count_multi;$m++){
						$pos = strpos($title,$query[$m]);
						if($pos === false){
						}
						else{
							$result[] = $key;
							break 1;
						}
					}
				}else{
					$pos = strpos($title,$query);
					if($pos === false){
					}
					else{
						$result[] = $key;
					}
				}
			}
		}
		if(count($result) > 5){
			$chunk = array_chunk($result,5,true);
		}
		else{
			if(count($result) < 1){
				$no_result = true;
			}
			else{
				$chunk[0] = $result;
			}
		}
		if($no_result){
			$out = $post->lang['NOT_FOUND'];
		}
		else{
			$out = $post->index_page(array_flip($chunk[$page -  1]),true,$is_admin);
		}
		$body1 = "<div class=\"page-header\">
		<h1><a href=\"".SITE_URL."\">".SITE_TITLE."</a></h1>
		<h2>".SITE_DESC."</h2>
		</div>
		<div class=\"header\">
		<h2><small>{$c_title}</small></h2>
		</div>
		<div class=\"row\">
		<div class=\"col-md-7\">
		{$out}
		</div>
		<div class=\"col-md-2\">
		{$search_form}
		</div>
		<div class=\"col-md-3\">
		{$post->arsip()}
		{$post->tag_cloud()}
		</div>
		</div>";
		$render_head = $post->_header($c_title . " (Page {$page}) - ".SITE_TITLE);
		$render_body = $post->_body($body1);
		if($get_request[0] != "timeline"){
			$render_foot = $post->_foot($copyright,$post->page_navi(count($chunk),$page,"/{$get_request[0]}/{$keyword}/"));
		}else{
			$render_foot = $post->_foot($copyright,$post->page_navi(count($chunk),$page,"/{$get_request[0]}/{$keyword}/?page="));
		}
	}
	else{
		header('HTTP/1.0 404 Not Found');
		$post = new sedot;
		$msg = "<div class=\"row\">
		<div class=\"col-md-12\">
		<h1>404 Not Found</h1><hr>
		<p>Sorry, the page you requested is not found on this blog.</p></div>
		</div>";
		$render_head = $post->_header("[404] - ".SITE_TITLE);
		$render_body = $post->_body($msg);
		$render_foot = $post->_foot();
	}
}
?>
