# flatsingleblog
The new lightweight and low-resources blogging platform written in PHP. It is single-file Flat-file blogging engine. No database required!

The current version is provide basic blogging features:
* Create/Edit a post
* Admin area (we call it backstage)
* Search a post
* Blog archives
* Tags
* Clean URL (permalink) / Url Rewrite
* Blog pagination

Installing:
* Put the index.php in your server
* Edit the config parameter in index.php
* Make directory "data/" (no quote) where the index.php is located. (must same)
* Fine :)
* 

##Managing and Other:

### To enter the admin area (backstage)
* open (your_blog_url)/backstage
* Enter the code challenge
* Enter the admin username & password
* Fine :)

### To force rebuild index
* after a new post is created, it generating a new index, called "index.json" that located same in index.php
* to force rebuild, open (your_blog_url)/build
* open your blog again
* Fine :)
