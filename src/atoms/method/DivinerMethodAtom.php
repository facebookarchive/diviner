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
 * @{class:DivinerAtom} representing a method.
 */
class DivinerMethodAtom extends DivinerAtom {

  private $returnType = 'wild';
  private $parameters = array();

  public function getType() {
    return self::TYPE_METHOD;
  }

  public function getIsTopLevelAtom() {
    return false;
  }

  public function getChildren() {
    return array();
  }

  public function sortAttributes(array $attributes) {
    $attributes = array_fill_keys($attributes, true);
    $attributes = array_select_keys(
      $attributes,
      array(
        'final',
        'public',
        'private',
        'protected',
        'abstract',
        'static',
      )) + $attributes;
    return array_keys($attributes);
  }

  public function setReturnType($return_type) {
    $this->returnType = $return_type;
    return $this;
  }

  public function getReturnType() {
    return $this->returnType;
  }

  public function getParameters() {
    return $this->parameters;
  }

  public function addParameter($name, $attributes = array()) {
    $this->parameters[$name] = $attributes;
    return $this;
  }

}
