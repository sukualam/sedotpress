<?php

/* SEDOTPRESS ENGINE
 * Version : 0.2.0
 * Source  : https://github.com/sukualam/sedotpress
 * News    : http://sedot.space
 * License : MIT
 */

/* DESCRIPTION
 * This script allows you to write own widget function
 * Then you can add your own widget from backstage by specify widget function name
 */
class widget extends sedot
{
	public static function taglist()
	{
		$x       = sedot::loadJson('index', 'tag');
		$tagList = '';
		foreach ($x as $name => $count) {
			$link    = sedot::_url('/tag/' . rawurlencode((string)$name));
			$tagList .= sprintf(
				'<li class="mb-1"><a href="%s">%s</a> <span class="badge text-bg-secondary">%d</span></li>',
				sedot::e($link),
				sedot::e((string)$name),
				(int)$count
			);
		}
		if ($tagList === '') {
			return '<p class="text-muted mb-0">No tags yet.</p>';
		}
		return '<ul class="list-unstyled mb-0">' . $tagList . '</ul>';
	}

	public static function archives()
	{
		$archive = sedot::loadJson('index', 'archive');
		$list    = '';
		foreach ($archive as $year => $months) {
			if (!is_array($months)) {
				continue;
			}
			foreach ($months as $month => $days) {
				$link = sedot::_url('/timeline/' . (int)$year . '/' . (int)$month);
				$label = date('F Y', strtotime((int)$year . '-' . (int)$month . '-01'));
				$count = is_array($days) ? array_sum($days) : (int)$days;
				$list .= sprintf(
					'<li class="mb-1"><a href="%s">%s</a> <span class="badge text-bg-secondary">%d</span></li>',
					sedot::e($link),
					sedot::e($label),
					$count
				);
			}
		}
		if ($list === '') {
			return '<p class="text-muted mb-0">No archives yet.</p>';
		}
		return '<ul class="list-unstyled mb-0">' . $list . '</ul>';
	}

	public static function pageNavi()
	{
		$url     = ACTUAL_URL;
		$current = self::$navPage;
		$total   = self::$navTotal;
		if ($total < 2) {
			return '';
		}
		$stack = array_chunk(range(1, $total), 5, true);
		$out   = '<nav><ul class="pagination mb-0">';
		foreach ($stack as $i => $group) {
			if (!in_array($current, $group, true)) {
				continue;
			}
			if ($i > 0) {
				$out .= sprintf('<li class="page-item"><a class="page-link" href="%s/page/%d">&laquo;</a></li>', sedot::e($url), $current - 1);
			}
			foreach ($group as $raw) {
				if ($raw === $current) {
					$out .= sprintf('<li class="page-item active"><span class="page-link">%d</span></li>', $raw);
				} else {
					$out .= sprintf('<li class="page-item"><a class="page-link" href="%s/page/%d">%d</a></li>', sedot::e($url), $raw, $raw);
				}
			}
			if ($i < count($stack) - 1) {
				$out .= sprintf('<li class="page-item"><a class="page-link" href="%s/page/%d">&raquo;</a></li>', sedot::e($url), $current + 1);
			}
			break;
		}
		return $out . '</ul></nav>';
	}

	public static function searchbox()
	{
		return sprintf(
			'<form action="%s" method="get" class="mb-3" role="search">
			<label class="form-label" for="widget-search">Search</label>
			<div class="input-group">
			<input class="form-control" id="widget-search" type="search" name="q" placeholder="Search...">
			<button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
			</div>
			</form>',
			sedot::e(sedot::_url('search'))
		);
	}

	public static function recentposts()
	{
		$index = sedot::loadJson('index', 'index-0');
		$list  = '';
		foreach ($index as $id => $meta) {
			if (!is_array($meta)) {
				continue;
			}
			$list .= sprintf(
				'<li class="mb-1"><a href="%s">%s</a></li>',
				sedot::e(sedot::_url((string)($meta[2] ?? ''))),
				sedot::e((string)($meta[1] ?? ''))
			);
		}
		if ($list === '') {
			return '<p class="text-muted mb-0">No posts yet.</p>';
		}
		return '<ul class="list-unstyled mb-0">' . $list . '</ul>';
	}
}
