<?php

/**
 * @{class:DivinerAtom} representing a class.
 */
class DivinerClassAtom extends DivinerAtom {

  private $methods = array();
  private $parentClasses = array();

  public function getType() {
    return self::TYPE_CLASS;
  }

  public function addMethod(DivinerMethodAtom $atom) {
    $this->methods[] = $atom;
    return $this;
  }

  public function getMethods() {
    return $this->methods;
  }

  public function getIsTopLevelAtom() {
    return true;
  }

  public function getChildren() {
    return array_merge(
      $this->methods);
  }

  public function addParentClass($parent_class) {
    $this->parentClasses[] = $parent_class;
  }

  public function getParentClasses() {
    return $this->parentClasses;
  }

}
