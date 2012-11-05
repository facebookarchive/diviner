<?php

class DivinerClassAtomView extends DivinerBaseAtomView {
  protected function renderBody() {
    $atom = $this->getAtom();
    $renderer = $this->getRenderer();

    $renderer->pushContext($atom);
      $result =
        $this->renderAttributes().
        $renderer->markupText($atom->getDocblockText()).
        $this->renderTasks().
        $this->renderMethods();
    $renderer->popContext($atom);

    return $result;
  }

  protected function getAtomInfoDictionary() {
    $atom = $this->getAtom();
    $dict = array();
    $renderer = $this->getRenderer();

    if ($atom->getParentClasses()) {
      $extends = array();
      foreach ($atom->getParentClasses() as $class) {
        $extends[] = ($this->isKnownAtom(DivinerAtom::TYPE_CLASS, $class)
          ? $renderer->renderAtomLinkRaw('class', $class)
          : phutil_escape_html($class));
      }
      $dict['Extends'] = implode(', ', $extends);
    }

    return parent::getAtomInfoDictionary() + $dict;
  }

  protected function getDefinedTasks() {
    $metadata = $this->getAtom()->getDocblockMetadata();
    $tasks = idx($metadata, 'task');

    $map = array();
    if ($tasks) {
      foreach (explode("\n", $tasks) as $task) {
        $split = preg_split('/\s+/', $task, $limit = 2);
        if (isset($split[0])) {
          $map[$split[0]] = idx($split, 1, $split[0]);
        }
      }
    }
    $map['unspecified'] = 'Unspecified';

    return $map;
  }

  protected function renderTasks() {
    $atom = $this->getAtom();
    $renderer = $this->getRenderer();
    $methods = $atom->getMethods();
    $tasks = array();
    foreach ($methods as $method) {
      $metadata = $method->getDocblockMetadata();
      $task = idx($metadata, 'task', 'unspecified');
      $tasks[$task][] = $method;
    }
    $def = $this->getDefinedTasks();
    $tasks = array_select_keys($tasks, array_keys($def)) + $tasks;

    $out = array();
    foreach ($tasks as $task => $methods) {
      $task_name = idx($def, $task, $task);
      $out[] = '<h4>'.phutil_escape_html($task_name).'</h4>';
      $methods = msort($methods, 'getName');
      $out[] = '<ul>';
      foreach ($methods as $method) {
        $out[] =
          '<li>'.
            $renderer->renderAttributes($method->getAttributes()).' '.
            $renderer->renderAtomAnchor($method).
          '</li>';
      }
      $out[] = '</ul>';
    }

    return
      '<h2>Tasks</h2>'.
      '<div class="atom-task-list">'.
        implode("\n", $out).
      '</div>';
  }

  protected function renderAttributes() {
    $atom = $this->getAtom();
    $renderer = $this->getRenderer();
    $metadata = $atom->getDocblockMetadata();

    $attributes = array();
    if (idx($metadata, 'stable') !== null) {
      $attributes[] = $renderer->renderAttributeNotice(
        'stable',
        'This class is stable: you may safely extend it.');
    }

    return implode("\n", $attributes);
  }

  protected function renderMethods() {

    $renderer = $this->getRenderer();
    $atom = $this->getAtom();
    $methods = $atom->getMethods();
    $methods = msort($methods, 'getName');
    $markup = array();
    foreach ($methods as $method) {
      $attributes = $renderer->renderAttributes($method->getAttributes());
      $return = $renderer->renderReturnTypeAttributes(
        $method->getReturnTypeAttributes());
      $params = $renderer->renderParameters($method->getParameters());
      $markup[] =
        '<h3>'.
          $renderer->renderAtomAnchorTarget($method).
          $attributes.' '.
          $return.' '.
          phutil_escape_html($method->getName()).'('.$params.')'.
        '</h3>';
      $markup[] = $renderer->renderParameterTable(
        $method->getParameters(),
        $method->getReturnTypeAttributes());
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
