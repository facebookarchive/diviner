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

/**
 * Parse ".diviner" articles into @{class:DivinerArticleAtom}s.
 */
class DivinerArticleEngine extends DivinerEngine {

  public function buildFileContentHashes() {
    $files = array();
    $root = $this->getConfiguration()->getProjectRoot();

    $finder = new FileFinder($root);
    $finder
      ->excludePath('*/.*')
      ->withSuffix('diviner')
      ->withType('f')
      ->setGenerateChecksums(true);

    foreach ($finder->find() as $path => $hash) {
      $path = Filesystem::readablePath($path, $root);
      $files[$path] = $hash;
    }

    return $files;
  }

  public function willParseFiles(array $file_map) {
    return;
  }

  public function parseFile($file, $data) {

    $atom = new DivinerArticleAtom();
    $atom->setFile($file);
    $atom->setLine(1);

    $data = "/**\n".str_replace("\n", "\n * ", $data)."\n */";
    $atom->setRawDocblock($data);

    $parser = new PhutilDocblockParser();
    list($text, $meta) = $parser->parse($data);

    $name = idx($meta, 'title', 'Untitled Article "'.basename($file).'"');
    $atom->setName($name);

    return array($atom);
  }
}
