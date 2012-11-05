<?php

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
