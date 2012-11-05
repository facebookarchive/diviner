<?php

$include_path = ini_get('include_path');
ini_set('include_path', $include_path.':'.dirname(__FILE__).'/../../');
@require_once 'libphutil/src/__phutil_library_init__.php';
if (!@constant('__LIBPHUTIL__')) {
  echo "ERROR: Unable to load libphutil. Update your PHP 'include_path' to ".
       "include the parent directory of libphutil/.\n";
  exit(1);
}

if (!ini_get('date.timezone')) {
  date_default_timezone_set('America/Los_Angeles');
}

phutil_load_library(dirname(__FILE__).'/../src/');
