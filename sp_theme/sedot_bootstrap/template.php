<?php
if (!defined('SEDOT_VER')) {
	die();
}

$post->enableWidget();
$post->createWidget('head');
$post->createWidget('header');

$entries = is_array($entries ?? null) ? $entries : [];
$tagLine = '';
$titleTag = '';
$metaDescription = '';

if ($_PAGE_404) {
	$box404 = '<div class="col-12"><div class="alert alert-warning" role="alert">
	<h1 class="h4 alert-heading">404 — Page Not Found</h1>
	<p class="mb-0">The page you are looking for does not exist. Try the <a href="' . sedot::e($post->_url()) . '">homepage</a>.</p>
	</div></div>';
	$post->addWidget($_FRONT_PAGE ? 'index-top' : 'post-top', $box404);
	$titleTag = '<title>404 Not Found - ' . sedot::e(SITE_TITLE) . '</title>';
}

if ($_FRONT_PAGE) {
	if ($_SEARCH_MODE) {
		$pageLabel = isset($getRequest[3]) && $getRequest[2] === 'page' ? ' | page ' . sedot::e($getRequest[3]) : '';
		$titleTag  = '<title>' . sedot::e(SITE_TITLE) . ' - ' . sedot::e($searchQuery) . $pageLabel . '</title>';
	} elseif (($getRequest[0] ?? '') === 'page') {
		$titleTag = '<title>' . sedot::e(SITE_TITLE) . ' | page ' . sedot::e((string)($getRequest[1] ?? '1')) . '</title>';
	} else {
		$titleTag = '<title>' . sedot::e(SITE_TITLE) . ' - ' . sedot::e(SITE_DESC) . '</title>';
	}
	$metaDescription = '<meta name="description" content="' . sedot::e(SITE_TITLE . ' - ' . SITE_DESC) . '">';

	$format = '';
	foreach ($entries as $id => $entry) {
		$tagLine = '';
		foreach (($entry['tag'] ?? []) as $label) {
			$tagLine .= '<a class="badge text-bg-success text-decoration-none" href="' . sedot::e($post->_url('/tag/' . rawurlencode($label))) . '">' . sedot::e($label) . '</a> ';
		}
		$thumb = '';
		if (!empty($entry['image'])) {
			$thumb = '<a class="float-start me-3 mb-2" href="' . sedot::e($entry['url']) . '">'
				. '<img class="img-thumbnail" alt="' . sedot::e($entry['title']) . '" style="max-width:200px;max-height:200px" src="' . sedot::e($entry['image']) . '">'
				. '</a>';
		}
		$format .= sprintf(
			'<div class="col-12 mb-4"><article>
			<h2 class="h4 mb-2"><a href="%1$s">%2$s</a></h2>
			<div class="mb-2"><time class="badge text-bg-info" datetime="%3$s">%4$s</time> %5$s</div>
			<p>%6$s%7$s</p>
			</article></div>',
			sedot::e($entry['url']),
			sedot::e($entry['title']),
			sedot::e((string)$entry['date']),
			sedot::e(date('d M Y H:i', strtotime((string)$entry['date']))),
			$tagLine,
			$thumb,
			sedot::e($post->getExcerpt((string)$entry['entry'], 250))
		);
	}
	if ($format === '') {
		$empty = $_SEARCH_MODE
			? 'No results for &ldquo;' . sedot::e($searchQuery) . '&rdquo;.'
			: 'No posts published yet.';
		$format = '<div class="col-12"><div class="alert alert-info">' . $empty . '</div></div>';
	}
	$post->addWidget('index-top', $format);
	$post->addWidget('head', $titleTag);
	$post->addWidget('head', $metaDescription);
} elseif ($_SINGLE_POST && is_array($entry)) {
	$tagLine = '';
	foreach (($entry['tag'] ?? []) as $label) {
		$tagLine .= '<a class="badge text-bg-success text-decoration-none" href="' . sedot::e($post->_url('/tag/' . rawurlencode($label))) . '">' . sedot::e($label) . '</a> ';
	}
	$titleTag       = '<title>' . sedot::e($entry['title']) . ' - ' . sedot::e(SITE_TITLE) . '</title>';
	$metaDescription = '<meta name="description" content="' . sedot::e($post->getExcerpt((string)$entry['entry'], 160)) . '">';

	$commentsHtml = '<section id="comments" class="mt-4">';
	$commentsHtml .= '<h3 class="h5">Comments (' . count($comments ?? []) . ')</h3>';
	if (!empty($commentFlash)) {
		$commentsHtml .= '<div class="alert alert-' . sedot::e($commentFlash['type'] ?? 'info') . '" role="alert">' . sedot::e($commentFlash['text'] ?? '') . '</div>';
	}
	if (empty($comments)) {
		$commentsHtml .= '<p class="text-muted">No comments yet, be the first!</p>';
	}
	foreach (($comments ?? []) as $comment) {
		$commentUrl = (string)($comment['url'] ?? '');
		$linkHtml   = '';
		if ($commentUrl !== '' && filter_var($commentUrl, FILTER_VALIDATE_URL)) {
			$linkHtml = ' <a class="text-decoration-none" href="' . sedot::e($commentUrl) . '" rel="nofollow noopener"><small>(website)</small></a>';
		}
		$commentsHtml .= sprintf(
			'<div class="d-flex gap-3 mb-3 border-bottom pb-3">
			<img class="rounded-circle flex-shrink-0" src="%1$s" width="48" height="48" alt="" loading="lazy">
			<div class="flex-grow-1">
			<b>%2$s</b>%3$s <small class="text-muted">— %4$s</small>
			<div class="mt-1">%5$s</div>
			</div>
			</div>',
			sedot::e(sedot::gravatar((string)($comment['email'] ?? ''), 48)),
			sedot::e((string)($comment['name'] ?? '')),
			$linkHtml,
			sedot::e(date('d M Y H:i', strtotime((string)($comment['date'] ?? '')))),
			nl2br(sedot::e((string)($comment['content'] ?? '')))
		);
	}

	$commentsHtml .= sprintf(
		'<form method="post" action="%1$s" class="mt-3" id="commentForm">
		%2$s
		<input type="hidden" name="sp_action" value="comment">
		<input type="hidden" name="post_id" value="%3$s">
		<input type="hidden" name="sp_ts" value="%4$d">
		<input type="text" name="sp_trap" value="" tabindex="-1" autocomplete="off" class="d-none" aria-hidden="true">
		<div class="row">
		<div class="col-md-4 mb-3"><label class="form-label" for="c-name">Name *</label>
		<input class="form-control" id="c-name" type="text" name="cname" maxlength="50" required></div>
		<div class="col-md-4 mb-3"><label class="form-label" for="c-email">Email (optional, for Gravatar)</label>
		<input class="form-control" id="c-email" type="email" name="cemail"></div>
		<div class="col-md-4 mb-3"><label class="form-label" for="c-url">Website (optional)</label>
		<input class="form-control" id="c-url" type="url" name="curl" placeholder="https://"></div>
		</div>
		<div class="mb-3"><label class="form-label" for="c-body">Comment *</label>
		<textarea class="form-control" id="c-body" name="cbody" rows="4" maxlength="5000" required></textarea></div>
		<button class="btn btn-primary" type="submit">Post Comment</button>
		</form>',
		sedot::e($post->_url((string)$entry['permalink'])),
		sp_csrf_field(),
		sedot::e((string)($entry['id'] ?? '')),
		time()
	);
	$commentsHtml .= '</section>';

	$format = sprintf(
		'<div class="col-12"><article>
		<h1 class="h3">%1$s</h1>
		<div class="mb-3"><time class="badge text-bg-info" datetime="%2$s">%3$s</time> %4$s</div>
		<div class="post-content">%5$s</div>
		</article>%6$s</div>',
		sedot::e($entry['title']),
		sedot::e((string)$entry['date']),
		sedot::e(date('d M Y H:i', strtotime((string)$entry['date']))),
		$tagLine,
		(string)($entry['entry'] ?? ''),
		$commentsHtml
	);
	$post->addWidget('head', $titleTag);
	$post->addWidget('head', $metaDescription);
	$post->addWidget('post-top', $format);
}

$header = sprintf(
	'<h1 class="mb-0"><a class="text-decoration-none" href="%1$s">%2$s</a></h1><p class="lead mb-0">%3$s</p>',
	sedot::e($post->_url()),
	sedot::e(SITE_TITLE),
	sedot::e(SITE_DESC)
);
$post->addWidget('header', $header);
$post->addWidget('head', '<meta name="generator" content="sedotpress-' . sedot::e(SEDOT_VER) . '">');
if ($titleTag === '' || !$_FRONT_PAGE && !$_SINGLE_POST && !$_PAGE_404) {
	$post->addWidget('head', '<title>' . sedot::e(SITE_TITLE) . ' - ' . sedot::e(SITE_DESC) . '</title>');
}
$post->addWidget('head', '<link rel="alternate" type="application/rss+xml" title="' . sedot::e(SITE_TITLE) . ' RSS" href="' . sedot::e(sedot::_url('rss')) . '">');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $post->widgetSection('head'); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  </head>
  <body>
  <?php $post->widgetSection('top'); ?>
  <nav class="navbar navbar-expand-lg bg-body-secondary border-bottom mb-4">
    <div class="container">
      <a class="navbar-brand fw-bold" href="<?php echo sedot::e($post->_url()); ?>"><?php echo sedot::e(SITE_TITLE); ?></a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav" aria-controls="siteNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="siteNav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item"><a class="nav-link" href="<?php echo sedot::e($post->_url()); ?>"><i class="fa-solid fa-house"></i> Home</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo sedot::e(sedot::_url('rss')); ?>"><i class="fa-solid fa-square-rss"></i> RSS</a></li>
        </ul>
        <form class="d-flex" role="search" action="<?php echo sedot::e(sedot::_url('search')); ?>" method="get">
          <input class="form-control me-2" type="search" name="q" placeholder="Search..." aria-label="Search" value="<?php echo sedot::e($_GET['q'] ?? ''); ?>">
          <button class="btn btn-outline-primary" type="submit">Search</button>
        </form>
      </div>
    </div>
  </nav>
  <header class="site-header mb-4">
  <div class="container">
  <?php $post->widgetSection('header'); ?>
  </div>
  </header>
  <div class="container">
   <div class="row">
    <div class="col-md-6">
		<div class="row">
		<?php
		if ($_FRONT_PAGE) {
			$post->widgetSection('index-top');
			$post->widgetSection('index-bottom');
		} else {
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
  <footer class="border-top mt-4 py-4">
  <div class="container">
	  <div class="row">
		<?php $post->widgetSection('footer'); ?>
	  </div>
	  <small class="text-muted">Powered by <a href="https://github.com/sukualam/sedotpress">SEDOTPRESS</a></small>
  </div>
  </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
