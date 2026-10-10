# Sedotpress
The new lightweight and low-resources blogging platform written in PHP. It is single-file Flat-file blogging engine. No database required! All data saved in JSON format.

Requires **PHP 8.0+** (tested on PHP 8.3) with the `mbstring`, `json`, `SimpleXML` and (optionally) `curl`, `gd`, `intl` extensions.

## Features:
* Posts & comments saved in JSON format
* Lightweight & Low-Resource
* Portable
* URL Rewriting
* Built-In Comment System + Gravatar (pending/approved/spam moderation, honeypot + rate limit)
* Auto Generate sitemap.xml, rss.xml & robots.txt
* Search, tag archive & timeline pages
* Drafts, reserved-slug & duplicate-permalink protection
* CSRF-protected admin forms, bcrypt password support, IP blacklist
* Backup export (JSON) & Blogger XML import
* Widget manager (HTML, recent posts, tags, archives, page navigation, search box)
* Bootstrap 5 admin & theme (CDN), Quill rich-text editor
* etc ...

## Downloads:
https://github.com/sukualam/sedotpress/archive/master.zip

## Installing:
* Extract all files to your web server (root `/` or a subdirectory like `blog/`)
* Make sure the web server can write to the project folder (it creates `config.json`, `sp_post/` and `sp_static/` on first run)
* Open your site in the browser — a default `config.json` is generated automatically
* Open `http://(your_blog_url)/backstage` and login with the default credentials `admin` / `root`
* **Change the admin password immediately** in Backstage → Settings
* Finish!

Configuration lives in `config.json` (site title, URL, admin credentials, timezone, theme, debug mode). It is created on first run and contains your credentials — keep it out of version control (already listed in `.gitignore`).

## Security notes:
* The data folders (`sp_post/`, `sp_static/`) and `config.json` must NOT be reachable over HTTP.
  * **Apache**: the shipped `.htaccess` already blocks them.
  * **Nginx / PHP built-in server**: add a rule to deny those paths, or place the app behind a router (see below).
* Set `"debug": false` in `config.json` on production.

## URL Rewrites:
##### Apache
* Use the shipped `.htaccess` (also blocks `sp_post/`, `sp_static/` and `config.json`).

##### Lighttpd
* Add this line in /etc/lighttpd/lighttpd.conf
* `url.rewrite = ("^/(.*)\.(.+)$" => "$0","^/(.+)/?$" => "/index.php/$1")`

##### Nginx
* Point `try_files $uri $uri/ /index.php?$query_string;` at `index.php`, and add:
* `location ~ ^/(sp_post|sp_static)/ { deny all; }`
* `location = /config.json { deny all; }`

## Development server:
```
php -S localhost:8000 router.php
```
`router.php` serves the app with the PHP built-in server and blocks access to the data folders, `config.json` and path traversal.

## Latest News:
* To get the latest news and development status, visit my blog http://sedot.space

## Recent Changelog:
##### (for full changelog, read changelog.txt)
* v0.2.0: PHP 8 compatibility overhaul, config.json, Bootstrap 5 theme/admin, comments re-enabled, CSRF protection, RSS/sitemap/robots, search, Blogger import, widget manager fixes — see changelog.txt

## Managing and Other:
##### To enter the admin area (backstage)
* Open `http://(your_blog_url)/backstage`

##### Theme & Modification:
* Themes live in `sp_theme/<name>/` (`template.php`, `widget.php`, `config.ini`).
* You can directly edit the source code.
* But, if a newer version is available, you must re-edit again.

Report any bugs here, and contribute :)

Thank you!
