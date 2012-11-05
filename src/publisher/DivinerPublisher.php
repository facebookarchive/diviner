<?php

final class DivinerPublisher {

  protected $configuration;
  protected $allAtoms;

  public function __construct(DivinerProjectConfiguration $configuration) {
    $this->configuration = $configuration;
    $this->allAtoms = array();
  }

  public function getConfiguration() {
    return $this->configuration;
  }

  public function addAtoms($atoms) {
    foreach ($atoms as $atom) {
      $this->allAtoms[] = $atom;
    }
    return $this;
  }

  public function getAllAtoms() {
    return $this->allAtoms;
  }

  public function publish() {
    $this->assertTopLevelAtomsAreUnique();
    return $this->generateViews();
  }

  private static function flattenAndGroupAtoms(array $atoms) {
    assert_instances_of($atoms, 'DivinerAtom');
    $atoms = self::flattenAtoms($atoms);
    $atoms = self::selectTopLevelAtoms($atoms);
    $atoms = mgroup($atoms, 'getType');
    return $atoms;
  }

  private static function flattenAtoms(array $atoms) {
    assert_instances_of($atoms, 'DivinerAtom');
    $all_atoms = array();
    foreach ($atoms as $atom) {
      $all_atoms[] = array($atom);
      $all_atoms[] = $atom->getAllChildren();
    }
    return array_mergev($all_atoms);
  }

  private static function selectTopLevelAtoms(array $atoms) {
    assert_instances_of($atoms, 'DivinerAtom');
    $result = array();
    foreach ($atoms as $atom) {
      if ($atom->getIsTopLevelAtom()) {
        $result[] = $atom;
      }
    }
    return $result;
  }

  private function assertTopLevelAtomsAreUnique() {
    $type_groups = self::flattenAndGroupAtoms($this->getAllAtoms());
    foreach ($type_groups as $type => $type_group) {
      $name_groups = mgroup($type_group, 'getName');
      foreach ($name_groups as $name => $name_group) {
        if (count($name_group) == 1) {
          continue;
        }
        $descs = array();
        foreach ($name_group as $atom) {
          $descs[] = $atom->getFile().':'.$atom->getLine();
        }
        // TODO: add undivinable flag or ignore list info
        throw new Exception(
          "There are ".count($name_group)." different definitions of the ".
          "{$type} {$name}: ".implode(', ', $descs).". Each {$type} MUST ".
          "have only one definition.");
      }
    }
  }

  protected function generateViews() {

    $atoms = $this->getAllAtoms();
    $atoms = self::flattenAtoms($atoms);
    $atoms = self::selectTopLevelAtoms($atoms);

    $views = array();

    foreach ($atoms as $atom) {
      switch ($atom->getType()) {
        case DivinerAtom::TYPE_CLASS:
        case DivinerAtom::TYPE_INTERFACE:
          $view = new DivinerClassAtomView($atom);
          break;
        case DivinerAtom::TYPE_ARTICLE:
          $view = new DivinerArticleAtomView($atom);
          break;
        case DivinerAtom::TYPE_FUNCTION:
          $view = new DivinerFunctionAtomView($atom);
          break;
        default:
          continue 2;
      }

      $views[] = $view->setKnownAtoms($atoms);
    }

    return $views;
  }

}
