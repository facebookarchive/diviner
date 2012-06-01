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
    $name = phutil_escape_html($atom->getName().'()');
    return $type.' '.$name;
  }



}
