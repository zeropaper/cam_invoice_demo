<?php

namespace Drupal\cam_invoice_demo\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class ModuleConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cam_invoice_demo_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cam_invoice_demo.settings'
    ];
  }

  private function fetchProcessDefinitions() {
    return \Drupal::service('camunda_bpm_api.process_definition')->getList();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('cam_invoice_demo.settings');

    $procdefs = $this->fetchProcessDefinitions();

    $options = array(
      '' => $this->t('None')
    );
    if (!empty($procdefs)) {
      foreach ($procdefs as $key => $value) {
        $options[$value['id']] = $value['name'] . ' v' . $value['version'] . '';
      }
    }
    $form['invoice_process_id'] = array(
      '#required' => TRUE,
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Process definition ID'),
      '#description' => $this->t('Select a process definition (probably "Invoice Receipt v2") which should be used.'),
      '#default_value' => $config->get('invoice_process_id')
    );


    return parent::buildForm($form, $form_state);
  }



  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $config = $this->config('cam_invoice_demo.settings');
    $config
      ->set('invoice_process_id', $values['invoice_process_id'])
      ->save();
  }
}