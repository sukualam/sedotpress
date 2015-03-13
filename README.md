# flatsingleblog
The new lightweight and low-resources blogging platform written in PHP. It is single-file Flat-file blogging engine. No database required!

## Version:

### v0.1 beta
The current version is provide basic blogging features:
* Create/Edit a post
* Admin area (we call it backstage)
* Search a post
* Blog archives
* Tags
* Clean URL (permalink) / Url Rewrite
* Blog pagination

Installing:
* Put the index.php and .htaccess in your server
* Edit the config parameter in index.php, save
* Create a directory called "data/" (no quote) that located same index.php
* Fine :)

##Managing and Other:

### To enter the admin area (backstage)
* open (your_blog_url)/backstage
* Enter the code challenge
* Enter the admin username & password
* Fine :)

### To force rebuild index
* after a new post is created, it automatically generating a new index file, called "index.json" that located same in index.php
* you dont need to rebuild manually, but if you want to do some experiment, you can force rebuild it
* to force rebuild, open (your_blog_url)/build
* open your blog again
* Fine :)
