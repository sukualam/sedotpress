<?php
if(!defined("SEDOT_VER")){die();}


/* load user widget */
$post->enableWidget();

/* override widget that not declared ini config.ini */
$post->createWidget('head');
$post->createWidget('header');

if($_PAGE_404){
	$message = '<div class="col-md-12">
				<h1>404 NOT FOUND</h1>
				</div>';	
	$post->addWidget('index-top',$message);
}
if($_FRONT_PAGE){
	if($getRequest[0] == "page"){
		$title = '<title>'.SITE_TITLE.' | page '. $getRequest[1] . '</title>';
	}elseif($_SEARCH_MODE){
		$_page = isset($getRequest[3]) ? ' | page '.$getRequest[3] : '';
		$title = '<title>'.SITE_TITLE.' - '. $getRequest[1] . $_page . '</title>';
	}else{
		$title = '<title>'.SITE_TITLE.' - '.SITE_DESC.'</title>';
	}
	$metaDescription = '<meta name="description" content="'.SITE_TITLE.' - '.SITE_DESC.'">';	
	
	foreach($entries as $id => $entry){
		foreach($entry['tag'] as $label){	
			$tags[] = '<a class="label label-success" href="'.$post->_url('/tag/'.$label).'">'.$label.'</a>';	
		}
		$format[] = 	'<div class="col-md-12">
						 <article>
							<h2><a title="'.$entry['title'].'" href="'.$entry['url'].'">'.$entry['title'].'</a></h2>
							<div class="tags"><time class="label label-info">'.date('d-M-Y h:m', strtotime($entry['date'])).'</time>
							'.implode(' ',$tags).'</div>&nbsp;
							<div class="">
							<p><a href="#" class="thumbnail">
							<img alt="'.$entry['title'].'" style="max-width:200px;max-height:200px" title="'.$entry['title'].'" src="'.$entry['image'].'">
							</a>'.$post->getExcerpt($entry['entry'],250).'</p></div>			
						 </article>
						 </div>';
		unset($tags);
	}
	$post->addWidget('index-top',implode('',$format));
	$post->addWidget('head',$title);
	$post->addWidget('head',$metaDescription);
}
if($_SINGLE_POST){
	foreach($entry['tag'] as $label){	
			$tags[] = '<a class="label label-success" href="'.$post->_url('/tag/'.$label).'">'.$label.'</a>';	
	}
	$title = '<title>'.$entry['title'].' - '.SITE_TITLE.'</title>';
	$metaDescription = '<meta name="description" content="'.$post->getExcerpt($entry['entry'],160).'">';	
	$format = 	'<div class="col-md-12">
					<article>
					<h2>'.$entry['title'].'</h2>
					<span>'.implode(' ',$tags).'</span><br>
					'.$entry['entry'].'
					</article>
				 </div>';
	$post->addWidget('head',$title);
	$post->addWidget('head',$metaDescription);
	$post->addWidget('post-top',$format);
}

$header = sprintf('
	<h1>%s <small>%s</small></h1>',
	sprintf('<a href="%1$s">%2$s</a>',SITE_URL,SITE_TITLE),SITE_DESC);

$post->addWidget('header',$header);
$generator = '<meta name="generator" content="sedotpress-'.SEDOT_VER.'">';
$post->addWidget('head',$generator);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->  
    <?php $post->widgetSection('head'); ?>
    <!-- Bootstrap -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css" rel="stylesheet">
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
  <?php $post->widgetSection('top'); ?>
  <header class="site-header">
  <div class="container">
  <?php $post->widgetSection('header'); ?>
  </div>
  </header>
  <div class="container">
   <div class="row">
    <div class="col-md-6">
		<div class="row">
		<?php
		if($_FRONT_PAGE){
			$post->widgetSection('index-top');
			$post->widgetSection('index-bottom');
		}else{
			$post->widgetSection('post-top');
			$post->widgetSection('post-bottom');
		}
		?>
		</div>
	</div>
	<div class="col-md-3">
		<div class="row">
		<?php $post->widgetSection('sidebar1'); ?>
		</div>
	</div>
	<div class="col-md-3">
		<div class="row">
		<?php $post->widgetSection('sidebar2'); ?>
		</div>
	</div>
   </div>  
  </div>
  <footer>
  <div class="container">
	  <div class="row">
		<?php $post->widgetSection('footer'); ?>
	  </div>
	  Powered by <a href="http://sedot.space">SEDOTPRESS</a>
  </div>
  </footer>
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.4/js/bootstrap.min.js"></script>
  </body>
</html>
