<?php

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

    $header = phutil_tag(
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

    return phutil_tag(
      'div',
      array(
        'class' => 'atom-doc',
      ),
      phutil_safe_html($header.$info.$body)); // TODO: This is cheating.
  }

  abstract protected function renderBody();

  protected function renderHeaderContent() {
    $atom = $this->getAtom();
    $renderer = $this->getRenderer();

    $type = $renderer->renderType($atom->getType());

    $name = $atom->getName();

    return hsprintf('%s %s', $type, $name);
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
