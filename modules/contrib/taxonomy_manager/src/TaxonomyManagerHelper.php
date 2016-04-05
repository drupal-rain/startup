<?php

namespace Drupal\taxonomy_manager;

use Drupal\Core\Language\LanguageInterface;

class TaxonomyManagerHelper {
  /**
   * checks if voc has terms
   *
   * @param $vid voc id
   * @return true, if terms already exists, else false
   */
  public static function _taxonomy_manager_voc_is_empty($vid) {
    $has_rows = (bool) db_query_range("SELECT 1 FROM {taxonomy_term_data} t INNER JOIN {taxonomy_term_hierarchy} h ON t.tid = h.tid WHERE vid = :vid AND h.parent = 0", 0, 1, array(':vid' => $vid))->fetchField();
    return !$has_rows;
  }

  /**
   * Helper function for mass adding of terms.
   *
   * @param $input
   *   The textual input with terms. Each line contains a single term. Child term
   *   can be prefixed with a dash '-' (one dash for each level). Term names
   *   starting with a dash and should not become a child term need to be wrapped
   *   in quotes.
   * @param $vid
   *   The vocabulary id.
   * @param int $parents
   *   An array of parent term ids for the new inserted terms. Can be 0.
   * @param $term_names_too_long
   *   Return value that is used to indicate that some term names were too long
   *   and truncated to 255 characters.
   *
   * @return An array of the newly inserted term objects
   */
  public static function mass_add_terms($input, $vid, $parents, &$term_names_too_long = array()) {
    $new_terms = array();
    $terms = explode("\n", str_replace("\r", '', $input));
    $parents = count($parents) ? $parents : 0;

    // Stores the current lineage of newly inserted terms.
    $last_parents = array();
    foreach ($terms as $name) {
      if (empty($name)) {
        continue;
      }
      $matches = array();
      // Child term prefixed with one or more dashes
      if (preg_match('/^(-){1,}/', $name, $matches)) {
        $depth = strlen($matches[0]);
        $name = substr($name, $depth);
        $current_parents = isset($last_parents[$depth-1]) ? array($last_parents[$depth-1]->id()) : 0;
      }
      // Parent term containing dashes at the beginning and is therefore wrapped
      // in double quotes
      elseif (preg_match('/^\"(-){1,}.*\"/', $name, $matches)) {
        $name = substr($name, 1, -1);
        $depth = 0;
        $current_parents = $parents;
      }
      else {
        $depth = 0;
        $current_parents = $parents;
      }
      // Truncate long string names that will cause database exceptions.
      if (strlen($name) > 255) {
        $term_names_too_long[] = $name;
        $name = substr($name, 0, 255);
      }

      $filter_formats = filter_formats();
      $format = array_pop($filter_formats);
      $values = [
        'name' => $name,
        'format' => $format->id(), // @todo do we need to set a format?
        'vid' => $vid,
        'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED, // @todo default language per vocabulary setting?
      ];
      if (!empty($current_parents)) {
        foreach ($current_parents as $p) {
          $values['parent'][] = array('target_id' => $p);
        }

      }
      $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->create($values);
      $term->save();
      $new_terms[] = $term;
      $last_parents[$depth] = $term;
    }
    return $new_terms;
  }

  /**
   * Helper function that deletes terms.
   * Optionally orphans (terms where parent get deleted) can be deleted as well
   *
   * Difference to core: deletion of orphans optional
   *
   * @param $tids array of term ids to delete
   * @param $delete_orphans If TRUE, orphans get deleted
   */
  public static function delete_terms($tids, $delete_orphans = FALSE) {
    $deleted_terms = array();
    $remaining_child_terms = array();

    while (count($tids) > 0) {
      $orphans = array();
      foreach ($tids as $tid) {
        $children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($tid);
        if (!empty($children)) {
          foreach ($children as $child) {
            $parents = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($child->id());
            if ($delete_orphans) {
              if (count($parents) == 1) {
                $orphans[$child->tid] = $child->id();
              }
              else {
                $remaining_child_terms[$child->id()] = $child->getName();
              }
            }
            else {
              $remaining_child_terms[$child->id()] = $child->getName();
              if ($parents) {
                // Parents structure see TermStorage::updateTermHierarchy
                $parents_array = array();
                foreach ($parents as $parent) {
                  if ($parent->id() != $tid) {
                    $parent->target_id = $parent->id();
                    $parents_array[$parent->id()] = $parent;
                  }
                }
                if (empty($parents_array)) {
                  $parents_array = array(0);
                }
                $child->parent = $parents_array;
                \Drupal::entityTypeManager()->getStorage('taxonomy_term')->deleteTermHierarchy(array($child->id()));
                \Drupal::entityTypeManager()->getStorage('taxonomy_term')->updateTermHierarchy($child);
              }
            }
          }
        }
        $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($tid);
        if ($term) {
          $deleted_terms[] = $term->getName();
          $term->delete();
        }
      }
      $tids = $orphans;
    }
    return array('deleted_terms' => $deleted_terms, 'remaining_child_terms' => $remaining_child_terms);
  }

  /**
   * Returns html markup for (un)select all checkboxes buttons.
   * @return string
   */
  public static function _taxonomy_manager_select_all_helpers_markup() {
    return '<span class="taxonomy-manager-select-helpers">' .
    '<span class="select-all-children" title="' . t("Select all") . '">&nbsp;&nbsp;&nbsp;&nbsp;</span>' .
    '<span class="deselect-all-children" title="' . t("Remove selection") . '">&nbsp;&nbsp;&nbsp;&nbsp;</span>' .
    '</span>';
  }

}
