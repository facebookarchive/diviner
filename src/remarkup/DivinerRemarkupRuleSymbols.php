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
            phutil_escape_html($name).$suffix,
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
        phutil_escape_html($name).$suffix,
        $anchor = null,
        $project));
  }

}
