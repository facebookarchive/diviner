<?php

/*
 * Copyright 2012 Facebook, Inc.
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

final class DivinerViewContext {
  private $knownAtoms;

  public function __construct(array $atoms) {
    assert_instances_of($atoms, 'DivinerAtom');
    $this->knownAtoms = array();
    foreach ($atoms as $atom) {
      $this->knownAtoms[$atom->getType()][$atom->getName()] = true;
    }
    return $this;
  }

  public function isKnownAtom($type, $name) {
    return isset($this->knownAtoms[$type][$name]);
  }
}
