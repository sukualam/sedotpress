# Sedotpress
The new lightweight and low-resources blogging platform written in PHP. It is single-file Flat-file blogging engine. No database required! demo ( http://sedot.space )

## Downloads:
Latest releases: https://github.com/sukualam/sedotpress/releases

## Installing:
* Copy "index.php", "data", "comment" in root directory "/"
* Edit some configuration in index.php, save it
* Make sure folder "data" & "comment" writable (you can chmod it)
* You ready to go
* (Just Note: if you place it on sub dir like "/blog", you also need edit .htaccess)


## Recent Changelog
### (for full changelog, read changelog.txt)

## Features
* Fast & lightweight
* Index caching
* Create/edit/delete a post
* Built-In Comment System
* Admin area (we call it backstage)
* Search a post
* Blog archives
* Tags
* Clean URL (permalink) / Url Rewrite
* Page Navigation
* RSS Feeds
* Sitemap.xml generator
* etc.. (in development..)

##Managing and Other:

### To enter the admin area (backstage)
* open http://(your_blog_url)/backstage
* Enter the code challenge
* Enter the admin username & password
* Fine :)

### Theme & Modification
* You can directly edit the source code, since this is just single file,
* but, if a newer version is available, you must re-edit again
* you advised to hacking this..


### EXPERT GUIDE:
#### To force rebuild index
* after a new post is created, it automatically saving a metadata of post, called "index.json" that located same in index.php
* you dont need to rebuild manually, but if you want to do some experiment, you can force rebuild it
* to force rebuild, open http://(your_blog_url)/build
* open your blog again
* Fine :)

Report bugs here, and contribute :)
