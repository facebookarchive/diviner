#!/usr/bin/env php
<?php

/*
 * Copyright 2012 Facebook, Inc.
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
phutil_require_module('phutil', 'symbols');
PhutilErrorHandler::initialize();

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

foreach ($configuration->getConfig('phutil_libraries', array()) as $library) {
  if (Filesystem::pathExists($root.'/'.$library)) {
    phutil_load_library($root.'/'.$library);
  } else {
    phutil_load_library($library);
  }
}

$publisher = new DivinerPublisher($configuration);

$all_atoms = array();

$engines = $configuration->buildEngines();

if (!$engines) {
  throw new Exception(
    "No documentation engines are specified in your .divinerconfig.");
}

execx('mkdir -p %s', $root.'/.divinercache');

foreach ($engines as $engine) {
  $engine_name = get_class($engine);

  $files = $engine->buildFileContentHashes();

  $file_map = array();
  $cache_loc = array();
  $skipped = 0;
  foreach ($files as $file => $hash) {
    // Include the file path in the hash.
    $files[$file] = md5($file.$hash);

    if (preg_match('@^(externals|scripts)/@', $file)) {
      $skipped++;
      continue;
    }

    $cache_loc[$file] =
      $root.'/.divinercache/'.
      $files[$file].'.'.
      $engine_name;

    if (Filesystem::pathExists($cache_loc[$file])) {
      $data = Filesystem::readFile($cache_loc[$file]);
      $atoms = unserialize($data);
      if ($atoms) {
        $publisher->addAtoms($atoms);
        continue;
      }
    }

    $file_map[$file] = Filesystem::readFile(
      Filesystem::resolvePath($file, $root));
  }

  $n = number_format(count($files) - count($file_map) - $skipped);
  echo "[{$engine_name}] Found {$n} files in cache...\n";

  $n = number_format(count($file_map));
  echo "[{$engine_name}] Parsing documentation for {$n} files...";

  foreach (array_chunk($file_map, 32, true) as $file_map_chunk) {
    $engine->willParseFiles($file_map_chunk);

    foreach ($file_map_chunk as $file => $data) {
      if (strpos($data, '@undivinable') !== false) {
        $atom = new DivinerFileAtom();
        $atom->setName($file);
        $atom->setFile($file);
        $publisher->addAtoms(array($atom));
      } else {
        try {
          $atoms = $engine->parseFile($file, $data);
          Filesystem::writeFile($cache_loc[$file], serialize($atoms));
          $publisher->addAtoms($atoms);
        } catch (Exception $ex) {
          $atom = new DivinerFileAtom();
          $atom->setName($file);
          $atom->setFile($file);
          $publisher->addAtoms(array($atom));
        }
      }
    }
    echo ".";
  }
  echo "\n";
}

echo "Publishing documentation...\n";

$publisher->publish();

echo "Done.\n";
