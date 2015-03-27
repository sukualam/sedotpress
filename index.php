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


/* ------------------------*/
/* EDIT THIS CONFIGURATION */
/* ------------------------*/

### START CONFIG ###

// admin username, default is "admin"
define("ADMIN_NICKNM","admin");

// admin password, default is "root"
define("ADMIN_PASSWD","root");
// your rocks blog title
define("SITE_TITLE","Sedotpress");
// your rocks blog description
define("SITE_DESC","my sedotpress blog");

// your blog url (without "/" at end)
//--- for example:
//--- http://example.com
//--- http://example.com/blog
//--- http://example.com/blog/subblog
//--- INFO: maybe you need edit the .htaccess
//--------- if you install this in non root "/" directory
//--- etc ....
define("SITE_URL","http://localhost");

// comment to debugging
error_reporting(0);

### END CONFIG ###


/* --------------------------------------------*/
/* --------- THE MAGIC IS BELOW HERE ----------*/
/* --just leave it alone if you feel comfort --*/
/* ------- feel some bugs? go hack this ------ */
/* --------------- THANK YOU ------------------*/

$find_base = explode("/",rtrim(SITE_URL,"/"),4);
if(! isset($find_base[3]) || $find_base[3] == ""){
	$requested = $_SERVER["REQUEST_URI"];
}
else{
	$requested = str_replace("/{$find_base[3]}","",$_SERVER["REQUEST_URI"]);
}

$get_request = explode("/",$requested);
array_shift($get_request);

$extracss = "
<link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.3.0/css/font-awesome.min.css\">
<link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/froala-editor/1.2.6/css/froala_editor.min.css\">
";
$extrajs = "
<script src=\"https://cdnjs.cloudflare.com/ajax/libs/froala-editor/1.2.6/js/froala_editor.min.js\"></script>
<script src=\"https://cdnjs.cloudflare.com/ajax/libs/froala-editor/1.2.6/js/plugins/tables.min.js\"></script>
<script src=\"https://cdnjs.cloudflare.com/ajax/libs/froala-editor/1.2.6/js/plugins/lists.min.js\"></script>
<script>$(function(){\$('#konten').editable({inlineMode: false})});</script>";

class sedot{
/*
function: create_index()
description:
it create static index cache in json (index.json, archive.json, tags.json)
and create a sitemap
*/
function create_index(){
	$dir = "data/";
	$scandir = array_diff(scandir($dir), array('..', '.'));
	natsort($scandir); # life saver
	$reversed = array_reverse($scandir);
	foreach($reversed as $filename){
		$handle = fopen($dir.$filename,"r");
		$read = fread($handle,128);
		$container[$filename] = trim($read,"%");
		fclose($handle);
	}
	if(file_exists("sitemap.xml")){
		unlink("sitemap.xml");
	}
	if(true){
		$create_sitemap = fopen("sitemap.xml","a");
		$xml_sitemap = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
		<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"> 
		";
		fwrite($create_sitemap,$xml_sitemap);
		foreach($container as $postid => $metadata){
			$meta_explode = explode("|",$metadata);
			$write_node = "<url><loc>".SITE_URL."/{$meta_explode[2]}</loc></url>
			";
			fwrite($create_sitemap,$write_node);
		}
		$xml_sitemap = "</urlset>";
		fwrite($create_sitemap,$xml_sitemap);
		fclose($create_sitemap);
	}
	$chunk = array_chunk($container,5,TRUE);
	if(file_exists("rss.xml")){
		unlink("rss.xml");
	}
	if(true){
		for($i = 0;$i<= 1;$i++){
			foreach($chunk[$i] as $postid => $metadata){
			$explode = explode("|",$metadata);
			$title = $explode[1];
			$url = SITE_URL."/{$explode[2]}";
			$open_file = fopen("data/{$postid}","r");
			fseek($open_file,128);
			$read_file = fread($open_file,filesize("data/{$postid}"));
			$read_file = htmlentities($read_file);
			fclose($open_file);
			$temp_data[] = "
			<item>
			<title>{$title}</title>
			<link>{$url}</link>
			<description>{$read_file}</description>
			</item>";
			}
		}
		$create_rss = fopen("rss.xml","a+");
		$rss_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
		<rss version=\"2.0\">
		<channel>
		<title>".SITE_TITLE."</title>
		<link>".SITE_URL."</link>
		<description>".SITE_DESC."</description>";
		fwrite($create_rss,$rss_xml);
		fwrite($create_rss,implode("\n",$temp_data));
		$rss_xml = "
		</channel>
		</rss>";
		fwrite($create_rss,$rss_xml);
		fclose($create_rss);
	}
	$json = json_encode($chunk);
	for($i=0;$i<=count($chunk);$i++){
		foreach($chunk[$i] as $key => $val){
			$expl = explode("|",$val);
			$date = $expl[0];
			$tagpl[] = explode(",",$expl[3]);
			$datx = explode(" ",$date);
			$hier[$datx[2]][$datx[1]][$datx[0]] .= $key.",";
		}
	}
	foreach($tagpl as $val){
		foreach($val as $vall){
			$tags[] = $vall;
		}
	}
	$counttag = array_count_values($tags);
	if(file_exists("tags.json")){
		unlink("tags.json");
	}
	$tag_index = fopen("tags.json","a+");
	$tag_encode_json = json_encode($counttag);
	$tgwrite = fwrite($tag_index,$tag_encode_json);
	fclose($tag_index);
	if(file_exists("archive.json")){
		unlink("archive.json");
	}
	$reverse_year = array_reverse($hier,true);
	$year_index = fopen("archive.json","a+");
	$encode_json = json_encode($reverse_year);
	$write = fwrite($year_index,$encode_json);
	fclose($year_index);
	if(file_exists("index.json")){
		unlink("index.json");
	}
	$json_filename = "index.json";
	$save = fopen($json_filename,"a+");
	fwrite($save,$json);
	fclose($save);
}
function comment_index($post){
	$dir = "comment/{$post}/";
	$scandir = array_diff(scandir($dir), array('..','.','index.json'));
	natsort($scandir); # life saver
	$reversed = array_reverse($scandir);
	foreach($reversed as $filename){
		$handle = fopen($dir.$filename,"r");
		$read = fread($handle,30);
		$container[$filename] = trim($read,"%");
		fclose($handle);
	}
	$json = json_encode($container);
	if(file_exists("comment/{$post}/index.json")){
		unlink("comment/{$post}/index.json");
	}
	$json_filename = "comment/{$post}/index.json";
	$save = fopen($json_filename,"a+");
	fwrite($save,$json);
	fclose($save);
}
function create_comment($post){
	$dir = "comment/{$post}";
	if(! is_dir($dir)){
		if(mkdir($dir)){
			return true;
		}
		else{
			return false;
		}
	}
	else{
		return true;
	}
}
// load index.json and transform it to associated array
function load_index(){
	$read = fopen("index.json","r");
	$str = fread($read,filesize("index.json"));
	fclose($read);
	$x = json_decode($str,true);
	return $x;
}
function load_comment($post){
	$read = fopen("comment/{$post}/index.json","r");
	$str = fread($read,filesize("comment/{$post}/index.json"));
	fclose($read);
	$x = json_decode($str,true);
	return $x;
}
// load archive.json and transform it to associated array
function load_archive(){
	$read = fopen("archive.json","r");
	$str = fread($read,filesize("archive.json"));
	fclose($read);
	$x = json_decode($str,true);
	return $x;
}
// load tags.json and transform it to associated array
function load_tags(){
	$read = fopen("tags.json","r");
	$str = fread($read,filesize("tags.json"));
	fclose($read);
	$x = json_decode($str,true);
	return $x;
}
function tag_cloud(){
	$x = self::load_tags();
	$lst .= "<h3>Tag Clouds</h3>";
	$lst .= "<ul>";
	foreach($x as $key => $val){
		$lst .= "<li><a title=\"Tag {$key} has {$val} post\" href=\"".SITE_URL."/tag/{$key}\">{$key}</a>
		<span title=\"post count\">({$val})</span>
		</li>";
	}
	$lst .= "</ul>";
	return $lst;
}

function arsip(){
	$x = self::load_archive();
	$lst .= "<h3>Blog Archive</h3>
<ul>";
	foreach($x as $year => $month){
		$lst .= "
		<li>
		<a title=\"Post archives in {$year}\" href=\"".SITE_URL."/timeline/{$year}\">{$year}</a>
			<ul>
			";
		foreach($month as $key => $post){
			foreach($post as $val){
				$xxx = explode(",",rtrim($val,","));
				$count += count($xxx);
			}
			$lst .= "<li>
			<a title=\"Post archives in {$key} {$year}\" href=\"".SITE_URL."/timeline/{$year}/".strtolower($key)."\">{$key}</a>
			<span>({$count})</span>
			</li>
			";
			$count = 0;
		}
		$lst .= "</ul>
		</li>
		";
	}
	$lst .= "</ul>";
	return $lst;
}
function index_page($post_array){
	foreach($post_array as $key => $c){
		$u = fopen("data/".$key,"r");
		$postmeta = fread($u,128);
		$postmeta = rtrim($postmeta,"%");
		$split_meta = explode("|",$postmeta);
		$meta_date = $split_meta[0];
		$meta_title = $split_meta[1];
		$meta_url = $split_meta[2];
		$meta_tags = explode(",",$split_meta[3]);
		foreach($meta_tags as $val){
			$tag .= "<a title=\"tagged as {$val}\" class=\"label label-primary\" href=\"".SITE_URL."/tag/".strtolower($val)."\">{$val}</a>
			";
		}
		fseek($u,128);
		#$konten = fread($u,filesize("data/".$key)-128);
		$konten = fread($u,160);
		$konten = self::strip_html_tags($konten);
		$cutlastword = strrpos($konten, ' ');
		$konten = substr($konten, 0, $cutlastword);
		$group[] = "
		  <div class=\"col-md-12\">
			<div class=\"row\">
			<h2><a title=\"{$meta_title}\" rel=\"bookmark\" href=\"".SITE_URL."/{$meta_url}\">{$meta_title}</a></h2>
			<time title=\"date posted\" class=\"badge\" datetime=\"".date('d-m-Y', strtotime($meta_date))."\">{$meta_date}</time>
			<span>{$tag}</span>
			</div>
			<div class=\"row\">
			<p class=\"lead\">{$konten}
			<span><a title=\"Read article {$meta_title}\" href=\"".SITE_URL."/{$meta_url}\">Readmore</a></span>
			</p>
			</div>
		  </div>
		  ";
		fclose($u);
		unset($tag);
	}
	return implode("",$group);
}

// fill remaining character with %%%
function fill_string($str,$len){
	$fill_len = $len - strlen($str);
	for($i = 0;$i < $fill_len;$i++){
		$filler .= "%";
	}
	$filled_string = $str . $filler;
	return $filled_string;
}

// required for single post query or to find filename by permalink
// it seems radical :)
function get_filename($array,$permalink){
	foreach($array as $indexnum => $files){
		foreach($array[$indexnum] as $filename => $filemeta){
			$expl = explode("|",$filemeta);
			if($expl[2] == $permalink){
			$target_filename = $filename;
			return $target_filename;
			break 2;
			}
		}
	}
}

// the basic page navigation with 3 parameter
// param 1: usually total of result or posts (it will divided later)
// param 2: because value in param 1 is divided, param 2 is pointer
// param 3: (optional) custom url for page link
function page_navi($count,$page,$prefix_url){
	if(strlen($prefix_url) <= 1){
		$prefix = SITE_URL."/page/";
	}
	else{
		$prefix = SITE_URL.$prefix_url;
	}
	$row = 5;
	$current = $page;
	$total_item = $count;
	for($i = 0;$i < $total_item;$i++){
		$raw[$i] = $i + 1;
	}
	$stack = array_chunk($raw,$row);
	$stack_count = count($stack);
	$output .= "<nav>";
	$output .= "<ul class=\"pagination\">";
	for($i = 0;$i <= $stack_count;$i++){     
		if(in_array($current,$stack[$i])){  
			if($i > 0){
				$output .= "<li>";
				$output .= "<a title=\"Go to previous page\" class='page-numbers' href='{$prefix}".($current - 1)."'>&laquo;</a>";
				$output .= "</li>";
			}
			foreach($stack[$i] as $raw){
			if($raw == $current){
				$output .= "<li class=\"active\">";
				$output .= "<span title=\"Current page\" class='page-numbers current'>{$raw}</span>";
				$output .= "</li>";
			}else{
				$output .= "<li>";
				$output .= "<a title=\"Go to page {$raw}\" class='page-numbers' href='{$prefix}".$raw."'>{$raw}</a>";
				$output .= "</li>";
			}
			}
			if($current < $total_item){
				$output .= "<li>";
				$output .= "<a title=\"Go to next page\" class='page-numbers' href='{$prefix}".($current + 1)."'>&raquo;</a>";
				$output .= "</li>";
			}
			break 1;
		}
	}
	$output .= "</ul>";
	$output .= "</nav>";
	return $output;
}
function format_post($filename){
	$handle = fopen("data/".$filename,"r");
	$read_meta = fread($handle,128);
	$read_meta = rtrim($read_meta,"%");
	$split_meta = explode("|",$read_meta);
	$meta_tags = explode(",",$split_meta[3]);
	foreach($meta_tags as $val){
	$tag .= "<a title=\"post tagged in {$val}\" class=\"label label-primary\" href=\"".SITE_URL."/tag/".strtolower($val)."\">{$val}</a>
	";
	}
	fseek($handle,128);
	$konten = fread($handle,filesize("data/".$filename) - 128);
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
    <meta name=\"generator\" content=\"sedotpress\">
	{$b}
	<title>{$a}</title>
	<link href=\"".SITE_URL."/rss\" rel=\"alternate\" type=\"application/rss+xml\"/>
    <link rel=\"stylesheet\" href=\"https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css\">
    
	
    <!--[if lt IE 9]>
      <script src=\"https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js\"></script>
      <script src=\"https://oss.maxcdn.com/respond/1.4.2/respond.min.js\"></script>
    <![endif]-->
	{$c}
  </head>
  <body style=\"background:#efefef\">
  <div style=\"margin-top:35px;margin-bottom:35px;background:#fff;box-shadow:0px 3px 3px #aaa\" class=\"container\">{$d}";
}
function _body($a,$b,$c,$d){
	echo $a;
	echo $b;
	echo $c;
	echo $d;
}
function _foot($a,$b,$c,$d){
	echo "
    <script src=\"https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js\"></script>
    <script src=\"https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js\"></script>
	{$c}
    <div>{$b}</div>
    <footer style=\"padding:10px\" class=\"footer\">{$a}</footer>
   </div>
  </body>
</html>";
}
function strip_html_tags($text){
	// Copyright (c) 2008, David R. Nadeau, NadeauSoftware.com.
	// All rights reserved.
	$text = preg_replace(
		array(
			// Remove invisible content
			'@<head[^>]*?>.*?</head>@siu',
			'@<style[^>]*?>.*?</style>@siu',
			'@<script[^>]*?.*?</script>@siu',
			'@<object[^>]*?.*?</object>@siu',
			'@<embed[^>]*?.*?</embed>@siu',
			'@<applet[^>]*?.*?</applet>@siu',
			'@<noframes[^>]*?.*?</noframes>@siu',
			'@<noscript[^>]*?.*?</noscript>@siu',
			'@<noembed[^>]*?.*?</noembed>@siu',
			// Add line breaks before & after blocks
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
	// Remove all remaining tags and comments and return.
	return strip_tags($text);
}
} // endclass


/* Now, it time to process the requested url
 * It will listen $_SERVER["REQUEST_URI"] and parse it
 * It will render the page depending requested url
 */

// widgets, yeah
$copyright = "&copy; 2015 sedot.space | Powered by <a href=\"https://github.com/sukualam/sedotpress\">Sedotpress</a>";

$search_form = "
<h3>Search</h3>
<form action=\"".SITE_URL."/search/\" method=\"post\">
<div class=\"input-group\">
      <input type=\"text\" name=\"q\" class=\"form-control\" placeholder=\"Search for...\">
      <span class=\"input-group-btn\">
        <button class=\"btn btn-default\" type=\"submit\">Go!</button>
      </span>
    </div>
</form>
<h3>Links</h3>
<ul>
<li><a title=\"".SITE_TITLE." RSS Feeds\" href=\"".SITE_URL."/rss\">RSS Feeds</a></li>
</ul>
";

if(count($get_request) == 1 || $get_request[0] == "backstage"){
	if($get_request[0] != ""){
		if($get_request[0] == "build"){
			## -------------
			## REBUILD INDEX
			## -------------
			$post = new sedot;
			$build = $post->create_index();
		}
		elseif($get_request[0] == "rss"){
			## -----------
			## THIS IS RSS
			## -----------
			header("Content-Type: application/rss+xml; charset=ISO-8859-1");
			$open_rss = fopen("rss.xml","r");
			$read_rss = fread($open_rss,filesize("rss.xml"));
			fclose($open_rss);
			echo $read_rss;
		}
		elseif($get_request[0] == "backstage"){
			## -----------------
			## THIS IS BACKSTAGE
			## -----------------
			session_start();
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
				$msg_1 = "Your code is <b>{$_SESSION["KEY"]}</b>
				Enter it here <form action=\"".SITE_URL."/backstage\" method=\"post\">
				<input type=\"text\" name=\"key\">
				<input type=\"submit\">
				</form>
				Back to <a href=\"".SITE_URL."\">".SITE_TITLE."</a><br><hr>
				{$copyright}
				";
				if($_POST["key"] == $_SESSION["KEY"]){
					$_SESSION["TIMES"] = 0;
					$_SESSION["LOGIN"] = "almost";
					$msg_1 = "Its correct!, you can continue...<br>
					<a href=\"".SITE_URL."/backstage\" class=\"btn btn-primary\">Continue</a> or back to <a href=\"".SITE_URL."\">".SITE_TITLE."</a><br><hr>
					{$copyright}
					";
				}
				echo $msg_1;
				break;
			}
			if($_SESSION["LOGIN"] == "almost"){
				$_SESSION["TIMES"]++;
				$msg_2 = "<h1>".SITE_TITLE." &middot; <small>Backstage Area</small></h1>";
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
					$_SESSION["LOGIN"] = $_SESSION["KEY"];
					header("Location: ".SITE_URL."/backstage");
				}
				$render_head = $post->_header("Login into backstage..");
				$render_body = $post->_body($msg_3);
				$render_foot = $post->_foot($copyright);
				break;
			}
			#echo "WELCOME! session_id: {$_SESSION["LOGIN"]}";
			$request = $get_request[1];
			$menu = "
				[<a href=\"".SITE_URL."/backstage/\">Backstage</a>]
				[<a href=\"".SITE_URL."/backstage/manage\">Manage</a>]
				[<a href=\"".SITE_URL."/backstage/create\">Create</a>]";
			if($request == ""){
				$h = "
				<div class=\"header\">
				<h1>Welcome, ".ADMIN_NICKNM."!</h1>
				</div><div class=\"row\">
				<div class=\"col-md-4\">
				<h2><a href=\"".SITE_URL."/backstage/manage\">Manage</a></h2>
				<p>Manage all posts, view the detail of the posts.</p>
				</div>
				<div class=\"col-md-4\">
				<h2><a href=\"".SITE_URL."/backstage/create\">Create</a></h2>
				<p>Create a new post, and auto rebuild index.</p>
				</div>
				<div class=\"col-md-4\">
				<h2><a href=\"".SITE_URL."/backstage/logout\">Logout</a></h2>
				<p>Exit the administration session.</p>
				</div>
				</div>
				";
				$render_head = $post->_header("Backstage");
				$render_body = $post->_body($h);
				$render_foot = $post->_foot($copyright);
			}
			elseif($request == "logout"){
				session_start();
				session_destroy();
				header("Location: ".SITE_URL);
			}
			elseif($request == "manage"){
				$index = $post->load_index();
				if(isset($_GET["hal"])){
					$x = $_GET["hal"];
				}else{
					$x = 1;
				}
				$list = "<table class=\"table\">";
				foreach($index[$x - 1] as $key => $meta){
					$exp = explode("|",$meta);
					$list .= "<tr><td>{$exp[0]}</td>
					<td>{$exp[1]}</td>
					<td>{$exp[3]}</td>
					<td><a href=\"".SITE_URL."/backstage/edit/?id={$key}\">EDIT</a></td>
					<td><a href=\"".SITE_URL."/backstage/delete/?id={$key}\">DELETE</a></td>
					</tr>";
				}
				$list .= "</table>";
				$lay_00 = "<div class=\"row\">
				<div class=\"col-md-6\"><h1>Backstage</h1></div>
				</div>";
				$lay_01 = "<div class=\"row\">
				<div class=\"col-md-8\">{$list}</div>
				</div>";
				$render_head = $post->_header("Manage post");
				$render_body = $post->_body($lay_00,$lay_01,$menu);
				$render_foot = $post->_foot($copyright,$post->page_navi(count($index),$x,"/backstage/manage/?hal="));
			}
			elseif($request == "delete"){
				if(isset($_GET["id"])){
					if(unlink("data/{$_GET["id"]}")){
						$post->create_index();
						header("Location: ".SITE_URL."/backstage/manage");
					}
				}
			}
			elseif($request == "edit"){
				if(isset($_GET["id"])){
					$handle = fopen("data/{$_GET['id']}","r");
					$read128 = fread($handle,128);
					$read128_trim = rtrim($read128,"%");
					$get_meta = explode("|",$read128_trim);
					fseek($handle,128);
					$read_konten = fread($handle,filesize("data/{$_GET['id']}") - 128);
					fclose($handle);
					$layout = "
					<form action=\"".SITE_URL."/backstage/create\" method=\"post\">
					<div class=\"form-group\">
					<label>Entry</label>
					<textarea id=\"konten\" class=\"form-control\" name=\"konten\">{$read_konten}</textarea>
					<div class=\"form-group\">
					<label>Title</label>
					<input value=\"{$get_meta[1]}\" class=\"form-control\" type=\"text\" name=\"title\">
					</div><div class=\"form-group\">
					<label>Permalink</label>
					<input value=\"{$get_meta[2]}\" class=\"form-control\" type=\"text\" name=\"url\">
					</div><div class=\"form-group\">
					<label>Tag</label>
					<input value=\"{$get_meta[3]}\" class=\"form-control\" type=\"text\" name=\"tag\">
					</div><div class=\"form-group\">
					<input type=\"hidden\" name=\"_revise\" value=\"1\">
					<input type=\"hidden\" name=\"_postname\" value=\"{$_GET['id']}\">
					</div><div class=\"form-group\">
					<input class=\"btn btn-success\" type=\"submit\">
					</div></div></form>
					";
					// render
					
					$render_head = $post->_header("Edit post",$extracss);
					$render_body = $post->_body($msg,$layout,$menu);
					$render_foot = $post->_foot($copyright,NULL,$extrajs);
				}
			}
			elseif($request == "create"){
				if(isset($_POST["_create"]) || isset($_POST["_revise"])){
					$index = $post->load_index();
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
					$url = $_POST["url"];
					$tag = $_POST["tag"];
					$konten = $_POST["konten"];
					$meta = array($date,$title,$url,$tag);
					if(in_array("",$meta)){
						$_SESSION["msg"] = "All field must not empty";
						header("Location: /backstage/create");
						exit;
					}
					$meta = $post->fill_string(implode("|",$meta),128);
					$data = $meta . $konten;
					$filename = "post{$latest}";
					if(isset($_POST["_revise"])){
						unlink("data/{$filename}");
					}
					$handle = fopen("data/{$filename}","a+");
					$write = fwrite($handle,$data);
					if($write != false){
						$msg = "<div class=\"alert alert-success alert-dismissible\" role=\"alert\">";
						$msg .= "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">";
						$msg .= "<span aria-hidden=\"true\">&times;</span></button>";
						$msg .= "Written {$filename} ".strlen($data)."bytes of file";
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
				$layout = "{$msg_err}
				<form action=\"".SITE_URL."/backstage/create\" method=\"post\">
				<div class=\"form-group\">
				<label>Entry</label>
				<textarea id=\"konten\" class=\"form-control\" name=\"konten\"></textarea>
				</div>
				<div class=\"form-group\">
				<label>Title</label>
				<input class=\"form-control\" type=\"text\" name=\"title\">
				</div><div class=\"form-group\">
				<label>Permalink</label>
				<input class=\"form-control\" type=\"text\" name=\"url\">
				</div><div class=\"form-group\">
				<label>Tag</label>
				<input class=\"form-control\" type=\"text\" name=\"tag\">
				</div><div class=\"form-group\">
				<input type=\"hidden\" name=\"_create\" value=\"1\">
				</div><div class=\"form-group\">
				<input class=\"btn btn-success\" type=\"submit\">
				</div></form>
				";
				// render
				$render_head = $post->_header("Create a Post",$extracss);
				$render_body = $post->_body($msg,$layout,$menu);
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
			$index = $post->load_index();
			session_start();
			if(isset($_SESSION["celeng"])){
				$capcay_old = $_SESSION["celeng"];
			}
			$_SESSION["celeng"] = substr(crc32(md5(microtime(true))),1,7);
			$capcay_new = $_SESSION["celeng"];
			$file_pointer = $post->get_filename($index,$get_request[0]);
			$is_comment = $post->create_comment($file_pointer);
			$pointer_comment = $_POST["pointer"];
			if(isset($_SESSION["LOGIN"])){
				$nick = "<i>Admin</i>";
				$comm_text = $post->strip_html_tags($_POST["comment"]);
				
			}
			else{
				$nick = $post->strip_html_tags($_POST["usernick"]);
				$comm_text = $post->strip_html_tags($_POST["comment"]);
			}
			if(isset($pointer_comment) && $pointer_comment != ""){
				if($nick !== "" && $comm_text !== ""){
					if($_POST["capcay"] == $capcay_old || isset($_SESSION["LOGIN"])){
						$date = date("d F Y");
						$meta = array($date,$nick);
						$meta = $post->fill_string(implode("|",$meta),30);
						$data = $meta . $comm_text;
						if($is_comment){
							$index_com = $post->load_comment($file_pointer);
							$latest = key($index_com);
							$latest = ltrim($latest,"comm");
							$latest += 1;
							$filename = "comment/{$file_pointer}/comm{$latest}";
							$wr_com = fopen($filename,"a+");
							$wrcom = fwrite($wr_com,$data);
							fclose($wr_com);
							$post->comment_index($file_pointer);
						}
						$nocapcay = "<div class=\"alert alert-success alert-dismissible\" role=\"alert\">
						<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">
						<span aria-hidden=\"true\">&times;</span>
						</button>Comment published !</div>";
					}
					else{
						$nocapcay = "<div class=\"alert alert-danger alert-dismissible\" role=\"alert\">
						<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">
						<span aria-hidden=\"true\">&times;</span>
						</button>Please enter the correct capcay !</div>";
					}
				}
			}else{
				$nocapcay = "";
			}
			//load comment
			$index_comment = $post->load_comment($file_pointer);
			foreach($index_comment as $key => $val){
				$meta = explode("|",$val);
				$read_com = fopen("comment/{$file_pointer}/{$key}","r");
				fseek($read_com,30);
				$read_i = fread($read_com,filesize("comment/{$file_pointer}/{$key}"));
				fclose($read_com);
				if(isset($_SESSION["LOGIN"])){
					$delbut = "<a href=\"".SITE_URL."/{$get_request[0]}/?del={$key}\">(del)</a>";
				}
				else{
					$delbut = "";
				}
				$comment_format .= "<div class=\"the_komeng\">
				<h4>{$meta[1]} <small>{$meta[0]}</small> {$delbut}</h4>";
				$comment_format .= "<p>{$read_i}</p>";
				$comment_format .= "</div>
				";
			}
			$print = $post->format_post($file_pointer);
			// custom template
			$meta_desc = "<meta name=\"description\" content=\"".$post->strip_html_tags(substr($post->post->content,0,150))."\">";
			$body_contain = "
			<div class=\"site-title\">
			<h1><a href=\"".SITE_URL."\">".SITE_TITLE."</a></h1>
			<h2>".SITE_DESC."</h2>
			</div>

			<div class=\"row\">
				<div class=\"col-md-7\">
				{$nocapcay}
				<h2 title=\"{$post->post->title}\">{$post->post->title}</h2>
				<time title=\"{$post->post->datetime}\" class=\"badge\" datetime=\"".date('d-m-Y', strtotime($post->post->datetime))."\">{$post->post->datetime}</time>
				<span>{$post->post->tag}
				<a class=\"label label-warning\" href=\"".SITE_URL."/{$post->post->permalink}\" title=\"permalink for {$post->post->title}\">permalink</a>
				</span><br>
				{$post->post->content}
				<div class=\"row comment\">
					<div class=\"col-md-12\">
					<h3>Comments</h3>
					
					{$comment_format}
					</div>
					<div class=\"col-md-12\">
						<h3>Write a comment</h3>
							<form action=\"".SITE_URL."/{$get_request[0]}\" method=\"post\">
								<div class=\"form-group\">
								<label>Nickname</label>
								<input class=\"form-control\" type=\"text\" name=\"usernick\">
								<label>Write a comment</label>
								<textarea style=\"width:100%\" class=\"form-control\" name=\"comment\"></textarea>
								<label>Enter code: {$capcay_new}</label>
								<input style=\"width:150px\" class=\"form-control\" type=\"text\" name=\"capcay\">
								<input type=\"hidden\" name=\"pointer\" value=\"".md5($file_pointer)."\"/><br>
								<input class=\"btn btn-sm btn-primary\" type=\"submit\">
								</div>
							</form>
					</div>
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
			$render_body = $post->_body($body_contain);
			$render_foot = $post->_foot($copyright);
		}
	}else{
		## ------------------
		## THIS IS FRONT PAGE
		## ------------------
		$post = new sedot;
		$index = $post->load_index();
		$pagenum = 1;
		$pointer = $index[$pagenum - 1];
		//custom template
		$body1 = "
		<div class=\"site-title\">
		<h1>".SITE_TITLE."</h1>
		<h2>".SITE_DESC."</h2>
		</div>
		<div class=\"row\">
			<div class=\"col-md-7\">
			{$post->index_page($pointer)}
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
		$index = $post->load_index();
		$pagenum = $get_request[1];
		$pointer = $index[$pagenum - 1];
		$body_contain = "
		<div class=\"site-title\">
		<h1><a href=\"".SITE_URL."\">".SITE_TITLE."</a></h1>
		<h2>".SITE_DESC."</h2>
		</div>
		<div class=\"header\">
		<h2><small>Page {$pagenum}</small></h2>
		</div>
		<div class=\"row\">
			<div class=\"col-md-7\">
			{$post->index_page($pointer)}
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
		$render_head = $post->_header(SITE_TITLE." - Page {$pagenum}");
		$render_body = $post->_body($body_contain);
		$render_foot = $post->_foot($copyright,$post->page_navi(count($index),$pagenum));
	}
	elseif($get_request[0] == "search" || $get_request[0] == "tag" || $get_request[0] == "timeline"){
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
		#echo $query;
		#var_dump($query);
		$post = new sedot;
		$index = $post->load_index();
		$count = count($index);
		for($i = 0;$i <= $count;$i++){
			foreach($index[$i] as $key => $value){
				$split = explode("|",$value);
				if($get_request[0] == "search"){
					$c_title = "Search result(s) for ".rtrim($keyword,"+");
					$title = $value;
				}
				elseif($get_request[0] == "timeline"){
					$title = strtolower($split[0]);
					$c_title = "Blog archive in {$query}";
				}
				else{
					$title = strtolower($split[3]);
					$c_title = "Posts tagged in {$query}";
				}
				if($is_multi){
					for($m = 0;$m <= $count_multi;$m++){
						$pos = strpos($title,$query[$m]);
						if($pos === false){
							# ...
						}
						else{
							$result[] = $key;
							break 1;
						}
					}
				}else{
					$pos = strpos($title,$query);
					if($pos === false){
						# ...
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
			$output = "Not found";
		}
		else{
			$output = $post->index_page(array_flip($chunk[$page -  1]));
		}
		
		$body1 = "
		<div class=\"site-title\">
		<h1><a href=\"".SITE_URL."\">".SITE_TITLE."</a></h1>
		<h2>".SITE_DESC."</h2>
		</div>
		<div class=\"header\">
		<h2><small>{$c_title}</small></h2>
		</div>
		<div class=\"row\">
			<div class=\"col-md-7\">
			{$output}
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
		$render_head = $post->_header($c_title . " (Page {$page}) - ".SITE_TITLE);
		$render_body = $post->_body($body1);
		if($get_request[0] != "timeline"){
			$render_foot = $post->_foot($copyright,$post->page_navi(count($chunk),$page,"/{$get_request[0]}/{$keyword}/"));
		}else{
			$render_foot = $post->_foot($copyright,$post->page_navi(count($chunk),$page,"/{$get_request[0]}/{$keyword}/?page="));
		}
	}
	else{
		session_start();
		if(isset($_SESSION["LOGIN"])){
			$post = new sedot;
			$index = $post->load_index();
			$file_pointer = $post->get_filename($index,$get_request[0]);
			if(unlink("comment/{$file_pointer}/{$_GET["del"]}")){
				$post->comment_index($file_pointer);
				header("Location: ".SITE_URL."/{$get_request[0]}");
			}
		}
		else{
			header("Location: ".SITE_URL);
		}
	}
}
?>
