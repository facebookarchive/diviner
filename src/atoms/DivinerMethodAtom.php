<?php

/**
 * @{class:DivinerAtom} representing a method.
 */
class DivinerMethodAtom extends DivinerAtom {

  private $returnTypeAttributes = array();
  private $parameters = array();

  public function getType() {
    return self::TYPE_METHOD;
  }

  public function getIsTopLevelAtom() {
    return false;
  }

  public function getChildren() {
    return array();
  }

  public function sortAttributes(array $attributes) {
    $attributes = array_fill_keys($attributes, true);
    $attributes = array_select_keys(
      $attributes,
      array(
        'final',
        'public',
        'private',
        'protected',
        'abstract',
        'static',
      )) + $attributes;
    return array_keys($attributes);
  }

  public function setReturnTypeAttributes(array $dict) {
    $this->returnTypeAttributes = $dict;
    return $this;
  }

  public function getReturnTypeAttributes() {
    return $this->returnTypeAttributes;
  }

  public function getParameters() {
    return $this->parameters;
  }

  public function addParameter($name, $attributes = array()) {
    $this->parameters[$name] = $attributes;
    return $this;
  }

}
