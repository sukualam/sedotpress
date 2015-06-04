<?php

/* SEDOTPRESS ENGINE
 * Version : 0.1.6
 * Source  : https://github.com/sukualam/sedotpress
 * News    : http://sedot.space
 * License : MIT
 */

 
/* DESCRIPTION
 * This script allows you to write own widget function
 * Then you can add your own widget from backstage by specify widget function name
 */
class widget extends sedot{
	function taglist(){
		$x = sedot::loadJson('index','tag');
		foreach($x as $name => $count){
			$link = trim(SITE_URL,'/').'/tag/'.$name;
			$tags[] = '<li><a href="'.$link.'">'.$name.'</a></li>';
		}
		$container = '<ul>%1$s</ul>';
		return sprintf($container,implode('',$tags));
	}
	function archives(){
		$archive = sedot::loadJson('index','archive');
		foreach($archive as $year => $x){
			foreach($x as $month => $y){
				$link = trim(SITE_URL,'/').'/timeline/'.$year.'/'.$month;
				$list[] = '<li><a href="'.$link.'">'.date('F Y',strtotime($year.'-'.$month)).'</a></li>';
			}
		}
		$container = '<ul>%1$s</ul>';
		return sprintf($container,implode('',$list));
	}
	function pageNavi(){
		$url = ACTUAL_URL;
		$current = $this->page;
		$stack = $this->total;
		for($i = 1;$i <= $stack;$i++){$stack_temp[$i] = $i;}
		$stack = array_chunk($stack_temp,5,true);
		$out[] = '<nav><ul class="pagination">';
		for($i = 0; $i <= count($stack); $i++){
			if(in_array($current, $stack[$i])){  
				if($i > 0){
					$out[] = '<li><a href="'.$url.'/page/'.($current - 1).'" class="page-numbers">&laquo;</a></li>';
				}
				foreach($stack[$i] as $raw){
					if($raw == $current){
						$out[] = '<li class="active"><span class="page-numbers current">'.$raw.'</span></li>';
					}else{
						$out[] = '<li><a href="'.$url.'/page/'.$raw.'" class="page-numbers">'.$raw.'</a></li>';
					}
				}
				if($current < $this->total){
					$out[] = '<li><a href="'.$url.'/page/'.($current + 1).'" class="page-numbers">&raquo;</a></li>';
				}
				break 1;
			}
		}
		$out[] = '</ul></nav>';
		return implode('',$out);
	}
}
?>