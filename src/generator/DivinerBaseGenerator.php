<?php

abstract class DivinerBaseGenerator {

  private $projectConfiguration;
  private $renderer;

  public function setProjectConfiguration(DivinerProjectConfiguration $config) {
    $this->projectConfiguration = $config;
    return $this;
  }

  public function getProjectConfiguration() {
    return $this->projectConfiguration;
  }

  public function setRenderer($renderer) {
    $this->renderer = $renderer;
    return $this;
  }

  public function getRenderer() {
    return $this->renderer;
  }

  abstract public function generateDocumentation(array $views);

}
