<?php
/*
The MIT License (MIT)

Copyright (c) [2015] [Sukualam]

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

## VERSION: 0.1 beta
/* ------------------------*/
/* EDIT THIS CONFIGURATION */
/* ------------------------*/

### START CONFIG ###
// admin username, default is "admin"
define("ADMIN_NICKNM","admin");
// admin password, default is "root"
define("ADMIN_PASSWD","root");
// your rocks blog title
define("SITE_TITLE","FlatSingleBlog");
// url
define("SITE_URL","http://localhost");
// comment to debugging
error_reporting(0);
### END CONFIG ###


/* --------------------------------------------*/
/* --------- THE MAGIC IS BELOW HERE ----------*/
/* --just leave it alone if you feel comfort --*/
/* ------- feel some bugs? go hack this ------ */
/* --------------- THANK YOU ------------------*/

$waktu = microtime(true);
// there is just one class here
// i doubt the class concept, but
// i notice that this script is much faster
// and green :)
// benchmarking 1000 posts just += 0.005s in my cheap machine
// WTF :)
class cgile_init{
// create file "index.json" & "archive.json"
// that contain bunch of index 
// posts filename & post datetime
// for better performance...
// coz, it saved a "static formatted data array"
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
	$chunk = array_chunk($container,5,TRUE);
	$json = json_encode($chunk);
	for($i=0;$i<=count($chunk);$i++){
		foreach($chunk[$i] as $key => $val){
			$expl = explode("|",$val);
			$date = $expl[0];
			$datx = explode(" ",$date);
			$hier[$datx[2]][$datx[1]][$datx[0]] .= $key.",";
		}
	}
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

// load index.json and transform it to associated array
function load_index(){
	$read = fopen("index.json","r");
	$str = fread($read,filesize("index.json"));
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
function arsip(){
	$x = self::load_archive();

	$lst .= "<h3>Blog Archive</h3>
<ul>";
	foreach($x as $year => $month){
		$lst .= "
		<li>
		<a title=\"Post archives in {$year}\" href=\"".SITE_URL."/timeline/{$year}\">{$year}</a>
		</li>
		<li style=\"list-style-type:none\">
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
		$group[] = "
		  <div class=\"col-xs-12 col-md-12\">
			<div class=\"row\">
			<h2><a title=\"{$meta_title}\" rel=\"bookmark\" href=\"".SITE_URL."/{$meta_url}\">{$meta_title}</a></h2>
			<time title=\"date posted\" class=\"badge\" datetime=\"".date('d-m-Y', strtotime($meta_date))."\">{$meta_date}</time>
			<span>{$tag}</span>
			</div>
			<div class=\"row\">
			{$konten}
			<span>
			<a class=\"btn btn-xs btn-success\" title=\"Read article {$meta_title}\" href=\"".SITE_URL."/{$meta_url}\">Readmore</a>
			</span>
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
	$tag .= "<span><a class=\"label label-primary\" href=\"".SITE_URL."/tag/".strtolower($val)."\">{$val}</a></span> ";
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
<html lang=\"en\">
  <head>
    <meta charset=\"utf-8\">
    <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
    <meta name=\"generator\" content=\"FlatSingleBlog\">
    <title>{$a}</title>

    <!-- Bootstrap -->
    <link rel=\"stylesheet\" href=\"https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css\">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src=\"https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js\"></script>
      <script src=\"https://oss.maxcdn.com/respond/1.4.2/respond.min.js\"></script>
    <![endif]-->
  </head>
  <body>
  {$b}
  <div class=\"container\">";
  echo $c;
  echo $d;
}
function _body($a,$b,$c,$d){
	echo $a;
	echo $b;
	echo $c;
	echo $d;
}
function _foot($a,$b,$c,$d){
	echo "<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src=\"https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js\"></script>
    <script src=\"https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js\"></script>
    <div>{$b}</div>
    <footer>{$a}</footer>
   </div>
  </body>
</html>";
}
} // endclass


/* Now, it time to process the requested url
 * It will listen $_SERVER["REQUEST_URI"] and parse it
 * It will render the page depending requested url
 */

$get_request = explode("/",$_SERVER["REQUEST_URI"]);
$search_form = "<h3>Search</h3><form action=\"".SITE_URL."/search/\" method=\"post\">
<input type=\"text\" name=\"q\">
<input type=\"submit\">
</form>";

array_shift($get_request);

if(count($get_request) == 1 || $get_request[0] == "backstage"){
	if($get_request[0] != ""){
		if($get_request[0] == "build"){
			## -------------
			## REBUILD INDEX
			## -------------
			$post = new cgile_init;
			$build = $post->create_index();
		}
		elseif($get_request[0] == "backstage"){
			## -----------------
			## THIS IS BACKSTAGE
			## -----------------
			session_start();
			$post = new cgile_init;
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
				";
				if($_POST["key"] == $_SESSION["KEY"]){
					$_SESSION["TIMES"] = 0;
					$_SESSION["LOGIN"] = "almost";
					$msg_1 = "Its correct!, you can continue...
					<a href=\"".SITE_URL."/backstage\" class=\"btn btn-primary\">Continue</a>
					";
				}
				echo $msg_1;
				break;
			}
			if($_SESSION["LOGIN"] == "almost"){
				$_SESSION["TIMES"]++;
				$msg_2 = "
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
				$render_body = $post->_body($msg_2);
				$render_foot = $post->_foot();
				break;
			}
			#echo "WELCOME! session_id: {$_SESSION["LOGIN"]}";
			$request = $get_request[1];
			$menu = "
				[<a href=\"/backstage/\">Backstage</a>]
				[<a href=\"/backstage/manage\">Manage</a>]
				[<a href=\"/backstage/create\">Create</a>]";
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
				<h2><a href=\"".SITE_URL."/backstage/backup\">Backup/Restore</a></h2>
				<p>Easy backup / restore your posts.</p>
				</div>
				</div>
				";
				$render_head = $post->_header("Backstage");
				$render_body = $post->_body($h);
				$render_foot = $post->_foot("<p>Copyright 2015 FlatSingleBlog</p>");
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
				$render_foot = $post->_foot(NULL,$post->page_navi(count($index),$x,"/backstage/manage/?hal="));
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
					$layout = "<form action=\"".SITE_URL."/backstage/create\" method=\"post\">
					<div class=\"form-group\">
					<label>Entry</label>
					<textarea class=\"form-control\" name=\"konten\">{$read_konten}</textarea>
					</div>
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
					</div></form>
					";
					// render
					$render_head = $post->_header("Edit post");
					$render_body = $post->_body($msg,$layout,$menu);
					$render_foot = $post->_foot();
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
						$msg .= "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button>";
						$msg .= "Written {$filename} ".strlen($data)."bytes of file";
						$msg .= "</div>";
						fclose($handle);
						$post->create_index();
					}
					
				}
				$layout = "<form action=\"".SITE_URL."/backstage/create\" method=\"post\">
				<div class=\"form-group\">
				<label>Entry</label>
				<textarea class=\"form-control\" name=\"konten\"></textarea>
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
				$render_head = $post->_header("Create a Post");
				$render_body = $post->_body($msg,$layout,$menu);
				$render_foot = $post->_foot();
			}
			elseif($request == "logout"){
				session_destroy();
				header("Location: /backstage");
			}
			elseif($request == "backup"){
				echo 1;
			}
		}
		else{
			## -------------------
			## THIS IS SINGLE POST
			## -------------------
			$post = new cgile_init;
			$index = $post->load_index();
			$file_pointer = $post->get_filename($index,$get_request[0]);
			unset($index);
			$print = $post->format_post($file_pointer);
			// widgets, yeah
			$copyright = "<p>Powered by FlatSingleBlog. Generated in: ";
			$copyright .= "<i>".substr(microtime(true) - $waktu,0,5)."s</i>";
			$copyright .= "</p>";
			// custom template
			$body_contain = "
			<div class=\"site-title\">
			<h1><a href=\"".SITE_URL."\">".SITE_TITLE."</a></h1>
			</div>
			<div class=\"header\">
			<h2>{$post->post->title}</h2>
			<time class=\"badge\" datetime=\"".date('d-m-Y', strtotime($post->post->datetime))."\">{$post->post->datetime}</time>
			{$post->post->tag}
			</div>
			<div class=\"row\">
				<div class=\"col-md-9\">
				{$post->post->content}
				</div>
				<div class=\"col-md-3\">
				
				</div>
			</div>"
			;
			// render
			$render_head = $post->_header($post->post->title . " - ".SITE_TITLE);
			$render_body = $post->_body($body_contain,$search_form);
			$render_foot = $post->_foot($copyright);
		}
	}else{
		## ------------------
		## THIS IS FRONT PAGE
		## ------------------
		$post = new cgile_init;
		$index = $post->load_index();
		$pagenum = 1;
		$pointer = $index[$pagenum - 1];
		//custom template
		$body1 = "
		<div class=\"site-title\">
		<h1>".SITE_TITLE."</h1>
		</div>
		<div class=\"header\">
		<h2>{$post->post->title}</h2>
		</div>
		<div class=\"row\">
			<div class=\"col-md-7\">
			<div class=\"row-fluid\">
			{$post->index_page($pointer)}
			</div>
			</div>
			<div class=\"col-md-2\">
			{$search_form}
			</div>
			<div class=\"col-md-3\">
			{$post->arsip()}
			</div>
		</div>"
		;
		$body2 = "
		<div class=\"row\">
		<div class=\"col-md-12\">
		{$post->page_navi(count($index),$pagenum)}
		</div>
		</div>";
		// widgets, yeah
		$copyright = "<p>Powered by FlatSingleBlog. Generated in: ";
		$copyright .= "<i>".substr(microtime(true) - $waktu,0,5)."s</i>";
		$copyright .= "</p>";
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
		$post = new cgile_init;
		$index = $post->load_index();
		$pagenum = $get_request[1];
		$pointer = $index[$pagenum - 1];
		$body_contain = "
		<div class=\"site-title\">
		<h1><a href=\"".SITE_URL."\">".SITE_TITLE."</a></h1>
		</div>
		<div class=\"header\">
		<h2>Halaman {$pagenum}</h2>
		</div>
		<div class=\"row\">
			<div class=\"col-md-9\">
			{$post->index_page($pointer)}
			</div>
			<div class=\"col-md-3\">
			{$post->arsip()}
			</div>
		</div>"
		;
		// widgets, yeah
		$copyright = "<p>Powered by FlatSingleBlog. Generated in: ";
		$copyright .= "<i>".substr(microtime(true) - $waktu,0,5)."s</i>";
		$copyright .= "</p>";
		// $render
		$render_head = $post->_header(SITE_TITLE." - Page {$pagenum}");
		$render_body = $post->_body($body_contain,$search_form);
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
		$post = new cgile_init;
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
		</div>
		<div class=\"header\">
		<h2><i>{$c_title}</i></h2>
		</div>
		<div class=\"row\">
			<div class=\"col-md-7\">
			<div class=\"row-fluid\">
			{$output}
			</div>
			</div>
			<div class=\"col-md-2\">
			{$search_form}
			</div>
			<div class=\"col-md-3\">
			{$post->arsip()}
			</div>
		</div>"
		;
		// widgets, yeah
		$copyright = "<p>Powered by FlatSingleBlog. Generated in: ";
		$copyright .= "<i>".substr(microtime(true) - $waktu,0,5)."s</i>";
		$copyright .= "</p>";
		$render_head = $post->_header($c_title . " (Page {$page}) - ".SITE_TITLE);
		$render_body = $post->_body($body1);
		if($get_request[0] != "timeline"){
			$render_foot = $post->_foot($copyright,$post->page_navi(count($chunk),$page,"/{$get_request[0]}/{$keyword}/"));
		}else{
			$render_foot = $post->_foot($copyright,$post->page_navi(count($chunk),$page,"/{$get_request[0]}/{$keyword}/?page="));
		}
	}
}
?>
