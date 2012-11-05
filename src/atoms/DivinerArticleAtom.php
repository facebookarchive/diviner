<?php

/**
 * @{class:DivinerAtom} representing an article (supplementary high-level
 * documentation not tied to source code).
 */
class DivinerArticleAtom extends DivinerAtom {

  public function getIsTopLevelAtom() {
    return true;
  }

  public function getType() {
    return self::TYPE_ARTICLE;
  }

  public function getChildren() {
    return array();
  }

}
