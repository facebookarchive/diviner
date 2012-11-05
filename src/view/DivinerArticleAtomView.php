<?php

class DivinerArticleAtomView extends DivinerBaseAtomView {
  protected function renderBody() {
    $atom = $this->getAtom();
    $renderer = $this->getRenderer();
    return $renderer->markupText(
      $atom->getDocblockText(),
      array(
        'toc' => true,
      ));
  }
}
