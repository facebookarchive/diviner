<?php

/**
 * @{class:DivinerAtom} representing a source file.
 */
class DivinerFileAtom extends DivinerAtom {

  private $children = array();

  public function getType() {
    return self::TYPE_FILE;
  }

  public function addChild(DivinerAtom $atom) {
    $this->children[] = $atom;
    return $this;
  }

  public function getIsTopLevelAtom() {
    return true;
  }

  public function getChildren() {
    return $this->children;
  }

}
