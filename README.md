[DISCONTINUED]

# Sedotpress
The new lightweight and low-resources blogging platform written in PHP. It is single-file Flat-file blogging engine. No database required! All data saved in JSON format.

## Features:
* Posts & comments saved in JSON format
* Lightweight & Low-Resource
* Portable
* URL Rewriting
* Built-In Comment System + Gravatar
* Auto Generate sitemap.xml & rss.xml
* etc ...

## Downloads:
https://github.com/sukualam/sedotpress/archive/master.zip

## Installing:
* Extract all files in the zip to server (you can place it in root "/" or subdirectory "blog/")
* Edit some config in index.php
* Chmod the sp_lang folder `chmod 777 -R sp_lang`
* Finish!

## URL Rewrites:
##### Lighttpd
* Add this line in /etc/lighttpd/lighttpd.conf
* `url.rewrite = ("^/(.*)\.(.+)$" => "$0","^/(.+)/?$" => "/index.php/$1")`

##### Apache
* Use the .htaccess

## Latest News:
* 8/5/2015: This script totaly beginning, so, dont expect too much like wordpress function, but this script still in development way, if you see any bugs or suggestions, i will happy if you report it here.
* To get the latest news and development status, visit my blog http://sedot.space

## Recent Changelog:
##### (for full changelog, read changelog.txt)

##Managing and Other:
##### To enter the admin area (backstage)
* Open `http://(your_blog_url)/backstage`

##### Theme & Modification:
* You can directly edit the source code, since this is just single file.
* But, if a newer version is available, you must re-edit again.

Report any bugs here, and contribute :)

Thank you!
