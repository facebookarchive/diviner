<?php

/**
 * Parse XHP (or PHP) source files into @{class:DivinerAtom}s.
 */
class DivinerXHPEngine extends DivinerEngine {

  private $trees;

  public function buildFileContentHashes() {
    $files = array();
    $root = $this->getConfiguration()->getProjectRoot();

    $finder = new FileFinder($root);
    $finder
      ->excludePath('*/.*')
      ->withSuffix('php')
      ->withType('f')
      ->setGenerateChecksums(true);

    foreach ($finder->find() as $path => $hash) {
      $path = Filesystem::readablePath($path, $root);
      $files[$path] = $hash;
    }

    return $files;
  }

  public function willParseFiles(array $file_map) {
    $futures = array();
    foreach ($file_map as $file => $data) {
      $futures[$file] = xhpast_get_parser_future($data);
    }

    foreach (Futures($futures)->limit(8) as $file => $future) {
      try {
        $this->trees[$file] = XHPASTTree::newFromDataAndResolvedExecFuture(
          $file_map[$file],
          $future->resolve());
      } catch (Exception $ex) {
        $this->trees[$file] = $ex;
      }
    }
  }

  public function parseFile($file, $data) {

    $tree = $this->trees[$file];
    if ($tree instanceof Exception) {
      throw $tree;
    }

    $root = $tree->getRootNode();

    $atoms = array();

    $func_decl = $root->selectDescendantsOfType('n_FUNCTION_DECLARATION');
    foreach ($func_decl as $func) {
      $name = $func->getChildByIndex(2);

      $atom = new DivinerFunctionAtom();
      $atom->setName($name->getConcreteString());
      $atom->setLine($func->getLineNumber());
      $atom->setFile($file);

      $this->findAtomDocblock($atom, $func);

      $params = $func
        ->getChildByIndex(3)
        ->selectDescendantsOfType('n_DECLARATION_PARAMETER');
      $this->parseParams($atom, $params);
      $this->parseReturnType($atom, $func);

      $atoms[] = $atom;
    }

    $class_types = array(
      'DivinerClassAtom' => 'n_CLASS_DECLARATION',
      'DivinerInterfaceAtom' => 'n_INTERFACE_DECLARATION',
    );
    foreach ($class_types as $atom_class => $type) {
      $class_decls = $root->selectDescendantsOfType($type);
      foreach ($class_decls as $class) {
        $name = $class->getChildByIndex(1);

        $atom = newv($atom_class, array());
        $atom->setName($name->getConcreteString());
        $atom->setLine($class->getLineNumber());
        $atom->setFile($file);

        $extends = $class->getChildByIndex(2);
        $extends_class = $extends->selectDescendantsOfType('n_CLASS_NAME');
        foreach ($extends_class as $parent_class) {
          $atom->addParentClass($parent_class->getConcreteString());
        }

        $this->findAtomDocblock($atom, $class);

        $methods = $class->selectDescendantsOfType('n_METHOD_DECLARATION');
        foreach ($methods as $method) {
          $matom = new DivinerMethodAtom();
          $this->findAtomDocblock($matom, $method);

          $attribute_list = $method->getChildByIndex(0);
          $attributes = $attribute_list->selectDescendantsOfType('n_STRING');
          if ($attributes) {
            foreach ($attributes as $attribute) {
              $matom->setAttribute(strtolower($attribute->getConcreteString()));
            }
          } else {
            $matom->setAttribute('public');
          }

          $params = $method
            ->getChildByIndex(3)
            ->selectDescendantsOfType('n_DECLARATION_PARAMETER');
          $this->parseParams($matom, $params);

          $matom->setName($method->getChildByIndex(2)->getConcreteString());
          $matom->setFile($file);
          $matom->setLine($method->getLineNumber());

          if ($matom->getName() == '__construct') {
            $matom->setReturnTypeAttributes(array(
              'doctype' => 'this',
            ));
          } else {
            $this->parseReturnType($matom, $method);
          }
          $atom->addMethod($matom);
        }

        $atoms[] = $atom;
      }
    }

    $file_atom = new DivinerFileAtom();
    $file_atom->setName($file);
    $file_atom->setFile($file);
    foreach ($atoms as $atom) {
      $file_atom->addChild($atom);
    }

    $this->trees[$file]->dispose();
    unset($this->trees[$file]);

    return array($file_atom);
  }


  private function findAtomDocblock(DivinerAtom $atom, XHPASTNode $node) {
    $token = $node->getDocblockToken();
    if ($token) {
      $atom->setRawDocblock($token->getValue());
      return true;
    } else {
      return false;
    }
  }

  private function parseParams(DivinerAtom $atom, AASTNodeList $params) {
    $metadata = $atom->getDocblockMetadata();
    $docs = idx($metadata, 'param', '');
    if ($docs) {
      $docs = explode("\n", $docs);
    }

    foreach ($params as $param) {
      $name = $param->getChildByIndex(1);
      $dict = array(
        'type'    => $param->getChildByIndex(0)->getConcreteString(),
        'default' => $param->getChildByIndex(2)->getConcreteString(),
      );
      if ($docs) {
        $doc = array_shift($docs);
        if ($doc) {
          $dict += $this->parseParamDoc($doc);
        }
      }
      $atom->addParameter($name->getConcreteString(), $dict);
    }

    // Add extra parameters retrieved by func_get_args().
    if ($docs) {
      foreach ($docs as $doc) {
        if ($doc) {
          $atom->addParameter('', $this->parseParamDoc($doc));
        }
      }
    }
  }

  private function parseReturnType(DivinerAtom $atom, XHPASTNode $decl) {
    $metadata = $atom->getDocblockMetadata();
    $return = idx($metadata, 'return');
    if ($return) {
      $split = preg_split('/\s+/', trim($return), $limit = 2);
      if (!empty($split[0])) {
        $type = $split[0];
      } else {
        $type = 'wild';
      }

      if ($decl->getChildByIndex(1)->getTypeName() == 'n_REFERENCE') {
        $type = $type.' &';
      }

      $docs = null;
      if (!empty($split[1])) {
        $docs = $split[1];
      }

      $dict = array(
        'doctype' => $type,
        'docs'    => $docs,
      );

      $atom->setReturnTypeAttributes($dict);
    }
  }


}
