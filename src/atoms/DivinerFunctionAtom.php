<?php

/**
 * @{class:DivinerAtom} representing a function.
 */
class DivinerFunctionAtom extends DivinerAtom {

  private $returnTypeAttributes = array();
  private $parameters = array();

  public function getType() {
    return self::TYPE_FUNCTION;
  }

  public function getIsTopLevelAtom() {
    return true;
  }

  public function getChildren() {
    return array();
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
