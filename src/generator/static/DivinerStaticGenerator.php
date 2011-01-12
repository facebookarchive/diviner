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

class DivinerStaticGenerator extends DivinerBaseGenerator {

  public function generateDocumentation(array $views) {

    $renderer = new DivinerDefaultRenderer();
    $this->setRenderer($renderer);
    $configuration = $this->getProjectConfiguration();

    $root = $configuration->getProjectRoot().'/docs/';
    $name = $configuration->getProjectName();
    foreach ($views as $view) {
      $atom = $view->getAtom();
      $normal_name = $renderer->getNormalizedName($atom->getName());
      $normal_type = $renderer->getNormalizedName($atom->getType());

      $dir = "{$root}/{$normal_type}/";

      if (!Filesystem::pathExists($dir)) {
        Filesystem::createDirectory(
          $dir,
          $umask = 0755,
          $recursive = true);
      }

      $view->setRenderer($renderer);
      $renderer->setBaseURI('../');

      Filesystem::writeFile(
        "{$dir}/{$normal_name}.html",
        $this->renderView($view));
    }

    $groups = array();
    foreach ($views as $view) {
      $groups[$view->getAtom()->getType()][] = $view;
    }

    // Reorder the types.
    $groups = array_select_keys($groups, array('article')) + $groups;

    $renderer->setBaseURI('');

    $index = array();
    foreach ($groups as $type => $views) {

      $ordered = array();
      foreach ($views as $view) {
        $ordered[$view->getAtom()->getName()] = $view;
      }
      ksort($ordered);
      $views = array_values($ordered);

      $index[] = phutil_render_tag(
        'h1',
        array(
        ),
        $this->renderTypeDisplayName($type));
      if ($type == 'class') {
        $map = array(-1 => array());
        foreach ($views as $view) {
          $atom = $view->getAtom();
          $extends = $atom->getParentClasses();
          if (!$extends) {
            $extends = array(-1);
          }
          foreach ($extends as $parent) {
            $map[$parent][] = $view;
          }
        }
        $list = $this->renderClassHierarchy($map, $map[-1]);
      } else {
        $list = array();
        foreach ($views as $view) {
          $excerpt = $this->renderExcerpt($view);
          $list[] = phutil_render_tag(
            'li',
            array(),
            $renderer->renderAtomLink($view->getAtom()).$excerpt);
        }
      }
      $index[] = phutil_render_tag(
        'ul',
        array(
          'class' => 'atom-index',
        ),
        implode("\n", $list));
    }

    Filesystem::writeFile(
      $root.'/index.html',
      $this->renderTemplate(array(
        'TITLE'   => 'Project Index',
        'BODY'    => implode("\n", $index),
        'ROOT'    => '',
      )));

    $css = $root.'/css';
    if (!Filesystem::pathExists($css)) {
      Filesystem::createDirectory($css, $umask = 0755);
    }

    $sheets = array(
      'default_style.css' => 'diviner.css',
      'syntax.css'        => 'syntax.css',
    );

    foreach ($sheets as $sheet => $target) {
      $stylesheet = phutil_get_library_root('diviner').
                    '/../resources/css/'.
                    $sheet;
      Filesystem::writeFile(
        $css.'/'.$target,
        Filesystem::readFile($stylesheet));
    }
  }

  private function renderView(DivinerBaseAtomView $view) {

    $html = $view->renderView();

    $type = $view->getAtom()->getType();
    $name = $view->getAtom()->getName();

    $atom_name = $name.' ('.ucwords($type).')';
    $atom_name = phutil_escape_html($atom_name);

    $configuration = $this->getProjectConfiguration();
    $proj_name = $configuration->getProjectName();

    return $this->renderTemplate(
      array(
        'TITLE' => "\xE2\x97\x89 ".$atom_name.' | '.$proj_name,
        'BODY'  => $html,
        'ROOT'  => '../',
        'ATOM'  => $atom_name,
      ));
  }

  private function renderTemplate(array $dictionary) {
    $configuration = $this->getProjectConfiguration();

    $crumbs = array();
    $crumbs[] = phutil_render_tag(
      'a',
      array(
        'href' => $dictionary['ROOT'].'index.html',
      ),
      phutil_escape_html($configuration->getProjectName()));
    if (!empty($dictionary['ATOM'])) {
      $crumbs[] = '<a href="#">'.$dictionary['ATOM'].'</a>';
    }
    $crumbs = implode(' &raquo; ', $crumbs);

    $dictionary += array(
      'TITLE'   => null,
      'BODY'    => null,
      'CRUMBS'  => $crumbs,
      'ROOT'    => null,
    );

    $find = array();
    $repl = array();
    foreach ($dictionary as $k => $v) {
      $find[] = '{'.$k.'}';
      $repl[] = $v;
    }

    $root = phutil_get_library_root('diviner').'/../resources/html/';
    $tmpl = Filesystem::readFile($root.'/default_template.html');

    return str_replace($find, $repl, $tmpl);
  }

  private function renderClassHierarchy(array $map, array $list) {
    $renderer = $this->getRenderer();

    $out = array();
    foreach ($list as $view) {
      $atom = $view->getAtom();
      if (!empty($map[$atom->getName()])) {
        $content = $this->renderClassHierarchy($map, $map[$atom->getName()]);
        $content = phutil_render_tag(
          'ul',
          array(
          ),
          implode("\n", $content));
      } else {
        $content = null;
      }

      $excerpt = $this->renderExcerpt($view);

      $content = $renderer->renderAtomLink($atom).$excerpt.$content;

      $out[] = phutil_render_tag(
        'li',
        array(
        ),
        $content);
    }
    return $out;
  }

  private function renderExcerpt(DivinerBaseAtomView $view) {
    $excerpt = $view->renderExcerpt();
    if ($excerpt) {
      return phutil_render_tag(
        'span',
        array(
          'class' => 'atom-excerpt',
        ),
        ' &mdash; '.$excerpt);
    } else {
      return null;
    }
  }

  private function renderTypeDisplayName($type) {
    static $map = array(
      'class' => 'Classes',
    );
    if (isset($map[$type])) {
      return $map[$type];
    }
    return ucwords($type).'s';
  }

}
