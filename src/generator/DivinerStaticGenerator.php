<?php

class DivinerStaticGenerator extends DivinerBaseGenerator {

  public function generateDocumentation(array $views) {
    assert_instances_of($views, 'DivinerBaseAtomView');

    $renderer = new DivinerDefaultRenderer();
    $this->setRenderer($renderer);
    $configuration = $this->getProjectConfiguration();
    $renderer->setProjectConfiguration($configuration);

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
      $meta = $view->getAtom()->getDocblockMetadata();
      $group = idx($meta, 'group', 'radicals');
      $groups[$group][] = $view;
    }
    // Force radicals to the end.
    if (!empty($groups['radicals'])) {
      $radicals = $groups['radicals'];
      unset($groups['radicals']);
      $groups['radicals'] = $radicals;
    }

    $groups = array_select_keys(
      $groups,
      array_keys($configuration->getConfig('groups', array()))) + $groups;

    $renderer->setBaseURI('');
    $index = array();
    $index[] = $this->renderTableOfContents($groups);
    foreach ($groups as $name => $group) {
      $anchor = phutil_tag(
        'a',
        array(
          'name' => $renderer->getNormalizedName($name),
        ),
        '');
      $index[] = '<div class="atom-group-listing">';
      $index[] = '<h1>'.$anchor.$renderer->renderGroup($name).'</h1>';
      $index[] = $this->renderGroup($group);
      $index[] = '</div>';
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

  private function renderTableOfContents($groups) {
    $renderer = $this->getRenderer();

    $out = array();
    foreach ($groups as $name => $group) {
      $link = phutil_tag(
        'a',
        array(
          'href' => '#'.$renderer->getNormalizedName($name),
        ),
        $renderer->renderGroup($name));
      $out[] = '<li>'.$link.'</li>';
    }

    return
      '<div class="atom-toc">'.
        '<h1>Table of Contents</h1>'.
        '<ul>'.implode("\n", $out).'</ul>'.
      '</div>';

  }

  private function renderGroup($group) {
    $renderer = $this->getRenderer();

    $types = array();
    foreach ($group as $view) {
      $types[$view->getAtom()->getType()][] = $view;
    }

    // Reorder the types.
    $types = array_select_keys(
      $types,
      array('article', 'class', 'function')) + $types;

    $index = array();
    foreach ($types as $type => $views) {
      $ordered = array();
      foreach ($views as $view) {
        $ordered[$view->getAtom()->getName()] = $view;
      }
      ksort($ordered);
      $views = array_values($ordered);

      if ($type != 'article') {
        $index[] = phutil_tag(
          'h3',
          array(
          ),
          $this->renderTypeDisplayName($type));
      }
      if ($type == 'class') {
        $map = array(-1 => array());

        $local = array();
        foreach ($views as $view) {
          $atom = $view->getAtom();
          $local[$atom->getName()] = true;
        }

        foreach ($views as $view) {
          $atom = $view->getAtom();
          $extends = $atom->getParentClasses();
          $hit = false;
          foreach ($extends as $parent) {
            if (isset($local[$parent])) {
              $hit = true;
              break;
            }
          }
          if (!$hit) {
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
          $list[] = hsprintf(
            '<li>%s%s</li>',
            $renderer->renderAtomLink($view->getAtom()),
            $excerpt);
        }
      }
      $index[] = phutil_tag(
        'ul',
        array(
          'class' => 'atom-index',
        ),
        $list);
    }

    return
      '<div class="atom-group-contents">'.
        implode("\n", $index).
      '</div>';
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
    $crumbs[] = phutil_tag(
      'a',
      array(
        'href' => $dictionary['ROOT'].'index.html',
      ),
      $configuration->getProjectName());
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
    assert_instances_of($list, 'DivinerBaseAtomView');
    $renderer = $this->getRenderer();

    $out = array();
    foreach ($list as $view) {
      $atom = $view->getAtom();
      if (!empty($map[$atom->getName()])) {
        $content = $this->renderClassHierarchy($map, $map[$atom->getName()]);
        $content = phutil_tag(
          'ul',
          array(
          ),
          $content);
      } else {
        $content = null;
      }

      $out[] = hsprintf(
        '<li>%s%s%s</li>',
        $renderer->renderAtomLink($atom),
        $this->renderExcerpt($view),
        $content);
    }
    return $out;
  }

  private function renderExcerpt(DivinerBaseAtomView $view) {
    $excerpt = $view->renderExcerpt();
    if ($excerpt) {
      return hsprintf(
        '<span class="atom-excerpt"> &mdash; %s</span>',
        $excerpt);
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
