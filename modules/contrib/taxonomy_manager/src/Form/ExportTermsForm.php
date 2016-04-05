<?php

/**
 * @file
 * Contains \Drupal\taxonomy_manager\Form\ExportTermsForm.
 */

namespace Drupal\taxonomy_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\TermStorage;
use Drupal\taxonomy\VocabularyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for exporting given terms.
 */
class ExportTermsForm extends FormBase {

  /**
   * Term management.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * ExportTermsForm constructor.
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

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, VocabularyInterface $taxonomy_vocabulary = NULL, $selected_terms = array()) {
    if (empty($selected_terms)) {
      $form['info'] = array(
        '#markup' => $this->t('Please select the terms you want to export.'),
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
      '#title' => $this->t('Selected terms for export:'),
    );

    $form['download_csv'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Download CSV'),
    );

    $form['export'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Export'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $taxonomy_vocabulary = $form_state->getValue('voc');
    $selected_terms = $form_state->getValue('selected_terms');

//    $form_state->setRedirect('taxonomy_manager.admin_vocabulary', array('taxonomy_vocabulary' => $taxonomy_vocabulary->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_manager.export_form';
  }

}
