<?php

/*
 * Copyright 2011 Facebook, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class DivinerClassAtomView extends DivinerBaseAtomView {
  protected function renderBody() {
    $atom = $this->getAtom();
    $renderer = $this->getRenderer();
    return
      $renderer->markupText($atom->getDocblockText()).
      $this->renderMethods();
  }

  protected function getAtomInfoDictionary() {
    $atom = $this->getAtom();
    $dict = array();
    $renderer = $this->getRenderer();

    if ($atom->getParentClasses()) {
      $extends = array();
      foreach ($atom->getParentClasses() as $class) {
        $extends[] = $renderer->renderAtomLinkRaw('class', $class);
      }
      $dict['Extends'] = implode(', ', $extends);
    }

    return parent::getAtomInfoDictionary() + $dict;
  }

  protected function renderMethods() {

    $renderer = $this->getRenderer();
    $atom = $this->getAtom();
    $methods = $atom->getMethods();
    $methods = msort($methods, 'getName');
    $markup = array();
    foreach ($methods as $method) {
      $attributes = $renderer->renderAttributes($method->getAttributes());
      $return = $renderer->renderReturnType($method->getReturnType());
      $params = $renderer->renderParameters($method->getParameters());
      $markup[] =
        '<h3>'.
          $attributes.' '.
          $return.' '.
          $method->getName().'('.$params.')'.
        '</h3>';
      if (strlen($method->getDocblockText())) {
        $markup[] = $renderer->markupText($method->getDocblockText());
      } else {
        $markup[] = $renderer->renderUndocumented('method');
      }
    }
    $markup = implode("\n", $markup);
    return '<h2>Methods</h2>'.$markup;
  }

}
