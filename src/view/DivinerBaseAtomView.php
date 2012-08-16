<?php

/*
 * Copyright 2012 Facebook, Inc.
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

abstract class DivinerBaseAtomView {

  protected $atom;
  protected $renderer;
  protected $docblockText;
  protected $docblockMeta;

  private $knownAtoms = array();

  final public function __construct(DivinerAtom $atom) {
    $this->atom = $atom;
  }

  final public function getAtom() {
    return $this->atom;
  }

  final public function setKnownAtoms(array $atoms) {
    assert_instances_of($atoms, 'DivinerAtom');
    $this->knownAtoms = array();
    foreach ($atoms as $atom) {
      $this->knownAtoms[$atom->getType()][$atom->getName()] = true;
    }
    return $this;
  }

  final protected function isKnownAtom($type, $name) {
    return isset($this->knownAtoms[$type][$name]);
  }

  final public function setRenderer(DivinerRenderer $renderer) {
    $this->renderer = $renderer;
    return $this;
  }

  final public function getRenderer() {
    return $this->renderer;
  }

  public function renderExcerpt() {
    $atom = $this->getAtom();
    $renderer = $this->getRenderer();

    $text = $this->getAtom()->getDocblockText();
    $matches = null;
    if (preg_match('/^.*?([.!?]+(?:\s)|$)/s', $text, $matches)) {
      $text = preg_replace('/\s+/', ' ', $matches[0]);
      return $renderer->markupTextInline($text);
    }
    return null;
  }

  public function renderView() {
    $renderer = $this->getRenderer();

    $header = phutil_render_tag(
      'h1',
      array(
        'class' => 'atom-name',
      ),
      $this->renderHeaderContent());
    $body = $this->renderBody();

    $info = $this->getAtomInfoDictionary();
    if ($info) {
      $info = $renderer->renderAtomInfoTable($info);
    } else {
      $info = null;
    }

    return phutil_render_tag(
      'div',
      array(
        'class' => 'atom-doc',
      ),
      $header.$info.$body);
  }

  abstract protected function renderBody();

  protected function renderHeaderContent() {
    $atom = $this->getAtom();
    $renderer = $this->getRenderer();

    $type = $renderer->renderType($atom->getType());

    $name = phutil_escape_html($atom->getName());

    return $type.' '.$name;
  }

  protected function getAtomInfoDictionary() {
    $renderer = $this->getRenderer();
    $atom = $this->getAtom();
    $dict = array();
    $dict['Defined'] = $renderer->renderFileAndLine(
      $atom->getFile(),
      $atom->getLine());
    $metadata = $atom->getDocblockMetadata();
    $group = idx($metadata, 'group');
    if ($group) {
      $dict['Group'] = $renderer->renderGroup($group);
    }

    return $dict;
  }

}
