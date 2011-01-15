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
 * @{class:DivinerAtom} representing a function.
 */
class DivinerFunctionAtom extends DivinerAtom {

  private $returnTypeAttributes = array();
  private $parameters = array();

  public function getType() {
    return self::TYPE_FUNCTION;
  }

  public function getIsTopLevelAtom() {
    return true;
  }

  public function getChildren() {
    return array();
  }

  public function setReturnTypeAttributes(array $dict) {
    $this->returnTypeAttributes = $dict;
    return $this;
  }

  public function getReturnTypeAttributes() {
    return $this->returnTypeAttributes;
  }

  public function getParameters() {
    return $this->parameters;
  }

  public function addParameter($name, $attributes = array()) {
    $this->parameters[$name] = $attributes;
    return $this;
  }


}
