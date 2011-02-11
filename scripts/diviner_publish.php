#!/usr/bin/env php
<?php

/*
 * Copyright 2011 Facebook, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once dirname(__FILE__).'/__init_script__.php';

ini_set('memory_limit', -1);

$args = array_slice($argv, 1);

$full_rebuild = false;
$update_remote = false;
$trace = false;

phutil_require_module('phutil', 'future/exec');

$args = array_values($args);
$alen = count($args);
for ($ii = 0; $ii < $alen; $ii++) {
  $arg = $args[$ii];
  if ($arg == '--') {
    unset($args[$ii]);
    break;
  } else if ($arg == '--no-cache') {
    $full_rebuild = true;
  } else if ($arg == '--publish') {
    $update_remote = true;
  } else if ($arg == '--trace') {
    $config_trace_mode = true;
    ExecFuture::pushEchoMode(true);
  } else {
    continue;
  }
  unset($args[$ii]);
}
$args = array_values($args);

if (count($args) != 1) {
  $self = basename($argv[0]);
  echo "usage: {$self} [--no-cache] [--publish] [--trace] project_directory\n";
  exit(1);
}

phutil_require_module('diviner', 'configuration');
phutil_require_module('diviner', 'publisher');

$configuration = DivinerProjectConfiguration::newFromDirectory($args[0]);
$root = Filesystem::resolvePath($args[0]);

$publisher = new DivinerPublisher($configuration);

$all_atoms = array();

$engines = $configuration->buildEngines();
foreach ($engines as $engine) {
  $files = $engine->buildFileContentHashes();

  $file_map = array();
  foreach ($files as $file => $hash) {
    if (preg_match('/^externals/', $file)) {
      continue;
    }
    
    $file_map[$file] = Filesystem::readFile(
      Filesystem::resolvePath($file, $root));
  }
  

  $n = number_format(count($file_map));
  $engine_name = get_class($engine);
  echo "[{$engine_name}] Generating documentation for {$n} files...";

  $engine->willParseFiles($file_map);

  foreach ($file_map as $file => $data) {
    if (strpos($data, '@undivinable') !== false) {
      $atom = new DivinerFileAtom();
      $atom->setName($file);
      $atom->setFile($file);
      $publisher->addAtoms(array($atom));
    } else {
      $atoms = $engine->parseFile($file, $data);
      $publisher->addAtoms($atoms);
    }
  }
  echo "\n";
}

$publisher->publish();
