<?php

/* SEDOTPRESS ENGINE
 * Version : 0.1.7
 * Source  : https://github.com/sukualam/sedotpress
 * News    : http://sedot.space
 * License : MIT
 */
 
 
/* DESCRIPTION
 * This is admin area / Backstage for sedotpress
 */
if(!defined("SEDOT_VER")){die('WRONG ACCESS');}

$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;

class backstage extends sedot{

	function __construct(){
		
	}
	function sectionLink($param){
		$url = rtrim(SITE_URL,"/") . "/backstage/{$param}";
		return $url;
	}
	function filterPOST($str = false){
		if(!$str){
			foreach($_POST as $key => $val){
				$filter[$key] = htmlentities($val);
			}
			return @$filter;
		}else{
			return htmlentities($_POST[$str]);
		}
	}
	function nullStr($mixed,$prefix = 'untitled',$numbering = false){
		if(is_array($mixed)){
			foreach($mixed as $key => $val){
				if(is_null($mixed[$key]) || $mixed[$key] == ""){
					if(!$numbering){
						$mixed[$key] = $prefix;
					}else{
						$mixed[$key] = sedot::assignPostId($prefix);
					}
				}
			}
		}else{
			if(is_null($mixed) || $mixed == ''){
				if(!$numbering){
					$mixed = $prefix;
				}else{
					$mixed = sedot::assignPostId($prefix);
				}
			}
		}
		return $mixed;
	}
	function alertMessage($str,$type = 'info'){
		$alert = '<div class="alert alert-%s alert-dismissible" role="alert">
		<button type="button" class="close" data-dismiss="alert" aria-label="Close">
		<span aria-hidden="true">&times;</span></button>
		%s </div>';
		return sprintf($alert,$type,$str);
	}
	function panelBox($str,$type = 'default'){
		$panel = '<div class="panel panel-%s">
		<div class="panel-body">
		%s
		</div>
		</div>';
		return sprintf($panel,$type,$str);
	}
	function filterStr($str){
		$str = strtolower(sedot::plainString($str));
		$cleared = preg_replace('/[^a-zA-Z0-9\s]/u','',$str);
		return str_replace('\s','-',$cleared);
	}
	function setNavi($count = 5,$perpage = 5,$row = 5){
		$dummy = '';
		if($perpage <= 100){
			if($perpage <= 50){
				if($perpage <= 25){
					if($perpage <= 10){
						if($perpage <= 5){
							$perpage = 5;
						}else{
							$perpage = 10;
						}}else{
						$perpage = 25;
					}}else{
					$perpage = 50;
				}}else{
				$perpage = 100;
			}}else{
			$perpage = 100;
		}

		$countTimes = $count * 5;
		$total = ceil(ceil($countTimes) / $perpage);
		
		for($i = 0;$i < $total;$i++){
			$dummy[$i] = $i;
		}
		
		@$split = array_chunk($dummy,$row,true);
		
		if(is_null($split)){
			return false;
		}else{
			return $split;
		}
	}
	function pageNavi($current,$stack,$url){
		$out = '';
		$out .= '<nav>
		<ul class="pagination">';
		
		for($i = 0; $i <= count($stack); $i++){
			if(in_array($current, $stack[$i])){  
				if($i > 0){
					$out .= sprintf('<li>
					<a href="%1$s/page/%2$s" class="page-numbers" href="%2$s">&laquo;</a>
					</li>',$url,$current - 1);
				}
				foreach($stack[$i] as $raw){
					if($raw == $current){
						$out .= sprintf('<li class="active">
						<span class="page-numbers current">%s</span>
						</li>',$raw);
					}else{
						$out .= sprintf('<li>
						<a href="%1$s/page/%2$s" class="page-numbers">%2$s</a>
						</li>',$url,$raw);
					}
				}
				if($i < count($stack)){
					$out .= sprintf('<li>
					<a href="%1$s/page/%2$s" class="page-numbers" href="%2$s">&raquo;</a>
					</li>',$url,$current + 1);
				}
				break 1;
			}
		}
		
		$out .= '</ul></nav>';
		return $out;
	}
}

$admin   = new backstage;
$section = @$getRequest[1];
$baseUrl = sprintf('%s/backstage/%s',SITE_URL,$section);

$admin->createWidget('primary');
$admin->createWidget('secondary');

if($section == 'new' || $section == 'edit'){
	if($section == 'edit'){
		if(isset($getRequest[2]) && $getRequest[2] != '' || ! empty($getRequest[2])){
			if(file_exists(sprintf('sp_post/post%s',$getRequest[2]))){
				$isFileExist = true;
			}else{
				$isFileExist = false;
			}
		}else{
			$isFileExist = false;
		}

		if(! $isFileExist){
			die(sprintf('Invalid Operation - <a href="%s">Back</a>',$admin->sectionLink('')));
		}

		$loadPost = $admin->loadPost($getRequest[2]);
	}
	$form = sprintf('<h2>Edit Post</h2>
	<form action="%s" method="post">
	<div class="form-group">
	<label>Title</label>
	<input class="form-control" type="text" value="%s" name="title"/>
	<label>Tag</label>
	<input class="form-control" type="text" value="%s" name="tag"/>
	<label></label>
	<textarea id="konten" name="entry">%s</textarea>
	<label>Permalink (optional)</label>
	<input class="form-control" type="text" value="%s" name="permalink"/>
	<label>Saving Option</label>
	<div class="radio">
	<label>
	<input type="radio" name="status" id="optionsRadios1" value="publish" checked>Publish
	</label>
	</div>
	<div class="radio">
	<label>
	<input type="radio" name="status" id="optionsRadios2" value="draft">Draft
	</label>
	</div>
	<input type="submit" name="submit" value="OK"/>
	</div>
	</form>',
	$admin->sectionLink(sprintf('%s/%s',$section,@$getRequest[2])),
	@$loadPost['title'],
	@$loadPost['tag'],
	@$loadPost['entry'],
	@$loadPost['permalink']);
	
	$admin->addWidget('primary',$form);
	
	$reqs = $admin->filterPOST();
	
	if(isset($reqs['submit'])){
		
		$data['date'] = date(DateTime::ISO8601);
		$data['title'] = $admin->nullStr($reqs['title'],'untitled-',true);
		$data['permalink'] = $admin->nullStr($reqs['permalink'],$admin->filterStr($data['title']));
		$data['tag'] = $admin->nullStr($reqs['tag'],'uncategorized');
		$data['entry'] = $admin->nullStr($_POST['entry'],'Unfinished Story');
		$data['status'] = $admin->nullStr($reqs['status'],'publish');
		
		if($section == 'new'){
			$postFileName = sprintf('sp_post/%s',$admin->assignPostId());
		}else{
			$postFileName = sprintf('sp_post/post%s',$getRequest[2]);
		}
		
		
		if($admin->toFile($postFileName,$admin->toJson($data),true)){
			
			$admin->create_index();
			$message = $admin->alertMessage('Post Published!','success');
			$admin->addWidget('primary',$message,'before');
			
		}else{
			
			$message = $admin->alertMessage('Something Wrong','danger');
			$admin->addWidget('primary',$message,'before');
			
		}
		
	}
}
elseif($section == 'mypost' || $section == 'draft'){
	
	if($section == 'mypost'){
		$indexPublished = $admin->loadJson('index','post');
		
	}else{
		$indexPublished = $admin->loadJson('index','draft');
	}

	$setNavi = $admin->setNavi(count($indexPublished),5,5);

	
	
	if(isset($getRequest[2]) && isset($getRequest[3])){
		if($getRequest[2] == 'page'){
			$indexKey = $getRequest[3];
		}
		else{
			$indexKey = 0;
		}
	}
	else{
		$indexKey = 0;
	}
	
	$indexList = $admin->index_page($indexPublished[$indexKey]);
	
	foreach($indexList as $meta){
		$tableRows = '<tr>
		<td>%s</td>
		<td>%s</td>
		<td>%s</td>
		<td>%s</td>
		<td>%s</td>
		</tr>';
		
		$editPostUrl = sprintf('%s/%s',$admin->sectionLink('edit'),$meta['id']);
		$removePostUrl = sprintf('%s/%s',$admin->sectionLink('delete'),$meta['id']);
		
		@$formatRows .= sprintf($tableRows,
		$meta['date'],
		$meta['title'],
		round(filesize("sp_post/post{$meta['id']}") / 1024, 2),
		$meta['id'],
		'
		<a title="edit" href="'.$editPostUrl.'"><i class="fa fa-pencil"></i></a>
		<a title="edit" href="'.$removePostUrl.'"><i class="fa fa-remove"></i></a>
		'
		);
	}
	
	$table = '<h2>My Posts</h2>
	<div class="table-responsive">
	<table class="table table-hover">
	<tr class="active">
	<th>Date</th>
	<th>Title</th>
	<th>Size (Kb)</th>
	<th>Post Id</th>
	<th>Action</th>
	</tr>
	%s
	</table>
	</div>';
	
	$sidebar = '<h2>Options</h2>
	Sorting Order';
	$formatTable = sprintf($table,$formatRows);
	
	$admin->addWidget('primary',$formatTable);
	$admin->addWidget('secondary',$sidebar);
	
	if($setNavi){
		$admin->addWidget('primary',$admin->pageNavi($indexKey,$setNavi,$baseUrl));
	}
}
elseif($section == 'delete' && isset($getRequest[2])){
	/*
	 * Only for valid filename
	 */
	if(! file_exists(sprintf('sp_post/post%s',$getRequest[2]))){
		die();
	}
	
	/*
	 * Delete confirmation dialog
	 */
	 
	$form = '<h2>Delete Post</h2>
	<form action="" method="post">
	<div class="radio">
	<label>
	<input type="radio" name="del" id="optionsRadios1" value="yes" checked>Yes
	</label>
	</div>
	<div class="radio">
	<label>
	<input type="radio" name="del" id="optionsRadios2" value="no">No
	</label>
	</div>
	<input type="submit" name="confirm" value="OK"/>
	</form>';
	
	$msgError = $admin->alertMessage('Not Deleted','danger');
	$msgSuccess = $admin->alertMessage('Post Deleted','success');
	
	/*
	 * Handling the message
	 */
	 
	if(isset($_POST['del'])){
		if($_POST['del'] == 'yes'){
			if($admin->removeExistingFile(sprintf('sp_post/post%s',$getRequest[2]))){
				$admin->addWidget('primary',$msgSuccess);
				$admin->create_index();
			}
			else{
				$admin->addWidget('primary',$msgError);
			}
		}
		else{
			$admin->addWidget('primary',$msgError);
		}
	}
	else{
		$admin->addWidget('primary',$form);
	}
}
elseif($section == 'widget'){
	
	/*
	 * Load widget areas on templates
	 */
	$widgetConf = parse_ini_file('sp_theme/'.THEME.'/config.ini');
	$widgetFile = '_widget-'.THEME;
	$widgetPath = 'sp_static/misc/'.$widgetFile.'.json';
	/*
	 * Create default widget config in sp_json/ that store the widgets
	 * Filename format: widget-{theme_name}.json
	 * Still need to implemented
	 */
	if(! file_exists($widgetPath)){
		foreach($widgetConf['area'] as $key => $widget){
			$widgetSection[$widget] = '';
		}
		$widgetSet = $admin->toJson($widgetSection);
		
		if($admin->toFile($widgetPath,$widgetSet)){
			$msg = 'Default Config Created';
			$admin->addWidget('primary',$admin->alertMessage($msg,'success'));
		}
	}
	
	$loadWidget = $admin->loadJson('misc',$widgetFile);
	
	if(isset($getRequest[2])){
		/*
		 * $getRequest[2] means widget area name, example: sidebar1
		 * This handle following action: edit,remove,new
		 */
		if(@$_POST['widgetAction'] == 'edithtml'){
			$wArea = $_POST['widgetArea'];
			$wItemId = $_POST['widgetItemId'];
			$loadWidget[$wArea][$wItemId]['title'] = $_POST['widgetTitle'];
			$loadWidget[$wArea][$wItemId]['code'] = $_POST['htmlCode'];
			$rePack = $admin->toJson($loadWidget);	
			$admin->toFile($widgetPath,$rePack,true);	
		}
		if(isset($_POST['addWidget'])){
			if($_POST['addWidget'] == 'html'){
				$code = $_POST['htmlCode'];
			}else{
				$code = sprintf('#%1$s#',strtoupper($_POST['addWidget']));
			}
			$key = $admin->endKey($loadWidget[$getRequest[2]],true);	
			$loadWidget[$getRequest[2]][$key] = array(
			'type' => $_POST['addWidget'],
			'title' => $_POST['widgetTitle'],
			'code' => $code);
			$rePack = $admin->toJson($loadWidget);	
			$admin->toFile($widgetPath,$rePack,true);
		}
	}
	
	if(isset($getRequest[3]) && $getRequest[3] == 'add' && ! isset($_POST['widgetType'])){
		# This will show the widget type for new widget action
		$form = sprintf('<form action="%1$s" method="post">
		<label>(%2$s) New Widget:</label>
		<div class="radio">
		<label>
		<input type="radio" name="widgetType" id="optionsRadios1" value="html" checked>
		Custom HTML/Javascript Code
		</label>
		</div>
		<div class="radio">
		<label>
		<input type="radio" name="widgetType" id="optionsRadios3" value="own">
		<span style="color:green">Own Function</span>
		</label>
		</div>
		<input type="submit" name="submit" value="OK"/>
		<a href="%3$s">Cancel</a>
		</form>',
		$admin->sectionLink(sprintf('widget/%1$s/add',$getRequest[2])),
		$getRequest[2],
		$admin->sectionLink('widget'));
		$admin->addWidget('primary',$admin->panelBox($form),'before');
	}
	
	if(isset($getRequest[3]) && $getRequest[3] == 'add' && isset($_POST['widgetType'])){
		#This handle the widget type
		if($_POST['widgetType'] == 'html'){
			$htmlForm = sprintf('
			<h3>New Widget</h3>
			<form action="%2$s" method="post">
			<input type="hidden" name="addWidget" value="html"/>
			<div class="form-group">
			<label>Widget Title</label>
			<input class="form-control" type="text" name="widgetTitle"/>
			<label>HTML Code</label>
			<textarea  class="form-control" name="htmlCode"></textarea><br>
			<input class="btn btn-success" type="submit" value="save"/>
			<a href="%1$s">Cancel</a>
			</div>
			</form>',
			$_POST['widgetId'],
			$admin->sectionLink(sprintf('widget/%s',$getRequest[2])));
			$admin->addWidget('primary',$admin->panelBox($htmlForm),'before');
		}
		elseif($_POST['widgetType'] == 'own'){
			$getWidgets = $admin->currentMethod('widget');
			foreach($getWidgets as $widgetName){
				$widgetNameList[] = '<option value="'.$widgetName.'">'.$widgetName.'</option>';
			}
			$htmlForm = sprintf('
			<h3>New Widget</h3>
			<form action="%2$s" method="post">
			<div class="form-group">
			<label>Widget Function Name</label>
			<select class="form-control" name="addWidget">
			%3$s
			</select>
			<label>Widget Title</label>
			<input class="form-control" type="text" name="widgetTitle"/>
			<label>HTML Code</label>
			<textarea  class="form-control" name="htmlCode"></textarea><br>
			<input class="btn btn-success" type="submit" value="save"/>
			<a href="%1$s">Cancel</a>
			</div>
			</form>',
			$_POST['widgetId'],
			$admin->sectionLink(sprintf('widget/%s',$getRequest[2])),
			implode(' ',$widgetNameList)
			);
			$admin->addWidget('primary',$admin->panelBox($htmlForm),'before');
		}
		else{
			$htmlForm = sprintf('
			<h3>New Widget</h3>
			<form action="%2$s" method="post">
			<input type="hidden" name="addWidget" value="%3$s"/>
			<div class="form-group">
			<label>Widget Title</label>
			<input class="form-control" type="text" name="widgetTitle"/>
			<input class="btn btn-success" type="submit" value="save"/>
			<a href="%1$s">Cancel</a>
			</div>
			</form>',
			$_POST['widgetId'],
			$admin->sectionLink(sprintf('widget/%s',$getRequest[2])),
			$_POST['widgetType']);
			$admin->addWidget('primary',$admin->panelBox($htmlForm),'before');
		}
	}
	
	/*
	 * Move up the widget item
	 */
	 
	if(isset($getRequest[3]) && $getRequest[3] == 'up' && isset($getRequest[4])){
		$loadWidget[$getRequest[2]] = $admin->up($loadWidget[$getRequest[2]],$getRequest[4]);
		$loadWidget[$getRequest[2]] = array_values($loadWidget[$getRequest[2]]);
		$rePack = $admin->toJson($loadWidget);
		$admin->toFile($widgetPath,$rePack,true);
	}
	
	/*
	 * Move down the widget item
	 */
	 
	if(isset($getRequest[3]) && $getRequest[3] == 'down' && isset($getRequest[4])){
		$loadWidget[$getRequest[2]] = $admin->down($loadWidget[$getRequest[2]],$getRequest[4]);
		$loadWidget[$getRequest[2]] = array_values($loadWidget[$getRequest[2]]);
		$rePack = $admin->toJson($loadWidget);
		$admin->toFile($widgetPath,$rePack,true);
	}
	
	/*
	 * Delete widget item
	 */
	 
	if(isset($getRequest[3]) && $getRequest[3] == 'delete' && isset($getRequest[4])){
		unset($loadWidget[$getRequest[2]][$getRequest[4]]);
		$loadWidget[$getRequest[2]] = array_values($loadWidget[$getRequest[2]]);
		$rePack = $admin->toJson($loadWidget);
		$admin->toFile($widgetPath,$rePack,true);
	}
	
	if(isset($getRequest[3]) && $getRequest[3] == 'edit' && isset($getRequest[4])){
		/*
		 * Edit the existing widget item in current widget area
		 * $getRequest[4] means widget item id
		 */
		 
		$htmlForm = sprintf('
		<h3>Edit Widget</h3>
		<form action="%1$s" method="post">
		<h4>Widget Type: %6$s</h4>
		<input type="hidden" name="widgetAction" value="edithtml"/>
		<input type="hidden" name="widgetArea" value="%4$s"/>
		<input type="hidden" name="widgetItemId" value="%5$s"/>
		<div class="form-group">
		<label>Widget Title</label>
		<input class="form-control" type="text" value="%2$s" name="widgetTitle"/>
		<label>HTML Code</label>
		<textarea  class="form-control" name="htmlCode">%3$s</textarea><br>
		<input class="btn btn-success" type="submit" value="save"/>
		<a href="%1$s">Cancel</a>
		</div>
		</form>',
		$admin->sectionLink(sprintf('widget/%s',$getRequest[2])),
		$loadWidget[$getRequest[2]][$getRequest[4]]['title'],
		$loadWidget[$getRequest[2]][$getRequest[4]]['code'],
		$getRequest[2],
		$getRequest[4],
		$loadWidget[$getRequest[2]][$getRequest[4]]['type']
		);
		
		$admin->addWidget('primary',$admin->panelBox($htmlForm),'before');
		
	}
	
	/*
	 * Create a simple widget management
	 */
	
	foreach($widgetConf['area'] as $area){
		$widgetList = '';
		foreach($loadWidget[$area] as $keys => $widgets){
			$widgetList .= sprintf('
			<li>
			<a href="%1$s"><b>%5$s</b></a>&nbsp;
			<a href="%2$s"><i class="fa fa-arrow-up"></i></a>
			<a href="%3$s"><i class="fa fa-arrow-down"></i></a>
			<a href="%4$s"><i class="fa fa-times"></i></a>
			</li>',
			$admin->sectionLink('widget') . '/' . $area . '/edit/' . $keys,
			$admin->sectionLink('widget') . '/' . $area . '/up/' . $keys,
			$admin->sectionLink('widget') . '/' . $area . '/down/' . $keys,
			$admin->sectionLink('widget') . '/' . $area . '/delete/' . $keys,
			$widgets['title'] == '' ? '(notitle)' : $widgets['title']);
		}
		$box = sprintf('
		<h3>%s <a href="%s"><i class="fa fa-plus"></i></a></h3>
		<ul style="list-style-type:none">%s</ul>
		',
		$area,
		$admin->sectionLink('widget') . '/' . $area . '/add',
		$widgetList
		);
		
		$admin->addWidget('primary',$box);
		
		unset($widgetList);
	}
}
elseif($section == 'option'){
	$filename = 'config.json';
}
elseif($section == 'import'){
	$dialogBox = '
	Import from Blogger Backup (xml)
	<form action="" method="post">
	<input type="submit" name="act" value="ok"/>
	</form>';
	
	if(isset($_POST['act']) && $_POST['act'] == 'ok'){
		$filename = 'blog.xml';
		$parse = simplexml_load_file($filename);
		$encode = json_encode($parse);
		$decode = json_decode($encode,true);
		$reverse = array_reverse($decode['entry'],true);
		$term = 'http://schemas.google.com/blogger/2008/kind#post';
		foreach($reverse as $id => $content){
			if($content['category'][0]['@attributes']['term'] == $term){
				array_shift($content['category']);
				foreach($content['category'] as $keys => $tag){
					$tags[] = $content['category'][$keys]['@attributes']['term'];
				}
				$link = $content['link'][4]['@attributes']['href'];
				$link = rtrim($link,'{.html}');
				$permalink = explode('/',$link);end($permalink);
				$permalink = $permalink[key($permalink)];
				$data['date'] = date(DateTime::ISO8601);
				$data['title'] = $content['title'];
				$data['permalink'] = $permalink;
				$data['tag'] = implode(',',$tags);
				$data['entry'] = $content['content'];
				$data['status'] = 'publish';
				$postFileName = sprintf('sp_post/%s',$admin->assignPostId());
				$admin->toFile($postFileName,$admin->toJson($data),true);
				unset($tags);
			}
		}
		$admin->create_index();
	}
	$admin->addWidget('primary',$dialogBox);
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title><?php echo SITE_TITLE; ?> [Backstage]</title>
    <!-- Bootstrap -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.4/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.3.0/css/font-awesome.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.6.2/summernote.min.css" rel="stylesheet">
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
  <nav class="navbar navbar-inverse">
        <div class="container">
          <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
              <span class="sr-only">Toggle navigation</span>
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="<?php echo $admin->_url(); ?>"><?php echo SITE_TITLE; ?></a>
          </div>
          <div id="navbar" class="navbar-collapse collapse">
            <ul class="nav navbar-nav">
				<li><a href="<?php echo $admin->sectionLink('new'); ?>"><i class="fa fa-pencil"></i> New Post</a></li>
				<li><a href="<?php echo $admin->sectionLink('mypost'); ?>"><i class="fa fa-database"></i> My Posts</a></li>
				<li><a href="<?php echo $admin->sectionLink('draft'); ?>"><i class="fa fa-save"></i> Saved Drafts</a></li>
				<li><a href="<?php echo $admin->sectionLink('widget'); ?>"><i class="fa fa-sliders"></i> Widget</a></li>
				<li><a href="<?php echo $admin->sectionLink('import'); ?>"><i class="fa fa-sliders"></i> Import</a></li>
				<li><a href="<?php echo $admin->sectionLink('option'); ?>"><i class="fa fa-sliders"></i> Setting</a></li>
				<li><a href="<?php echo $admin->sectionLink('logout'); ?>"><i class="fa fa-sign-out"></i> Logout</a></li>
            </ul>
          </div><!--/.nav-collapse -->
        </div><!--/.container-fluid -->
   </nav>
  <header class="page-header">
  <div class="container">
   <h1><?php echo SITE_TITLE; ?> <small><?php echo SITE_DESC; ?></small></h1>
  </div>
  </header>
  <div class="container">
  </div>
  <div class="container">
   <div class="row">
   	<div class="col-md-12">
	<?php $admin->widgetSection('primary'); ?>
	</div>
   </div>  
  </div>
  <footer>
  <div class="container">
	<?php
	$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$finish = $time;
	$total_time = round(($finish - $start), 3);
	echo '<small>Powered by Sedotpress - Page generated in '.$total_time.' seconds.</small>';
	?>
  </div>
  </footer>
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.4/js/bootstrap.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.6.2/summernote.min.js"></script>
	<script>
		$(document).ready(function() {$('#konten').summernote({
		height: 300, // set editor height
		minHeight: null, // set minimum height of editor
		maxHeight: null, // set maximum height of editor
		focus: true, });});
	</script>
  </body>
</html>
