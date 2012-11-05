<?php

/**
 * Abstract representation of an entity (like a class or function) which
 * appears in source code.
 *
 * Each type documentable entity has a corresponding "atom" class, which stores
 * an abstract representation of parsed source code -- for instance,
 * @{class:DivinerClassAtom} has information like parent classes and methods,
 * while @{class:DivinerFunctionAtom} has information like function parameters.
 * All atoms store common information, like the file where the atom is declared
 * and the associated docblock.
 */
abstract class DivinerAtom {

  const TYPE_FUNCTION  = 'function';
  const TYPE_CLASS     = 'class';
  const TYPE_INTERFACE = 'interface';
  const TYPE_FILE      = 'file';
  const TYPE_ARTICLE   = 'article';
  const TYPE_METHOD    = 'method';

  private $file;
  private $line;
  private $type;
  private $name;
  private $isRoot;
  private $isTopLevelAtom;
  private $rawDocblock;
  private $docblockText;
  private $docblockMetadata;
  private $attributes = array();
  private $language;

  abstract public function getType();
  abstract public function getIsTopLevelAtom();
  abstract public function getChildren();

  public function getDocblockText() {
    if ($this->docblockText === null) {
      $this->parseDocblock();
    }
    return $this->docblockText;
  }

  public function getDocblockMetadata() {
    if ($this->docblockMetadata === null) {
      $this->parseDocblock();
    }
    return $this->docblockMetadata;
  }

  private function parseDocblock() {
    $parser = new PhutilDocblockParser();
    list($text, $meta) = $parser->parse($this->getRawDocblock());
    $this->docblockText = $text;
    $this->docblockMetadata = $meta;
  }

  public function setAttribute($attribute) {
    $this->attributes[$attribute] = true;
    return $this;
  }

  public function getAttributes() {
    return $this->sortAttributes(array_keys($this->attributes));
  }

  public function sortAttributes(array $attributes) {
    return $attributes;
  }

  public function setFile($file) {
    $this->file = $file;
    return $this;
  }

  public function getFile() {
    return $this->file;
  }

  public function setLine($line) {
    $this->line = $line;
    return $this;
  }

  public function getLine() {
    return $this->line;
  }

  public function setName($name) {
    $this->name = $name;
    return $this;
  }

  public function getName() {
    return $this->name;
  }

  public function setLanguage($language) {
    $this->language = $language;
    return $this;
  }

  public function getLanguage() {
    return $this->language;
  }

  public function setRawDocblock($raw_docblock) {
    $this->rawDocblock = $raw_docblock;
    return $this;
  }

  public function getRawDocblock() {
    return $this->rawDocblock;
  }

  public function getAllChildren() {
    $all = array();
    foreach ($this->getChildren() as $child) {
      $all[] = $child;
      foreach ($child->getAllChildren() as $more) {
        $all[] = $more;
      }
    }
    return $all;
  }

}
