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

class DivinerDefaultRenderer extends DivinerRenderer {

  private $baseURI;

  public function setBaseURI($base_uri) {
    $this->baseURI = $base_uri;
    return $this;
  }

  public function getBaseURI() {
    return $this->baseURI;
  }

  public function renderAttributes($attributes) {
    foreach ($attributes as $key => $attribute) {
      $attributes[$key] = phutil_render_tag(
        'span',
        array(
          'class' => 'atom-attribute-'.$attribute,
        ),
        phutil_escape_html($attribute));
    }
    return
      '<span class="atom-attributes">'.
        implode(' ', $attributes).
      '</span>';
  }

  public function renderParameters($parameters) {
    foreach ($parameters as $parameter => $dict) {
      $type = idx($dict, 'type');
      if ($type) {
        $type = phutil_render_tag(
          'span',
          array(
            'class' => 'atom-parameter-type',
          ),
          phutil_escape_html($type.' '));
      }
      $default = idx($dict, 'default');
      if ($default) {
        $default = phutil_render_tag(
          'span',
          array(
            'class' => 'atom-parameter-default',
          ),
          phutil_escape_html(' = '.$default));
      }
      $name = phutil_render_tag(
        'span',
        array(
          'class' => 'atom-parameter-name',
        ),
        phutil_escape_html($parameter));
      $parameters[$parameter] = $type.$name.$default;
    }
    return
      '<span class="atom-parameters">'.
        implode(', ', $parameters).
      '</span>';
  }

  public function renderReturnTypeAttributes(array $attributes) {
    $type = nonempty(
      idx($attributes, 'type'),
      idx($attributes, 'doctype'));
    return phutil_render_tag(
      'span',
      array(
        'class' => 'atom-return-type',
      ),
      phutil_escape_html($type));
  }

  public function renderUndocumented($type) {
    return
      '<div class="atom-undocumented">'.
        "This ".phutil_escape_html($type)." is not documented.".
      '</div>';
  }

  public function renderAtomInfoTable($dict) {
    $rows = array();
    foreach ($dict as $key => $value) {
      $rows[] = '<tr><th>'.$key.'</th><td>'.$value.'</td></tr>';
    }
    return '<table class="atom-info">'.implode("\n", $rows).'</table>';
  }

  public function markupText($text) {
    return
      '<div class="doc-markup">'.
        $this->getMarkupEngine()->markupText($text).
      '</div>';;
  }

  public function markupTextInline($text) {
    return $this->getInlineMarkupEngine()->markupText($text);
  }

  public function getNormalizedName($name) {
    // TODO: We should encode any weird characters so they become valid
    // in filesystem paths and URIs.
    $name = str_replace(' ', '_', $name);
    return $name;
  }

  public function getAtomURI(DivinerAtom $atom) {

  }

  public function getTypeDisplayName($type) {
    return ucwords($type);
  }

  public function getTypeDisplayNamePlural($type) {
    return $this->getTypeDisplayName().'s';
  }

  protected function getMarkupEngine() {
    if (empty($this->engine)) {
      $engine = new PhutilRemarkupEngine();

      $rules = array();
      $rules[] = new PhutilRemarkupRuleEscapeRemarkup();
      $rules[] = new PhutilRemarkupRuleHyperlink();
      $rules[] = $this->buildSymbolRule();
      $rules[] = new PhutilRemarkupRuleEscapeHTML();
      $rules[] = new PhutilRemarkupRuleMonospace();
      $rules[] = new PhutilRemarkupRuleBold();
      $rules[] = new PhutilRemarkupRuleItalic();

      $code_rules = array();
      $code_rules[] = new PhutilRemarkupRuleEscapeRemarkup();
      $code_rules[] = new PhutilRemarkupRuleHyperlink();
      $code_rules[] = new PhutilRemarkupRuleEscapeHTML();

      $blocks = array();
      $blocks[] = new PhutilRemarkupEngineRemarkupHeaderBlockRule();
      $blocks[] = new PhutilRemarkupEngineRemarkupListBlockRule();
      $blocks[] = new PhutilRemarkupEngineRemarkupCodeBlockRule();
      $blocks[] = new PhutilRemarkupEngineRemarkupDefaultBlockRule();

      foreach ($blocks as $block) {
        if ($block instanceof PhutilRemarkupEngineRemarkupCodeBlockRule) {
          $block->setMarkupRules($code_rules);
        } else {
          $block->setMarkupRules($rules);
        }
      }

      $engine->setBlockRules($blocks);

      $this->engine = $engine;
    }
    return $this->engine;
  }

  protected function getInlineMarkupEngine() {
    if (empty($this->inlineEngine)) {
      $engine = new PhutilRemarkupEngine();
      $rules = array();
      $rules[] = new PhutilRemarkupRuleEscapeRemarkup();
      $rules[] = $this->buildSymbolRule();
      $rules[] = new PhutilRemarkupRuleEscapeHTML();
      $rules[] = new PhutilRemarkupRuleMonospace();
      $rules[] = new PhutilRemarkupRuleBold();
      $rules[] = new PhutilRemarkupRuleItalic();

      $blocks = array();
      $blocks[] = new PhutilRemarkupEngineRemarkupInlineBlockRule();

      foreach ($blocks as $block) {
        $block->setMarkupRules($rules);
      }

      $engine->setBlockRules($blocks);

      $this->inlineEngine = $engine;
    }
    return $this->inlineEngine;
  }

  private function buildSymbolRule() {
    $rule = new DivinerRemarkupRuleSymbols();
    $rule->setRenderer($this);
    return $rule;
  }
  
  public function renderAtomAnchor(DivinerAtom $atom) {
    $suffix = '';
    switch ($atom->getType()) {
      case 'method':
      case 'function':
        $suffix = '()';
        break;
    }
    
    $type = $atom->getType();
    $name = $atom->getName();
    
    $type = $this->getNormalizedName($type);
    $name = $this->getNormalizedName($name);
    $anchor_name = $type.'/'.$name;
    $link_text = phutil_escape_html($atom->getName().$suffix);
    
    return phutil_render_tag(
      'a',
      array(
        'href'  => '#'.$anchor_name,
        'class' => 'atom-symbol',
      ),
      $link_text);
  }

  public function renderAtomLink(DivinerAtom $atom) {
    $suffix = '';
    switch ($atom->getType()) {
      case 'method':
      case 'function':
        $suffix = '()';
        break;
    }
    
    return $this->renderAtomLinkRaw(
      $atom->getType(),
      $atom->getName(),
      phutil_escape_html($atom->getName().$suffix));
  }
  
  public function renderAtomAnchorTarget(DivinerAtom $atom) {
    $type = $atom->getType();
    $name = $atom->getName();
    
    $type = $this->getNormalizedName($type);
    $name = $this->getNormalizedName($name);
    $anchor_name = $type.'/'.$name;
    
    return phutil_render_tag(
      'a',
      array(
        'name' => $anchor_name,
      ),
      '');
  }
  

  public function renderAtomLinkRaw($type, $name, $link_text = null) {
    if ($link_text === null) {
      $link_text = phutil_escape_html($name);
    }

    $base = $this->getBaseURI();

    $type = $this->getNormalizedName($type);
    $name = $this->getNormalizedName($name);

    return phutil_render_tag(
      'a',
      array(
        'href'  => "{$base}{$type}/{$name}.html",
        'class' => 'atom-symbol',
      ),
      $link_text);
  }

  public function renderType($type) {
    return phutil_render_tag(
      'span',
      array(
        'class' => 'atom-type',
      ),
      phutil_escape_html($this->getTypeDisplayName($type)));
  }
  
  public function renderParameterTable(array $params, array $return) {
    $table = array();

    $param_header = 'parameters';
    foreach ($params as $param => $details) {
      $type = nonempty(
        idx($details, 'doctype'),
        idx($details, 'type'),
        'wild');
      $docs = idx($details, 'docs');
      $table[] =
        '<tr>'.
          '<td class="atom-param-table-group">'.$param_header.'</td>'.
          '<td class="atom-param-type">'.phutil_escape_html($type).'</td>'.
          '<td class="atom-param-name">'.phutil_escape_html($param).'</td>'.
          '<td>'.$this->markupTextInline($docs).'</td>'.
        '</tr>';
      $param_header = null;
    }
    
    $type = nonempty(
      idx($return, 'doctype'),
      idx($return, 'type'),
      'wild');
      
    $docs = idx($return, 'docs');

    $table[] =
        '<tr class="atom-param-table-return">'.
          '<td class="atom-param-table-group">return</td>'.
          '<td class="atom-param-type">'.phutil_escape_html($type).'</td>'.
          '<td class="atom-param-name"></td>'.
          '<td>'.$this->markupTextInline($docs).'</td>'.
        '</tr>';

    return
      '<table class="atom-param-table">'.
        implode("\n", $table).
       '</table>';
  }
  
  public function renderGroup($group) {
    $map = $this->getProjectConfiguration()->getConfig('groups', array());
    $map = $map + array(
      'radicals' => 'Free Radicals',
    );
    $name = idx($map, $group, $group);
    $name = phutil_escape_html($name);
    return '<span class="atom-group">'.$name.'</span>';
  }
  
  public function renderFileAndLine($file, $line) {
    
    $src_base = $this->getProjectConfiguration()->getConfig('src_base');
    if (!$src_base) {
      return phutil_escape_html($file.':'.$line);
    }
    
    return phutil_render_tag(
      'a',
      array(
        'href'    => $src_base.'/'.$file.'#L'.$line,
        'target'  => 'blank',
      ),
      phutil_escape_html($file.':'.$line));
  }

}
