<?php

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
      $attributes[$key] = phutil_tag(
        'span',
        array(
          'class' => 'atom-attribute-'.$attribute,
        ),
        $attribute);
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
        $type = phutil_tag(
          'span',
          array(
            'class' => 'atom-parameter-type',
          ),
          hsprintf('%s ', $this->getInlineMarkupEngine()->markupText($type)));
      }
      $default = idx($dict, 'default');
      if (strlen($default)) {
        $default = phutil_tag(
          'span',
          array(
            'class' => 'atom-parameter-default',
          ),
          ' = '.$default);
      }
      $name = phutil_tag(
        'span',
        array(
          'class' => 'atom-parameter-name',
        ),
        $parameter);
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
    return phutil_tag(
      'span',
      array(
        'class' => 'atom-return-type',
      ),
      $this->getInlineMarkupEngine()->markupText($type));
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

  public function markupText($text, array $options = array()) {

    $engine = $this->getMarkupEngine();
    $text = $engine->markupText($text);

    $toc = null;
    if (!empty($options['toc'])) {
      $toc = PhutilRemarkupEngineRemarkupHeaderBlockRule::renderTableOfContents(
        $engine);
      if ($toc) {
        $toc = hsprintf(
          '<div class="doc-markup-toc">'.
            '<div class="doc-markup-toc-header">'.
              'Table of Contents'.
            '</div>'.
            '%s'.
          '</div>',
          $toc);
      }
    }

    return hsprintf(
      '<div class="doc-markup">%s%s</div>',
      $toc,
      $text);
  }

  public function markupTextInline($text) {
    return $this->getInlineMarkupEngine()->markupText($text);
  }

  public function getNormalizedName($name) {
    $name = preg_replace('#[\\\\ *:?|/_]+#', '_', $name);
    $name = rtrim($name, '_');
    return $name;
  }

  public function getAtomURI(DivinerAtom $atom) {

  }

  public function getTypeDisplayName($type) {
    return ucwords($type);
  }

  public function getTypeDisplayNamePlural($type) {
    return $this->getTypeDisplayName($type).'s';
  }

  protected function getMarkupEngine() {
    if (empty($this->engine)) {
      $engine = new PhutilRemarkupEngine();

      $engine->setConfig('pygments.enabled', true);
      $engine->setConfig(
        'uri.allowed-protocols',
        array(
          'http'  => true,
          'https' => true,
        ));
      $engine->setConfig('header.generate-toc', true);

      $rules = array();
      $rules[] = new PhutilRemarkupRuleEscapeRemarkup();
      $rules[] = new PhutilRemarkupRuleMonospace();
      $rules[] = new PhutilRemarkupRuleDocumentLink();
      $rules[] = new PhutilRemarkupRuleHyperlink();
      $rules[] = $this->buildSymbolRule();
      $rules[] = new PhutilRemarkupRuleBold();
      $rules[] = new PhutilRemarkupRuleItalic();
      $rules[] = new PhutilRemarkupRuleDel();

      $code_rules = array();
      $code_rules[] = new PhutilRemarkupRuleEscapeRemarkup();
      $code_rules[] = new PhutilRemarkupRuleDocumentLink();
      $code_rules[] = new PhutilRemarkupRuleHyperlink();

      $blocks = array();
      $blocks[] = new PhutilRemarkupEngineRemarkupQuotesBlockRule();
      $blocks[] = new PhutilRemarkupEngineRemarkupHeaderBlockRule();
      $blocks[] = new PhutilRemarkupEngineRemarkupNoteBlockRule();
      $blocks[] = new PhutilRemarkupEngineRemarkupListBlockRule();
      $blocks[] = new PhutilRemarkupEngineRemarkupCodeBlockRule();
      $blocks[] = new PhutilRemarkupEngineRemarkupTableBlockRule();
      $blocks[] = new PhutilRemarkupEngineRemarkupSimpleTableBlockRule();
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

    $this->engine->setConfig('diviner.context', $this->peekContext());

    if ($this->peekContext()) {
      $this->engine->setConfig(
        'phutil.codeblock.language-default',
        $this->peekContext()->getLanguage());
    }

    return $this->engine;
  }

  protected function getInlineMarkupEngine() {
    if (empty($this->inlineEngine)) {
      $engine = new PhutilRemarkupEngine();
      $rules = array();
      $rules[] = new PhutilRemarkupRuleEscapeRemarkup();
      $rules[] = new PhutilRemarkupRuleMonospace();
      $rules[] = $this->buildSymbolRule();
      $rules[] = new PhutilRemarkupRuleBold();
      $rules[] = new PhutilRemarkupRuleItalic();
      $rules[] = new PhutilRemarkupRuleDel();

      $blocks = array();
      $blocks[] = new PhutilRemarkupEngineRemarkupInlineBlockRule();

      foreach ($blocks as $block) {
        $block->setMarkupRules($rules);
      }

      $engine->setBlockRules($blocks);

      $this->inlineEngine = $engine;
    }

    $this->inlineEngine->setConfig('diviner.context', $this->peekContext());

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
    $anchor_name = $type.'/'.$name;

    return phutil_tag(
      'a',
      array(
        'href'  => '#'.$this->getNormalizedName($anchor_name),
        'class' => 'atom-symbol',
      ),
      $atom->getName().$suffix);
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
      $atom->getName().$suffix);
  }

  public function renderAtomAnchorTarget(DivinerAtom $atom) {
    $type = $atom->getType();
    $name = $atom->getName();
    $anchor_name = $type.'/'.$name;

    return phutil_tag(
      'a',
      array(
        'name' => $this->getNormalizedName($anchor_name),
      ),
      '');
  }


  public function renderAtomLinkRaw(
    $type,
    $name,
    $link_text = null,
    $anchor = null,
    $project = null) {

    if ($link_text === null) {
      $link_text = $name;
    }

    $base = $this->getBaseURI();

    $type = $this->getNormalizedName($type);
    $name = $this->getNormalizedName($name);
    if ($anchor) {
      $anchor = '#'.$this->getNormalizedName($anchor);
    }

    if ($project) {
      $base .= "../{$project}/";
    }

    return phutil_tag(
      'a',
      array(
        'href'  => "{$base}{$type}/{$name}.html{$anchor}",
        'class' => 'atom-symbol',
      ),
      $link_text);
  }

  public function renderType($type) {
    return phutil_tag(
      'span',
      array(
        'class' => 'atom-type',
      ),
      $this->getTypeDisplayName($type));
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
          '<td class="atom-param-type">'.$this->markupTextInline($type).'</td>'.
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
          '<td class="atom-param-type">'.$this->markupTextInline($type).'</td>'.
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
    return phutil_tag('span', array('class' => 'atom-group'), $name);
  }

  public function renderFileAndLine($file, $line) {

    $src_link = $this->getProjectConfiguration()->getConfig('src_link');
    if (!$src_link) {
      return phutil_escape_html($file.':'.$line);
    }

    return phutil_tag(
      'a',
      array(
        'href' => strtr($src_link, array(
          '%%' => '%',
          '%f' => phutil_escape_uri($file),
          '%l' => phutil_escape_uri($line),
        )),
        'target' => 'blank',
      ),
      $file.':'.$line);
  }

  public function renderAttributeNotice($type, $message) {
    return
      '<div class="atom-attribute-'.phutil_escape_html($type).'">'.
        phutil_escape_html($message).
      '</div>';
  }

}
