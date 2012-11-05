<?php

abstract class DivinerRenderer {

  private $projectConfiguration;
  private $context = array();

  public function pushContext($context) {
    $this->context[] = $context;
  }

  public function popContext() {
    array_pop($this->context);
  }

  public function peekContext() {
    return end($this->context);
  }

  public function setProjectConfiguration(
    DivinerProjectConfiguration $project_configuration) {
    $this->projectConfiguration = $project_configuration;
    return $this;
  }

  public function getProjectConfiguration() {
    return $this->projectConfiguration;
  }

}
