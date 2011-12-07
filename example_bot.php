<?php

// require the wikimediabot class
require_once('mediawikibot.class.php');

// set some needed constants that will override the default constants
define('URL', 'http://localhost/discipleship');
define('USERNAME', 'wikibot');
define('PASSWORD', '@dmin22');

// initiate new verse bot
$bot = new MediaWikiBot();

// get the session
$bot->login();

// logout
var_dump($bot);
$bot->logout();