<?php

class DivinerRemarkupRuleSymbols
  extends PhutilRemarkupRule {

  private $renderer;

  public function setRenderer(DivinerRenderer $renderer) {
    $this->renderer = $renderer;
    return $this;
  }

  public function getRenderer() {
    return $this->renderer;
  }

  public function apply($text) {
    return preg_replace_callback(
      '/@{([\w@]+?):([^}]+?)}/',
      array($this, 'markupSymbol'),
      $text);
  }

  public function markupSymbol($matches) {
    $type = $matches[1];
    $name = $matches[2];

    // Collapse sequences of whitespace into a single space.
    $name = preg_replace('/\s+/', ' ', $name);

    $project = null;
    if (strpos($type, '@') !== false) {
      list($type, $project) = explode('@', $type, 2);
    }

    switch ($type) {
      case 'method':
        $context = $this->getEngine()->getConfig('diviner.context');
        if (strpos($name, '::') !== false) {
          list($class, $method) = explode('::', $name);
        } else if (strpos($name, '.') !== false) {
          $parts = explode('.', $name);
          $method = array_pop($parts);
          $class = implode('.', $parts);
        } else if ($context) {
          $method = $name;
          $class = $context->getName();
        } else {
          return $matches[0];
        }
        $suffix = '()';
        $type = 'class';
        return $this->getEngine()->storeText(
          $this->getRenderer()->renderAtomLinkRaw(
            $type,
            $class,
            $name.$suffix,
            'method/'.$method,
            $project));
      case 'function':
        $suffix = '()';
        break;
      default:
        $suffix = '';
        break;
    }

    return $this->getEngine()->storeText(
      $this->getRenderer()->renderAtomLinkRaw(
        $type,
        $name,
        $name.$suffix,
        $anchor = null,
        $project));
  }

}
