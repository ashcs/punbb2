<?php

// Define the version and database revision that this code was written for
define('FORUM_VERSION', '1.4.4');
define('FORUM_DB_REVISION', 5);

// Define a few commonly used constants
define('FORUM_UNVERIFIED', 0);
define('FORUM_ADMIN', 1);
define('FORUM_GUEST', 2);

// Define avatars type
define('FORUM_AVATAR_NONE', 0);
define('FORUM_AVATAR_GIF', 1);
define('FORUM_AVATAR_JPG', 2);
define('FORUM_AVATAR_PNG', 3);

define('FORUM_SUBJECT_MAXIMUM_LENGTH', 70);
define('FORUM_DATABASE_QUERY_MAXIMUM_LENGTH', 140000);

define('FORUM_SEARCH_MIN_WORD', 3);
define('FORUM_SEARCH_MAX_WORD', 20);

define('FORUM_PUN_EXTENSION_REPOSITORY_URL', 'http://punbb.informer.com/extensions/1.4');

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
{
	define('FORUM_REQUEST_AJAX', 1);
}

// Format a time string according to $date_format, $time_format, and timezones
define('FORUM_FT_DATETIME', 0);
define('FORUM_FT_DATE', 1);
define('FORUM_FT_TIME', 2);

define('FORUM_SUPPORT_PCRE_UNICODE', 1);

define('FORUM_MAX_POSTSIZE_BYTES', 65535);