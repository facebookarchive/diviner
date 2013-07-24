<?php

$include_path = ini_get('include_path');
$include_path .= PATH_SEPARATOR.dirname(__FILE__).'/../../';
ini_set('include_path', $include_path);
@include_once 'libphutil/src/__phutil_library_init__.php';
if (!@constant('__LIBPHUTIL__')) {
  echo "ERROR: Unable to load libphutil. Update your PHP 'include_path' to ".
       "include the parent directory of libphutil/.\n";
  exit(1);
}

require_once 'libphutil/scripts/__init_script__.php';

if (!ini_get('date.timezone')) {
  date_default_timezone_set('America/Los_Angeles');
}

phutil_load_library(dirname(__FILE__).'/../src/');
