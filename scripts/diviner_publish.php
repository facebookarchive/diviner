#!/usr/bin/env php
<?php

require_once dirname(__FILE__).'/__init_script__.php';

ini_set('memory_limit', -1);

$args = new PhutilArgumentParser($argv);
$args->setTagline('multilanguage documentation generator');
$args->setSynopsis(<<<EOHELP
    **diviner** [__options__] __source_directory__
      Generate source documentation.

EOHELP
);
$args->parseStandardArguments();
$args->parse(
  array(
    array(
      'name'      => 'clean',
      'help'      => 'Ignore all caches.',
    ),
    array(
      'name'      => 'more',
      'wildcard'  => true,
    ),
  ));

$full_rebuild = $args->getArg('clean');

$more = $args->getArg('more');
if (count($more) !== 1) {
  $args->printHelpAndExit();
}
$source_dir = head($more);

$configuration = DivinerProjectConfiguration::newFromDirectory($source_dir);
$root = Filesystem::resolvePath($source_dir);

foreach ($configuration->getConfig('phutil_libraries', array()) as $library) {
  if (Filesystem::pathExists($root.'/'.$library)) {
    phutil_load_library($root.'/'.$library);
  } else {
    phutil_load_library($library);
  }
}

$publisher = new DivinerPublisher($configuration);

$engines = $configuration->buildEngines();

if (!$engines) {
  throw new Exception(
    "No documentation engines are specified in your .divinerconfig.");
}

Filesystem::createDirectory($root.'/.divinercache');

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

    if (!$full_rebuild && Filesystem::pathExists($cache_loc[$file])) {
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

  if (!$full_rebuild) {
    $n = number_format(count($files) - count($file_map) - $skipped);
    echo "[{$engine_name}] Found {$n} files in cache...\n";
  }

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

echo "Generating views...\n";

$views = $publisher->publish();

echo "Removing old documentation...\n";

Filesystem::remove($root.'/docs/');

echo "Publishing documentation...\n";

$generator = new DivinerStaticGenerator();
$generator->setProjectConfiguration($configuration);
$generator->generateDocumentation($views);

echo "Done.\n";
