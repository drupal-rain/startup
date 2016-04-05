<?php

/**
 * @file
 * Contains \Drupal\taxonomy_manager\Form\AddTermsToVocabularyForm.
 */

namespace Drupal\taxonomy_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\taxonomy_manager\TaxonomyManagerHelper;

/**
 * Form for adding terms to a given vocabulary.
 */
class AddTermsToVocabularyForm extends FormBase {

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Drupal\taxonomy\VocabularyInterface $vocabulary
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state, VocabularyInterface $taxonomy_vocabulary = NULL, $parents = array()) {
    // Cache form state so that we keep the parents in the modal dialog.
    // For non modals (non POST request), form state caching on is not allowed.
    // @see FormState::setCached()
    if ($this->getRequest()->getMethod() == 'POST') {
      $form_state->setCached(TRUE);
    }

    $form['voc'] = array('#type' => 'value', '#value' => $taxonomy_vocabulary);
    $form['parents']['#tree'] = TRUE;
    foreach ($parents as $p) {
      $form['parents'][$p] = array('#type' => 'value', '#value' => $p);
    }

    $description = $this->t("If you have selected one or more terms in the tree view, the new terms are automatically children of those.");
    $form['help'] = array(
      '#markup' => $description,
    );

    $form['mass_add'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Terms'),
      '#description' => $this->t("One term per line. Child terms can be prefixed with a
        dash '-' (one dash per hierarchy level). Terms that should not become
        child terms and start with a dash need to be wrapped in double quotes.
        <br />Example:<br />
        animals<br />
        -canine<br />
        --dog<br />
        --wolf<br />
        -feline<br />
        --cat"),
      '#rows' => 10,
      '#required' => TRUE,
    );
    $form['add'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add'),
    );
    return $form;
  }

  /**
   * Submit handler for adding terms.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $term_names_too_long = array();
    $term_names = array();

    $taxonomy_vocabulary = $form_state->getValue('voc');
    $parents = $form_state->getValue('parents');
    $mass_terms = $form_state->getValue('mass_add');

    $new_terms = TaxonomyManagerHelper::mass_add_terms($mass_terms, $taxonomy_vocabulary->id(), $parents, $term_names_too_long);
    foreach ($new_terms as $term) {
      $term_names[] = $term->label();
    }

    if (count($term_names_too_long)) {
      drupal_set_message($this->t("Following term names were too long and truncated to 255 characters: %names.",
        array('%names' => implode(', ', $term_names_too_long))), 'warning');
    }
    drupal_set_message($this->t("Terms added: %terms", array('%terms' => implode(', ', $term_names))));
    $form_state->setRedirect('taxonomy_manager.admin_vocabulary', array('taxonomy_vocabulary' => $taxonomy_vocabulary->id()));
  }

  public function getFormId() {
    return 'taxonomy_manager.add_form';
  }

}
