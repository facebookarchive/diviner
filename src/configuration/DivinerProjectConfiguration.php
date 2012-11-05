<?php

final class DivinerProjectConfiguration {

  private $params;
  private $root;

  public static function newFromDirectory($dir) {
    $dir = Filesystem::resolvePath($dir);
    $conf = $dir.'/.divinerconfig';
    if (!Filesystem::pathExists($conf)) {
      throw new Exception(
        "Required file '{$conf}' does not exist. Create a '.divinerconfig' ".
        "file to configure how project documentation is generated.");
    }

    $data = Filesystem::readFile($conf);
    $dict = json_decode($data, true);
    if (!is_array($dict)) {
      throw new Exception(
        "Parse error: config file '{$conf}' is not valid JSON.");
    }

    $configuration = new DivinerProjectConfiguration();

    if (empty($dict['name'])) {
      throw new Exception(
        "'.divinerconfig' file does not specify a 'name' for the project.");
    }

    $configuration->params = $dict;
    $configuration->root = $dir;

    return $configuration;
  }

  public function getProjectRoot() {
    return $this->root;
  }

  public function getProjectName() {
    return $this->getConfig('name');
  }

  public function getConfig($key, $default = null) {
    return idx($this->params, $key, $default);
  }

  public function buildEngines() {
    $engines = array();
    foreach ($this->getConfig('engines', array()) as $engine_config) {
      list($class, $config) = $engine_config;
      $object = newv($class, array($this, $config));
      $engines[] = $object;
    }
    return $engines;
  }

}
