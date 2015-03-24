# Sedotpress
The new lightweight and low-resources blogging platform written in PHP. It is single-file Flat-file blogging engine. No database required! demo ( http://sedot.space )

## Installing:
* Put the index.php and .htaccess in your server
* Edit some configuration in index.php, save it
* create folder "comment" , make it writable
* create folder "data" , make it writable
* JOS :)


## Changelog

### v.0.1.1 beta
* builtin comment system (beta)
* minor style change

### v0.1.0 beta
(The current version is provide basic blogging features):
* Create/Edit a post
* Admin area (we call it backstage)
* Search a post
* Blog archives
* Tags
* Clean URL (permalink) / Url Rewrite
* Blog pagination

##Managing and Other:

### To enter the admin area (backstage)
* open (your_blog_url)/backstage
* Enter the code challenge
* Enter the admin username & password
* Fine :)

### Theme & Modification
* You can directly edit the source code, since this is just single file,
* you advised to hacking this..


### EXPERT GUIDE: To force rebuild index
* after a new post is created, it automatically generating a new index file, called "index.json" that located same in index.php
* you dont need to rebuild manually, but if you want to do some experiment, you can force rebuild it
* to force rebuild, open (your_blog_url)/build
* open your blog again
* Fine :)

Report bugs here, and contribute :)
