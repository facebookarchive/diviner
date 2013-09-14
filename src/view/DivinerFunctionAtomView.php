<?php

class DivinerFunctionAtomView extends DivinerBaseAtomView {
  protected function renderBody() {
    $atom = $this->getAtom();
    $renderer = $this->getRenderer();

    $atom = $this->getAtom();
    $renderer = $this->getRenderer();

    $attributes = $renderer->renderAttributes($atom->getAttributes());
    $return = $renderer->renderReturnTypeAttributes(
      $atom->getReturnTypeAttributes());
    $params = $renderer->renderParameters($atom->getParameters());
    return
      '<h3>'.
      $attributes.' '.
      $return.' '.
      phutil_escape_html($atom->getName()).'('.$params.')'.
      '</h3>'.
      $renderer->renderParameterTable(
        $atom->getParameters(),
        $atom->getReturnTypeAttributes()).
      $renderer->markupText($atom->getDocblockText());
  }

  protected function renderHeaderContent() {
    $atom = $this->getAtom();
    $renderer = $this->getRenderer();
    $type = $renderer->renderType($atom->getType());
    $name = $atom->getName().'()';
    return hsprintf('%s %s', $type, $name);
  }



}
