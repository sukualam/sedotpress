<?php

/* SEDOTPRESS ENGINE
 * Version : 0.2.0
 * Source  : https://github.com/sukualam/sedotpress
 * News    : http://sedot.space
 * License : MIT
 */

/* DESCRIPTION
 * This is admin area / Backstage for sedotpress
 */
if (!defined('SEDOT_VER')) {
	die('WRONG ACCESS');
}

$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;

class backstage extends sedot
{
	function sectionLink($param)
	{
		return rtrim(SITE_URL, '/') . '/backstage/' . ltrim((string)$param, '/');
	}

	function input($key, $default = '')
	{
		$value = $_POST[$key] ?? $default;
		return is_string($value) ? trim($value) : $default;
	}

	function nullStr($mixed, $prefix = 'untitled', $numbering = false)
	{
		if (is_array($mixed)) {
			foreach ($mixed as $key => $val) {
				if ($val === null || $val === '') {
					$mixed[$key] = !$numbering ? $prefix : sedot::assignPostId($prefix);
				}
			}
			return $mixed;
		}
		if ($mixed === null || $mixed === '') {
			return !$numbering ? $prefix : sedot::assignPostId($prefix);
		}
		return $mixed;
	}

	function alertMessage($str, $type = 'info')
	{
		$alert = '<div class="alert alert-%s alert-dismissible fade show" role="alert">%s'
			. '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
		return sprintf($alert, $type, $str);
	}

	function panelBox($str, $type = 'default')
	{
		$card = '<div class="card mb-3 border-%s"><div class="card-body">%s</div></div>';
		return sprintf($card, $type, $str);
	}

	function filterStr($str)
	{
		$str = sp_strtolower(sedot::plainString((string)$str));
		$str = preg_replace('/[^a-z0-9\s]/u', '', (string)$str);
		$str = preg_replace('/\s+/', '-', trim((string)$str));
		return substr(trim((string)$str, '-'), 0, 100);
	}

	function isPermalinkTaken($permalink, $excludeId = '')
	{
		foreach (['post', 'draft'] as $type) {
			$index = sedot::loadJson('index', $type);
			foreach ($index as $chunk) {
				if (!is_array($chunk)) {
					continue;
				}
				foreach ($chunk as $id => $meta) {
					if ((string)$id === (string)$excludeId) {
						continue;
					}
					if (is_array($meta) && ($meta[2] ?? '') === $permalink) {
						return true;
					}
				}
			}
		}
		return false;
	}

	function setNavi($count = 5, $perpage = 5, $row = 5)
	{
		$buckets = [5, 10, 25, 50, 100];
		$perpage = in_array((int)$perpage, $buckets, true) ? (int)$perpage : 5;
		$total   = (int)ceil(((int)$count * POSTS_PER_PAGE) / $perpage);
		if ($total < 1) {
			return null;
		}
		$split = array_chunk(range(0, $total - 1), max(1, (int)$row), true);
		return $split ?: null;
	}

	function pageNavi($current, $stack, $url)
	{
		if (!is_array($stack) || !$stack) {
			return '';
		}
		$current = (int)$current;
		$out     = '<nav><ul class="pagination mb-0">';
		foreach ($stack as $i => $group) {
			if (!in_array($current, $group, true)) {
				continue;
			}
			if ($i > 0) {
				$out .= sprintf('<li class="page-item"><a class="page-link" href="%s/page/%d">&laquo;</a></li>', $url, $current - 1);
			}
			foreach ($group as $raw) {
				$raw = (int)$raw;
				if ($raw === $current) {
					$out .= sprintf('<li class="page-item active"><span class="page-link">%d</span></li>', $raw);
				} else {
					$out .= sprintf('<li class="page-item"><a class="page-link" href="%s/page/%d">%d</a></li>', $url, $raw, $raw);
				}
			}
			if ($i < count($stack) - 1) {
				$out .= sprintf('<li class="page-item"><a class="page-link" href="%s/page/%d">&raquo;</a></li>', $url, $current + 1);
			}
			break;
		}
		return $out . '</ul></nav>';
	}
}

$admin     = new backstage;
$section   = $getRequest[1] ?? '';
$baseUrl   = sprintf('%s/backstage/%s', SITE_URL, $section);
$csrfField = sp_csrf_field();
$adminFlash = $_SESSION['sp_admin_flash'] ?? null;
unset($_SESSION['sp_admin_flash']);

$admin->createWidget('primary');
$admin->createWidget('secondary');

function admin_flash($type, $text)
{
	$_SESSION['sp_admin_flash'] = ['type' => $type, 'text' => $text];
}

if ($section === 'new' || $section === 'edit') {
	$loadPost = null;
	$editId   = '';
	if ($section === 'edit') {
		$editId   = sedot::sanitizePostId($getRequest[2] ?? '');
		$loadPost = $editId !== '' ? sedot::loadPost($editId) : null;
		if ($loadPost === null) {
			die(sprintf('Invalid Operation - <a href="%s">Back</a>', $admin->sectionLink('')));
		}
	}
	$isPost    = $_SERVER['REQUEST_METHOD'] === 'POST';
	$formTitle = $isPost ? $admin->input('title') : (string)($loadPost['title'] ?? '');
	$formTag   = $isPost ? $admin->input('tag') : (string)($loadPost['tag'] ?? '');
	$formSlug  = $isPost ? $admin->input('permalink') : (string)($loadPost['permalink'] ?? '');
	$formEntry = $isPost ? (string)($_POST['entry'] ?? '') : (string)($loadPost['entry'] ?? '');
	$formStatus = $isPost ? $admin->input('status', 'publish') : (string)($loadPost['status'] ?? 'publish');
	if (!in_array($formStatus, ['publish', 'draft'], true)) {
		$formStatus = 'publish';
	}

	$form = sprintf('<h2>%s</h2>
	<form id="postForm" action="%s" method="post">
	%s
	<div class="mb-3">
	<label class="form-label" for="f-title">Title</label>
	<input class="form-control" id="f-title" type="text" value="%s" name="title"/>
	</div>
	<div class="mb-3">
	<label class="form-label" for="f-tag">Tag (comma separated)</label>
	<input class="form-control" id="f-tag" type="text" value="%s" name="tag"/>
	</div>
	<div class="mb-3">
	<label class="form-label">Content</label>
	<div id="editor" class="border rounded"></div>
	<textarea id="konten" name="entry" class="d-none">%s</textarea>
	</div>
	<div class="mb-3">
	<label class="form-label" for="f-slug">Permalink (optional, auto from title)</label>
	<input class="form-control" id="f-slug" type="text" value="%s" name="permalink"/>
	</div>
	<div class="mb-3">
	<label class="form-label d-block">Saving Option</label>
	<div class="form-check form-check-inline">
	<input class="form-check-input" type="radio" name="status" id="optPublish" value="publish"%s>
	<label class="form-check-label" for="optPublish">Publish</label>
	</div>
	<div class="form-check form-check-inline">
	<input class="form-check-input" type="radio" name="status" id="optDraft" value="draft"%s>
	<label class="form-check-label" for="optDraft">Draft</label>
	</div>
	</div>
	<button class="btn btn-primary" type="submit" name="submit" value="ok">Save</button>
	</form>',
		$section === 'edit' ? 'Edit Post' : 'New Post',
		$admin->sectionLink($section === 'edit' ? 'edit/' . $editId : 'new'),
		$csrfField,
		sedot::e($formTitle),
		sedot::e($formTag),
		sedot::e($formEntry),
		sedot::e($formSlug),
		$formStatus === 'publish' ? ' checked' : '',
		$formStatus === 'draft' ? ' checked' : ''
	);
	$admin->addWidget('primary', $form);

	if ($isPost && isset($_POST['submit'])) {
		if (!sedot::checkCsrf($_POST['sp_csrf'] ?? '')) {
			$admin->addWidget('primary', $admin->alertMessage('Security token invalid, please try again.', 'danger'), 'before');
		} else {
			$title  = $admin->input('title');
			$tag    = $admin->input('tag');
			$slug   = $admin->input('permalink');
			$status = $admin->input('status', 'publish') === 'draft' ? 'draft' : 'publish';
			$entry  = (string)($_POST['entry'] ?? '');
			if (trim(sedot::plainString($entry)) === '') {
				$entry = '';
			}
			$uniquePost = $admin->nullStr($title, 'untitled-', true);
			$permalink  = $admin->nullStr($slug, $admin->filterStr($uniquePost));

			if (in_array($permalink, RESERVED_SLUGS, true)) {
				$admin->addWidget('primary', $admin->alertMessage('That permalink is reserved, please choose another one.', 'danger'), 'before');
			} elseif ($admin->isPermalinkTaken($permalink, $editId)) {
				$admin->addWidget('primary', $admin->alertMessage('That permalink is already used by another post.', 'danger'), 'before');
			} else {
				$data = [
					'date'      => $loadPost['date'] ?? date('c'),
					'title'     => $uniquePost,
					'permalink' => $permalink,
					'tag'       => $admin->nullStr($tag, 'uncategorized'),
					'entry'     => $entry !== '' ? $entry : 'Unfinished Story',
					'status'    => $status,
				];
				if ($section === 'new') {
					$newName = $admin->assignPostId();
					$newId   = substr($newName, strlen('post'));
				} else {
					$newId = $editId;
				}
				if ($admin->toFile(sp_path('sp_post/post' . $newId), $admin->toJson($data), true)) {
					sedot::create_index();
					admin_flash('success', $status === 'publish' ? 'Post published!' : 'Draft saved.');
					header('Location: ' . $admin->sectionLink('edit/' . $newId));
					exit;
				}
				$admin->addWidget('primary', $admin->alertMessage('Something went wrong, the post could not be saved.', 'danger'), 'before');
			}
		}
	}
} elseif ($section === 'comments') {
	$action  = $getRequest[2] ?? '';
	$actId   = $getRequest[3] ?? '';
	$actIdx  = $getRequest[4] ?? '';
	if (in_array($action, ['approve', 'delete', 'spam'], true)) {
		if (!sedot::checkCsrf($_GET['token'] ?? '')) {
			admin_flash('danger', 'Security token invalid.');
		} elseif (sedot::moderateComment($actId, (int)$actIdx, $action)) {
			if ($action === 'delete') {
				sedot::create_index();
			}
			admin_flash('success', 'Comment ' . $action . 'd.');
		} else {
			admin_flash('danger', 'Comment not found.');
		}
		header('Location: ' . $admin->sectionLink('comments'));
		exit;
	}

	$filter = (string)($_GET['status'] ?? 'all');
	if (!in_array($filter, ['all', 'pending', 'approved', 'spam'], true)) {
		$filter = 'all';
	}
	$rows   = '';
	$counts = ['pending' => 0, 'approved' => 0, 'spam' => 0];
	foreach (sedot::allComments() as $comment) {
		$status = (string)($comment['status'] ?? 'pending');
		if (isset($counts[$status])) {
			$counts[$status] += 1;
		}
		if ($filter !== 'all' && $status !== $filter) {
			continue;
		}
		$postData  = sedot::loadPost($comment['post_id']);
		$postTitle = $postData['title'] ?? ('#' . $comment['post_id']);
		$postSlug  = $postData['permalink'] ?? '';
		$actions   = '';
		if ($status !== 'approved') {
			$actions .= sprintf(
				'<a class="btn btn-sm btn-success" title="approve" href="%s"><i class="fa-solid fa-check"></i></a> ',
				$admin->sectionLink(sprintf('comments/approve/%s/%d?token=%s', $comment['post_id'], $comment['index'], urlencode(sedot::csrfToken())))
			);
		}
		if ($status !== 'spam') {
			$actions .= sprintf(
				'<a class="btn btn-sm btn-warning" title="mark as spam" href="%s"><i class="fa-solid fa-ban"></i></a> ',
				$admin->sectionLink(sprintf('comments/spam/%s/%d?token=%s', $comment['post_id'], $comment['index'], urlencode(sedot::csrfToken())))
			);
		}
		$actions .= sprintf(
			'<a class="btn btn-sm btn-danger" title="delete" href="%s"><i class="fa-solid fa-trash"></i></a>',
			$admin->sectionLink(sprintf('comments/delete/%s/%d?token=%s', $comment['post_id'], $comment['index'], urlencode(sedot::csrfToken())))
		);
		$rows     .= sprintf(
			'<tr><td>%s</td><td><a href="%s">%s</a></td><td>%s<br><small class="text-muted">%s</small></td><td><span class="badge text-bg-%s">%s</span></td><td class="text-nowrap">%s</td></tr>',
			sedot::e(date('d M Y H:i', strtotime((string)($comment['date'] ?? '')))),
			sedot::e(sedot::_url($postSlug)),
			sedot::e($postTitle),
			sedot::e((string)($comment['name'] ?? '')),
			sedot::e((string)($comment['email'] ?? '')),
			$status === 'approved' ? 'success' : ($status === 'spam' ? 'danger' : 'secondary'),
			sedot::e($status),
			$actions
		);
	}

	$navTabs = '<div class="mb-3"><nav><ul class="nav nav-pills">';
	foreach (['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'spam' => 'Spam'] as $key => $label) {
		$badge = $key !== 'all' ? sprintf(' <span class="badge text-bg-secondary">%d</span>', $counts[$key]) : '';
		$navTabs .= sprintf(
			'<li class="nav-item"><a class="nav-link%s" href="%s">%s%s</a></li>',
			$filter === $key ? ' active' : '',
			sedot::e($admin->sectionLink('comments') . ($key === 'all' ? '' : '?status=' . $key)),
			$label,
			$badge
		);
	}
	$navTabs .= '</ul></nav></div>';

	$table = '<h2>Comments</h2>' . $navTabs . '<div class="table-responsive"><table class="table table-hover align-middle">
	<tr class="table-light"><th>Date</th><th>Post</th><th>Author</th><th>Status</th><th>Action</th></tr>
	%s</table></div>';
	$tableBody = $rows !== '' ? $rows : '<tr><td colspan="5" class="text-muted">No comments yet.</td></tr>';
	$admin->addWidget('primary', sprintf($table, $tableBody));
} elseif ($section === 'mypost' || $section === 'draft') {
	$indexPublished = $admin->loadJson('index', $section === 'mypost' ? 'post' : 'draft');
	$setNavi        = $admin->setNavi(count($indexPublished), POSTS_PER_PAGE, 5);

	if (($getRequest[2] ?? '') === 'page' && ctype_digit((string)($getRequest[3] ?? ''))) {
		$indexKey = (int)$getRequest[3];
	} else {
		$indexKey = 0;
	}
	if (!isset($indexPublished[$indexKey])) {
		$indexKey = 0;
	}

	$indexList  = $admin->index_page($indexPublished[$indexKey] ?? []);
	$formatRows = '';
	foreach ($indexList as $meta) {
		$editPostUrl   = sprintf('%s/%s', $admin->sectionLink('edit'), $meta['id']);
		$removePostUrl = sprintf('%s/%s', $admin->sectionLink('delete'), $meta['id']);
		$postFile      = sp_path('sp_post/post' . $meta['id']);
		$size          = is_file($postFile) ? round(filesize($postFile) / 1024, 2) : 0;
		$formatRows   .= sprintf(
			'<tr><td>%s</td><td><a href="%s">%s</a></td><td>%s</td><td>%s</td><td class="text-nowrap">
			<a title="edit" href="%s"><i class="fa-solid fa-pencil"></i></a>
			<a title="delete" href="%s"><i class="fa-solid fa-trash"></i></a>
			</td></tr>',
			sedot::e(date('d M Y H:i', strtotime((string)$meta['date']))),
			sedot::e($meta['url']),
			sedot::e($meta['title']),
			sedot::e((string)$size),
			sedot::e($meta['id']),
			$editPostUrl,
			$removePostUrl
		);
	}

	$table = '<h2>%s</h2>
	<div class="table-responsive">
	<table class="table table-hover align-middle">
	<tr class="table-light"><th>Date</th><th>Title</th><th>Size (Kb)</th><th>Post Id</th><th>Action</th></tr>
	%s
	</table>
	</div>';
	$tableBody = $formatRows !== '' ? $formatRows : '<tr><td colspan="5" class="text-muted">No posts yet.</td></tr>';
	$admin->addWidget('primary', sprintf($table, $section === 'mypost' ? 'My Posts' : 'Saved Drafts', $tableBody));

	$sidebar = '<h2>Options</h2><p class="text-muted mb-0">Posts sorted by date, newest first.</p>';
	$admin->addWidget('secondary', $sidebar);

	if ($setNavi) {
		$admin->addWidget('primary', $admin->pageNavi($indexKey, $setNavi, $baseUrl));
	}
} elseif ($section === 'delete' && isset($getRequest[2])) {
	$deleteId = sedot::sanitizePostId($getRequest[2]);
	$onDelete = $deleteId !== '' && sedot::loadPost($deleteId) !== null;
	if (!$onDelete) {
		die();
	}

	$form = sprintf('<h2>Delete Post</h2>
	<form action="" method="post">
	%s
	<div class="mb-3">
	<div class="form-check">
	<input class="form-check-input" type="radio" name="del" id="delYes" value="yes" checked>
	<label class="form-check-label" for="delYes">Yes, delete this post permanently</label>
	</div>
	<div class="form-check">
	<input class="form-check-input" type="radio" name="del" id="delNo" value="no">
	<label class="form-check-label" for="delNo">No, keep it</label>
	</div>
	</div>
	<button class="btn btn-danger" type="submit" name="confirm" value="ok">OK</button>
	</form>',
		$csrfField
	);

	if (isset($_POST['confirm'])) {
		if (!sedot::checkCsrf($_POST['sp_csrf'] ?? '')) {
			$admin->addWidget('primary', $admin->alertMessage('Security token invalid.', 'danger'));
		} elseif (($_POST['del'] ?? '') === 'yes' && sedot::removeExistingFile(sp_path('sp_post/post' . $deleteId))) {
			@unlink(sedot::commentFile($deleteId));
			sedot::create_index();
			$admin->addWidget('primary', $admin->alertMessage('Post Deleted', 'success'));
		} else {
			$admin->addWidget('primary', $admin->alertMessage('Not Deleted', 'danger'));
		}
	} else {
		$admin->addWidget('primary', $form);
	}
} elseif ($section === 'widget') {
	$widgetConf = sedot::themeConfig();
	$widgetFile = '_widget-' . THEME;
	$widgetPath = sp_path('sp_static/misc/' . $widgetFile . '.json');

	if (!is_file($widgetPath)) {
		$widgetSection = array_fill_keys($widgetConf['area'], []);
		if ($admin->toFile($widgetPath, $admin->toJson($widgetSection), true)) {
			admin_flash('success', 'Default widget config created.');
		}
	}

	$loadWidget = $admin->loadJson('misc', $widgetFile);
	if (!is_array($loadWidget)) {
		$loadWidget = [];
	}
	foreach ($loadWidget as $wArea => $wItems) {
		if (is_array($wItems)) {
			$loadWidget[$wArea] = array_values($wItems);
		}
	}
	$widgetArea = $getRequest[2] ?? '';
	$widgetAct  = $getRequest[3] ?? '';
	$widgetItem = $getRequest[4] ?? '';
	$widgetOk   = sedot::checkCsrf($_GET['token'] ?? ($_POST['sp_csrf'] ?? ''));

	if ($widgetArea !== '' && $widgetOk) {
		if (($admin->input('widgetAction') === 'edithtml') && isset($loadWidget[$widgetArea][$widgetItem])) {
			$loadWidget[$widgetArea][$widgetItem]['title'] = $admin->input('widgetTitle');
			$loadWidget[$widgetArea][$widgetItem]['code']   = (string)($_POST['htmlCode'] ?? '');
			$admin->toFile($widgetPath, $admin->toJson($loadWidget), true);
			admin_flash('success', 'Widget updated.');
			header('Location: ' . $admin->sectionLink('widget'));
			exit;
		}
		if (isset($_POST['addWidget']) && isset($loadWidget[$widgetArea])) {
			$widgetType = $admin->input('addWidget');
			if ($widgetType === 'html') {
				$code = (string)($_POST['htmlCode'] ?? '');
			} elseif (in_array($widgetType, sedot::widgetMethods(), true)) {
				$code = sprintf('#%s#', strtoupper($widgetType));
			} else {
				$code = '';
				$widgetType = 'html';
			}
			$key = count($loadWidget[$widgetArea]);
			$loadWidget[$widgetArea][$key] = [
				'type'  => $widgetType,
				'title' => $admin->input('widgetTitle'),
				'code'  => $code,
			];
			$admin->toFile($widgetPath, $admin->toJson($loadWidget), true);
			admin_flash('success', 'Widget added.');
			header('Location: ' . $admin->sectionLink('widget'));
			exit;
		}
		if (in_array($widgetAct, ['up', 'down', 'delete'], true) && isset($loadWidget[$widgetArea]) && $widgetItem !== '') {
			$items = $loadWidget[$widgetArea];
			$idx   = (int)$widgetItem;
			if ($widgetAct === 'up') {
				$items = sedot::up($items, $idx);
			} elseif ($widgetAct === 'down') {
				$items = sedot::down($items, $idx);
			} else {
				unset($items[$idx]);
			}
			$loadWidget[$widgetArea] = array_values($items);
			$admin->toFile($widgetPath, $admin->toJson($loadWidget), true);
			header('Location: ' . $admin->sectionLink('widget'));
			exit;
		}
	} elseif ($widgetArea !== '' && !$widgetOk) {
		$widgetAttempt = $_SERVER['REQUEST_METHOD'] === 'POST' || in_array($widgetAct, ['up', 'down', 'delete'], true);
		if ($widgetAttempt) {
			admin_flash('danger', 'Security token invalid.');
		}
	}

	$widgetToken = urlencode(sedot::csrfToken());

	if ($widgetAct === 'add' && !isset($_POST['widgetType'])) {
		$form = sprintf('<form action="%1$s" method="post">
		%2$s
		<label class="form-label d-block">(%3$s) New Widget:</label>
		<div class="form-check">
		<input class="form-check-input" type="radio" name="widgetType" id="wtHtml" value="html" checked>
		<label class="form-check-label" for="wtHtml">Custom HTML/Javascript Code</label>
		</div>
		<div class="form-check">
		<input class="form-check-input" type="radio" name="widgetType" id="wtOwn" value="own">
		<label class="form-check-label" for="wtOwn">Built-in Widget Function</label>
		</div>
		<button class="btn btn-primary mt-2" type="submit" value="ok">OK</button>
		<a class="btn btn-link" href="%4$s">Cancel</a>
		</form>',
			$admin->sectionLink('widget/' . $widgetArea . '/add'),
			$csrfField,
			sedot::e($widgetArea),
			$admin->sectionLink('widget')
		);
		$admin->addWidget('primary', $admin->panelBox($form, 'primary'), 'before');
	}

	if ($widgetAct === 'add' && isset($_POST['widgetType'])) {
		$widgetType = $admin->input('widgetType');
		$cancelUrl  = $admin->sectionLink('widget/' . $widgetArea);
		if ($widgetType === 'html') {
			$htmlForm = sprintf('<h3>New Widget</h3>
			<form action="%1$s" method="post">
			%2$s
			<input type="hidden" name="addWidget" value="html"/>
			<div class="mb-3">
			<label class="form-label">Widget Title</label>
			<input class="form-control" type="text" name="widgetTitle"/>
			<label class="form-label">HTML Code</label>
			<textarea class="form-control" name="htmlCode" rows="5"></textarea>
			</div>
			<button class="btn btn-success" type="submit">Save</button>
			<a class="btn btn-link" href="%3$s">Cancel</a>
			</form>',
				$cancelUrl,
				$csrfField,
				$cancelUrl
			);
		} elseif ($widgetType === 'own') {
			$widgetNameList = '';
			foreach (sedot::widgetMethods() as $fn) {
				$widgetNameList .= sprintf('<option value="%s">%s</option>', sedot::e($fn), sedot::e($fn));
			}
			$htmlForm = sprintf('<h3>New Widget</h3>
			<form action="%1$s" method="post">
			%2$s
			<div class="mb-3">
			<label class="form-label">Widget Function Name</label>
			<select class="form-select" name="addWidget">%3$s</select>
			<label class="form-label">Widget Title</label>
			<input class="form-control" type="text" name="widgetTitle"/>
			</div>
			<button class="btn btn-success" type="submit">Save</button>
			<a class="btn btn-link" href="%4$s">Cancel</a>
			</form>',
				$cancelUrl,
				$csrfField,
				$widgetNameList,
				$cancelUrl
			);
		} else {
			$htmlForm = sprintf('<h3>New Widget</h3>
			<form action="%1$s" method="post">
			%2$s
			<input type="hidden" name="addWidget" value="%3$s"/>
			<div class="mb-3">
			<label class="form-label">Widget Title</label>
			<input class="form-control" type="text" name="widgetTitle"/>
			</div>
			<button class="btn btn-success" type="submit">Save</button>
			<a class="btn btn-link" href="%4$s">Cancel</a>
			</form>',
				$cancelUrl,
				$csrfField,
				sedot::e($widgetType),
				$cancelUrl
			);
		}
		$admin->addWidget('primary', $admin->panelBox($htmlForm, 'primary'), 'before');
	}

	if ($widgetAct === 'edit' && isset($loadWidget[$widgetArea][$widgetItem])) {
		$item = $loadWidget[$widgetArea][$widgetItem];
		$htmlForm = sprintf('<h3>Edit Widget</h3>
		<form action="%1$s" method="post">
		%2$s
		<h6>Widget Type: %3$s</h6>
		<input type="hidden" name="widgetAction" value="edithtml"/>
		<input type="hidden" name="widgetArea" value="%4$s"/>
		<input type="hidden" name="widgetItemId" value="%5$s"/>
		<div class="mb-3">
		<label class="form-label">Widget Title</label>
		<input class="form-control" type="text" value="%6$s" name="widgetTitle"/>
		<label class="form-label">HTML Code</label>
		<textarea class="form-control" name="htmlCode" rows="6">%7$s</textarea>
		</div>
		<button class="btn btn-success" type="submit">Save</button>
		<a class="btn btn-link" href="%8$s">Cancel</a>
		</form>',
			$admin->sectionLink('widget/' . $widgetArea),
			$csrfField,
			sedot::e((string)($item['type'] ?? '')),
			sedot::e($widgetArea),
			sedot::e((string)$widgetItem),
			sedot::e((string)($item['title'] ?? '')),
			sedot::e((string)($item['code'] ?? '')),
			$admin->sectionLink('widget')
		);
		$admin->addWidget('primary', $admin->panelBox($htmlForm, 'primary'), 'before');
	}

	foreach ($widgetConf['area'] as $area) {
		$items = isset($loadWidget[$area]) && is_array($loadWidget[$area]) ? $loadWidget[$area] : [];
		$widgetList = '';
		foreach ($items as $keys => $widgets) {
			$widgetList .= sprintf(
				'<li class="list-unstyled d-flex gap-2 align-items-center mb-1">
				<a class="flex-grow-1" href="%1$s"><b>%5$s</b></a>
				<a href="%2$s" title="move up"><i class="fa-solid fa-arrow-up"></i></a>
				<a href="%3$s" title="move down"><i class="fa-solid fa-arrow-down"></i></a>
				<a href="%4$s" title="delete"><i class="fa-solid fa-xmark"></i></a>
				</li>',
				$admin->sectionLink('widget/' . $area . '/edit/' . $keys),
				$admin->sectionLink('widget/' . $area . '/up/' . $keys) . '?token=' . $widgetToken,
				$admin->sectionLink('widget/' . $area . '/down/' . $keys) . '?token=' . $widgetToken,
				$admin->sectionLink('widget/' . $area . '/delete/' . $keys) . '?token=' . $widgetToken,
				(isset($widgets['title']) && $widgets['title'] !== '') ? sedot::e($widgets['title']) : '(notitle)'
			);
		}
		$box = sprintf(
			'<h3 class="h5">%1$s <a href="%2$s" title="add widget"><i class="fa-solid fa-plus"></i></a></h3>
			<ul class="list-unstyled">%3$s</ul>',
			sedot::e($area),
			$admin->sectionLink('widget/' . $area . '/add'),
			$widgetList !== '' ? $widgetList : '<li class="text-muted">empty</li>'
		);
		$admin->addWidget('primary', $box);
	}
} elseif ($section === 'option') {
	$cfg = sp_load_config();
	$isSave = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings']);

	if ($isSave) {
		if (!sedot::checkCsrf($_POST['sp_csrf'] ?? '')) {
			admin_flash('danger', 'Security token invalid.');
		} else {
			$new           = $cfg;
			$saveBlocked   = false;
			$new['site_title'] = $admin->input('site_title', 'Sedotpress');
			$new['site_desc']  = $admin->input('site_desc');
			$siteUrl           = $admin->input('site_url');
			if ($siteUrl !== '' && (!filter_var($siteUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $siteUrl))) {
				admin_flash('danger', 'Site URL must start with http:// or https://');
				$saveBlocked = true;
			}
			$new['site_url'] = $siteUrl;
			$timezone = $admin->input('timezone');
			if (in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
				$new['timezone'] = $timezone;
			}
			$language = $admin->input('language');
			if (preg_match('/^[a-zA-Z_]{2,10}$/', $language)) {
				$new['language'] = $language;
			}
			$theme = $admin->input('theme');
			if (preg_match('/^[a-z0-9_\-]+$/i', $theme) && is_dir(sp_path('sp_theme/' . $theme))) {
				$new['theme'] = $theme;
			}
			$new['posts_per_page'] = min(100, max(1, (int)$admin->input('posts_per_page', 5)));
			$gravEmail = $admin->input('gravatar_email');
			$new['gravatar_email'] = ($gravEmail === '' || filter_var($gravEmail, FILTER_VALIDATE_EMAIL)) ? $gravEmail : $cfg['gravatar_email'];
			$adminUser = $admin->input('admin_user');
			if ($adminUser !== '') {
				$new['admin_user'] = $adminUser;
			}
			$newPass = (string)($_POST['admin_pass'] ?? '');
			if ($newPass !== '') {
				if (strlen($newPass) < 8) {
					admin_flash('danger', 'New password must be at least 8 characters.');
					$saveBlocked = true;
				} else {
					$new['admin_pass'] = password_hash($newPass, PASSWORD_DEFAULT);
				}
			}
			$new['comment_auto_approve'] = isset($_POST['comment_auto_approve']);
			$new['debug'] = isset($_POST['debug']);

			if ($saveBlocked) {
				$cfg = $new;
			} elseif (sp_save_config($new)) {
				admin_flash('success', 'Settings saved.');
				header('Location: ' . $admin->sectionLink('option'));
				exit;
			} else {
				admin_flash('danger', 'Could not write config.json, check file permissions.');
				$cfg = $new;
			}
		}
	}

	$passHashed = (bool)preg_match('/^\$2[ayb]\$/', (string)$cfg['admin_pass']);
	$timezoneList = '';
	foreach (DateTimeZone::listIdentifiers() as $tz) {
		$timezoneList .= sprintf('<option value="%s"%s>%s</option>', sedot::e($tz), $cfg['timezone'] === $tz ? ' selected' : '', sedot::e($tz));
	}
	$themeList = '';
	foreach ((array)glob(sp_path('sp_theme/*'), GLOB_ONLYDIR) as $dir) {
		$name = basename($dir);
		$themeList .= sprintf('<option value="%s"%s>%s</option>', sedot::e($name), $cfg['theme'] === $name ? ' selected' : '', sedot::e($name));
	}

	$settingsForm = sprintf('<h2>Settings</h2>
	<form action="%1$s" method="post">
	%2$s
	<div class="mb-3">
	<label class="form-label" for="s-title">Site Title</label>
	<input class="form-control" id="s-title" type="text" name="site_title" value="%3$s"/>
	</div>
	<div class="mb-3">
	<label class="form-label" for="s-desc">Site Description</label>
	<input class="form-control" id="s-desc" type="text" name="site_desc" value="%4$s"/>
	</div>
	<div class="mb-3">
	<label class="form-label" for="s-url">Site URL (empty = auto detect)</label>
	<input class="form-control" id="s-url" type="url" name="site_url" placeholder="https://example.com/blog" value="%5$s"/>
	</div>
	<div class="row">
	<div class="col-md-6 mb-3">
	<label class="form-label" for="s-tz">Timezone</label>
	<select class="form-select" id="s-tz" name="timezone">%6$s</select>
	</div>
	<div class="col-md-6 mb-3">
	<label class="form-label" for="s-lang">Language code</label>
	<input class="form-control" id="s-lang" type="text" name="language" value="%7$s"/>
	</div>
	</div>
	<div class="row">
	<div class="col-md-6 mb-3">
	<label class="form-label" for="s-theme">Theme</label>
	<select class="form-select" id="s-theme" name="theme">%8$s</select>
	</div>
	<div class="col-md-6 mb-3">
	<label class="form-label" for="s-ppp">Posts per page</label>
	<input class="form-control" id="s-ppp" type="number" min="1" max="100" name="posts_per_page" value="%9$d"/>
	</div>
	</div>
	<div class="mb-3">
	<label class="form-label" for="s-grav">Gravatar default email</label>
	<input class="form-control" id="s-grav" type="email" name="gravatar_email" value="%10$s"/>
	</div>
	<hr>
	<div class="row">
	<div class="col-md-6 mb-3">
	<label class="form-label" for="s-user">Admin username</label>
	<input class="form-control" id="s-user" type="text" name="admin_user" value="%11$s"/>
	</div>
	<div class="col-md-6 mb-3">
	<label class="form-label" for="s-pass">New password (%12$s)</label>
	<input class="form-control" id="s-pass" type="password" name="admin_pass" autocomplete="new-password" placeholder="leave blank to keep"/>
	</div>
	</div>
	<div class="form-check mb-2">
	<input class="form-check-input" type="checkbox" id="s-approve" name="comment_auto_approve"%13$s>
	<label class="form-check-label" for="s-approve">Auto approve new comments</label>
	</div>
	<div class="form-check mb-3">
	<input class="form-check-input" type="checkbox" id="s-debug" name="debug"%14$s>
	<label class="form-check-label" for="s-debug">Debug mode (show PHP errors in browser)</label>
	</div>
	<button class="btn btn-primary" type="submit" name="save_settings" value="ok">Save Settings</button>
	</form>',
		$admin->sectionLink('option'),
		$csrfField,
		sedot::e($cfg['site_title']),
		sedot::e($cfg['site_desc']),
		sedot::e($cfg['site_url']),
		$timezoneList,
		sedot::e($cfg['language']),
		$themeList,
		(int)$cfg['posts_per_page'],
		sedot::e($cfg['gravatar_email']),
		sedot::e($cfg['admin_user']),
		$passHashed ? 'bcrypt hash' : 'plaintext',
		!empty($cfg['comment_auto_approve']) ? ' checked' : '',
		!empty($cfg['debug']) ? ' checked' : ''
	);
	$admin->addWidget('primary', $settingsForm);

	$backupBox = sprintf('<h3 class="h5">Backup</h3>
	<p class="mb-2">Download every post and comment as a single JSON file.</p>
	<a class="btn btn-outline-primary" href="%s"><i class="fa-solid fa-download"></i> Export backup</a>',
		$admin->sectionLink('export')
	);
	$admin->addWidget('secondary', $admin->panelBox($backupBox, 'primary'));
} elseif ($section === 'export') {
	$posts = [];
	foreach ((array)glob(sp_path('sp_post/post*')) as $file) {
		$postData = json_decode((string)@file_get_contents($file), true);
		if (is_array($postData)) {
			$postData['id'] = basename($file, '.json');
			$posts[]        = $postData;
		}
	}
	$payload = [
		'app'        => 'sedotpress',
		'version'    => SEDOT_VER,
		'exported_at'=> date('c'),
		'site'       => SITE_URL,
		'posts'      => $posts,
		'comments'   => sedot::allComments(),
	];
	$filename = 'sedotpress-backup-' . date('Ymd-His') . '.json';
	header('Content-Type: application/json; charset=UTF-8');
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	header('Cache-Control: no-store');
	echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	exit;
} elseif ($section === 'import') {
	$dialogBox = '<h2>Import from Blogger backup (XML)</h2>
	<p class="text-muted">Export your blog from Blogger as XML, then upload it here. Existing posts are kept.</p>
	<form action="" method="post" enctype="multipart/form-data">
	' . $csrfField . '
	<div class="mb-3">
	<input class="form-control" type="file" name="blogxml" accept=".xml,text/xml,application/xml"/>
	<input type="hidden" name="act" value="ok"/>
	</div>
	<button class="btn btn-primary" type="submit">Upload &amp; Import</button>
	</form>';

	if (isset($_POST['act']) && $_POST['act'] === 'ok') {
		if (!sedot::checkCsrf($_POST['sp_csrf'] ?? '')) {
			$admin->addWidget('primary', $admin->alertMessage('Security token invalid.', 'danger'));
		} else {
			$xmlString = '';
			if (!empty($_FILES['blogxml']['tmp_name']) && is_uploaded_file($_FILES['blogxml']['tmp_name'])) {
				if ((int)($_FILES['blogxml']['size'] ?? 0) > 10 * 1024 * 1024) {
					$admin->addWidget('primary', $admin->alertMessage('File too large (max 10 MB).', 'danger'));
					$xmlString = null;
				} else {
					$xmlString = (string)file_get_contents($_FILES['blogxml']['tmp_name']);
				}
			} elseif (is_file(sp_path('blog.xml'))) {
				$xmlString = (string)file_get_contents(sp_path('blog.xml'));
			}
			if ($xmlString === null) {
				// error already added
			} elseif (trim($xmlString) === '') {
				$admin->addWidget('primary', $admin->alertMessage('No file uploaded (you can also place blog.xml in the Sedotpress folder).', 'danger'));
			} else {
				libxml_use_internal_errors(true);
				$parse = simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
				libxml_clear_errors();
				if ($parse === false) {
					$admin->addWidget('primary', $admin->alertMessage('The XML file could not be parsed.', 'danger'));
				} else {
					$decode = json_decode(json_encode($parse), true);
					$rawEntries = is_array($decode['entry'] ?? null) ? $decode['entry'] : [];
					if (isset($rawEntries['content']) || isset($rawEntries['title']) || isset($rawEntries['published'])) {
						$entries = [$rawEntries];
					} else {
						$entries = array_reverse($rawEntries, true);
					}
					$term = 'http://schemas.google.com/blogger/2008/kind#post';
					$imported = 0;
					foreach ($entries as $content) {
						if (!is_array($content)) {
							continue;
						}
						$categories = is_array($content['category'] ?? null) ? $content['category'] : [];
						$isPost = false;
						$tags = [];
						foreach ($categories as $cat) {
							$catTerm = is_array($cat) ? (string)($cat['@attributes']['term'] ?? '') : '';
							if ($catTerm === $term) {
								$isPost = true;
								continue;
							}
							if ($catTerm !== '') {
								$tags[] = $catTerm;
							}
						}
						if (!$isPost) {
							continue;
						}
						$link = '';
						foreach ((array)($content['link'] ?? []) as $lk) {
							$attrs = is_array($lk) ? (array)($lk['@attributes'] ?? []) : [];
							if (($attrs['rel'] ?? '') === 'alternate' && !empty($attrs['href'])) {
								$link = (string)$attrs['href'];
								break;
							}
						}
						$slugPath  = (string)parse_url($link, PHP_URL_PATH);
						$permalink = pathinfo($slugPath, PATHINFO_FILENAME);
						$title     = is_string($content['title'] ?? null) ? $content['title'] : (string)($content['title']['#content'] ?? 'untitled');
						$permalink = $permalink !== '' ? $admin->filterStr($permalink) : $admin->filterStr($title);
						if ($permalink === '' || in_array($permalink, RESERVED_SLUGS, true)) {
							$permalink = $admin->filterStr($title) ?: 'imported-post';
						}
						$body    = $content['content'] ?? '';
						if (is_array($body)) {
							$body = (string)($body['#content'] ?? '');
						}
						$published = is_string($content['published'] ?? null) ? $content['published'] : '';
						$data = [
							'date'      => ($published !== '' && strtotime($published)) ? date('c', strtotime($published)) : date('c'),
							'title'     => $title !== '' ? $title : 'untitled',
							'permalink' => $permalink,
							'tag'       => $tags ? implode(',', $tags) : 'uncategorized',
							'entry'     => $body !== '' ? $body : 'Imported post (empty content)',
							'status'    => 'publish',
						];
						$postFileName = $admin->assignPostId();
						if ($admin->toFile(sp_path('sp_post/' . $postFileName), $admin->toJson($data), true)) {
							$imported += 1;
						}
					}
					if ($imported > 0) {
						sedot::create_index();
						$admin->addWidget('primary', $admin->alertMessage($imported . ' post(s) imported.', 'success'));
					} else {
						$admin->addWidget('primary', $admin->alertMessage('No Blogger posts found in that file.', 'danger'));
					}
				}
			}
		}
	}
	$admin->addWidget('primary', $dialogBox);
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo sedot::e(SITE_TITLE); ?> [Backstage]</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet">
  </head>
  <body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
          <a class="navbar-brand" href="<?php echo sedot::e($admin->_url()); ?>"><?php echo sedot::e(SITE_TITLE); ?></a>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#backstageNav" aria-controls="backstageNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="backstageNav">
            <ul class="navbar-nav me-auto">
				<?php
				$pendingCount = sedot::countCommentsByStatus('pending');
				$navItems = [
					'new'      => ['fa-pencil', 'New Post'],
					'mypost'   => ['fa-file-lines', 'My Posts'],
					'draft'    => ['fa-floppy-disk', 'Saved Drafts'],
					'comments' => ['fa-comments', 'Comments' . ($pendingCount ? ' (' . $pendingCount . ')' : '')],
					'widget'   => ['fa-sliders', 'Widget'],
					'import'   => ['fa-file-import', 'Import'],
					'export'   => ['fa-file-export', 'Export'],
					'option'   => ['fa-gear', 'Settings'],
					'logout'   => ['fa-right-from-bracket', 'Logout'],
				];
				foreach ($navItems as $navKey => $navMeta):
					$isActive = ($navKey === $section || ($navKey === 'mypost' && $section === 'edit') || ($navKey === 'mypost' && $section === 'delete'));
				?>
				<li class="nav-item">
					<a class="nav-link<?php echo $isActive ? ' active' : ''; ?>" href="<?php echo sedot::e($admin->sectionLink($navKey)); ?>"><i class="fa-solid <?php echo $navMeta[0]; ?>"></i> <?php echo $navMeta[1]; ?></a>
				</li>
				<?php endforeach; ?>
            </ul>
          </div>
        </div>
   </nav>
  <header class="page-header py-3 mb-2 bg-body-emphasis border-bottom">
  <div class="container">
   <h1 class="h3 mb-0"><?php echo sedot::e(SITE_TITLE); ?> <small class="text-muted fw-normal"><?php echo sedot::e(SITE_DESC); ?></small></h1>
  </div>
  </header>
  <div class="container mb-5">
	<?php if ($adminFlash): ?>
	<div class="mb-3"><?php echo $admin->alertMessage(sedot::e($adminFlash['text']), $adminFlash['type'] ?? 'info'); ?></div>
	<?php endif; ?>
   <div class="row">
   	<div class="col-lg-8">
	<?php $admin->widgetSection('primary'); ?>
	</div>
	<div class="col-lg-4">
	<?php $admin->widgetSection('secondary'); ?>
	</div>
   </div>
  </div>
  <footer class="border-top py-3 mb-4 text-muted">
  <div class="container">
	<?php
	$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$finish = $time;
	$total_time = round(($finish - $start), 3);
	echo '<small>Powered by Sedotpress ' . sedot::e(SEDOT_VER) . ' - Page generated in ' . $total_time . ' seconds.</small>';
	?>
  </div>
  </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
	<?php if ($section === 'new' || $section === 'edit'): ?>
	<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
	<script>
	(function () {
		const Delta = Quill.import('delta');
		const quill = new Quill('#editor', { theme: 'snow', placeholder: 'Write your story...' });
		const source = document.getElementById('konten');
		if (source.value.trim() !== '') {
			quill.root.innerHTML = source.value;
		}
		document.getElementById('postForm').addEventListener('submit', function () {
			source.value = quill.root.innerHTML;
		});
		function insertImage(file) {
			const reader = new FileReader();
			reader.onload = function () {
				const range = quill.getSelection(true);
				quill.updateContents(new Delta().retain(range.index).insert({ image: reader.result }));
			};
			reader.readAsDataURL(file);
		}
		quill.container.addEventListener('paste', function (event) {
			const items = event.clipboardData && event.clipboardData.items;
			if (!items) { return; }
			for (let i = 0; i < items.length; i++) {
				if (items[i].type && items[i].type.indexOf('image/') === 0) {
					event.preventDefault();
					const file = items[i].getAsFile();
					if (file) { insertImage(file); }
				}
			}
		});
		quill.container.addEventListener('drop', function (event) {
			if (event.dataTransfer && event.dataTransfer.files) {
				event.preventDefault();
				for (let i = 0; i < event.dataTransfer.files.length; i++) {
					const file = event.dataTransfer.files[i];
					if (file.type.indexOf('image/') === 0) { insertImage(file); }
				}
			}
		});
	})();
	</script>
	<?php endif; ?>
  </body>
</html>
