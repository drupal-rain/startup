<?php

/**
 * @file
 * Contains \Drupal\taxonomy_manager\Form\DeleteTermsForm.
 */

namespace Drupal\taxonomy_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\TermStorage;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\taxonomy_manager\TaxonomyManagerHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for deleting given terms.
 */
class DeleteTermsForm extends FormBase {

  /**
   * The current request.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * DeleteTermsForm constructor.
   *
   * @param \Drupal\taxonomy\TermStorage $termStorage
   *    Object with convenient methods to manage terms.
   */
  public function __construct(TermStorage $termStorage) {
    $this->termStorage = $termStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('taxonomy_term')
    );
  }

  public function buildForm(array $form, FormStateInterface $form_state, VocabularyInterface $taxonomy_vocabulary = NULL, $selected_terms = array()) {
    if (empty($selected_terms)) {
      $form['info'] = array(
        '#markup' => $this->t('Please select the terms you want to delete.'),
      );
      return $form;
    }

    // Cache form state so that we keep the parents in the modal dialog.
    $form_state->setCached(TRUE);
    $form['voc'] = array('#type' => 'value', '#value' => $taxonomy_vocabulary);
    $form['selected_terms']['#tree'] = TRUE;

    $items = array();
    foreach ($selected_terms as $t) {
      $term = $this->termStorage->load($t);
      $items[] = $term->getName();
      $form['selected_terms'][$t] = array('#type' => 'value', '#value' => $t);
    }

    $form['terms'] = array(
      '#theme' => 'item_list',
      '#items' => $items,
      '#title' => $this->t('Selected terms for deletion:'),
    );

    $form['delete_orphans'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Delete children of selected terms, if there are any'),
    );

    $form['delete'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
    );
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $taxonomy_vocabulary = $form_state->getValue('voc');
    $selected_terms = $form_state->getValue('selected_terms');
    $delete_orphans = $form_state->getValue('delete_orphans');

    $info = TaxonomyManagerHelper::delete_terms($selected_terms, $delete_orphans);
    drupal_set_message(t("Deleted terms: %deleted_term_names", array('%deleted_term_names' => implode(', ', $info['deleted_terms']))));
    if (count($info['remaining_child_terms'])) {
      drupal_set_message(t("Remaining child terms with different parents: %remaining_child_term_names", array('%remaining_child_term_names' => implode(', ', $info['remaining_child_terms']))));
    }
    $form_state->setRedirect('taxonomy_manager.admin_vocabulary', array('taxonomy_vocabulary' => $taxonomy_vocabulary->id()));

  }

  public function getFormId() {
    return 'taxonomy_manager.delete_form';
  }
}
