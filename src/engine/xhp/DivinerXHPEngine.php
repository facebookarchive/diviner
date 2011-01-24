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
      $this->trees[$file] = XHPASTTree::newFromDataAndResolvedExecFuture(
        $file_map[$file],
        $future->resolve());
    }
  }

  public function parseFile($file, $data) {

    $tree = $this->trees[$file]->getRootNode();

    $atoms = array();

    $func_decl = $tree->selectDescendantsOfType('n_FUNCTION_DECLARATION');
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

    $class_decl = $tree->selectDescendantsOfType('n_CLASS_DECLARATION');
    foreach ($class_decl as $class) {
      $name = $class->getChildByIndex(1);

      $atom = new DivinerClassAtom();
      $atom->setName($name->getConcreteString());
      $atom->setLine($class->getLineNumber());
      $atom->setFile($file);

      $extends = $class->getChildByIndex(2);
      $extends_class = $extends->selectDescendantsOfType('n_CLASS_NAME');
      foreach ($extends_class as $parent_class) {
        $atom->addParentClass($parent_class->getConcreteString());
      }

      $this->findAtomDocblock($atom, $class);

      $methods = $class_decl->selectDescendantsOfType('n_METHOD_DECLARATION');
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

        $this->parseReturnType($matom, $method);
        $atom->addMethod($matom);
      }

      $atoms[] = $atom;
    }

    $file_atom = new DivinerFileAtom();
    $file_atom->setName($file);
    $file_atom->setFile($file);
    foreach ($atoms as $atom) {
      $file_atom->addChild($atom);
    }

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

  private function parseParams(DivinerAtom $atom, XHPASTNodeList $params) {
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
          $split = preg_split('/\s+/', trim($doc), $limit = 2);
          if (!empty($split[0])) {
            $dict['doctype'] = $split[0];
          }
          if (!empty($split[1])) {
            $dict['docs'] = $split[1];
          }
        }
      }
      $atom->addParameter($name->getConcreteString(), $dict);
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
