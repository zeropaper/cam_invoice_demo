<?php

namespace Drupal\cam_invoice_demo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


class StartForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'cam_invoice_demo_start';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['file'] = array(
      // '#required' => true,
      '#title' => t('Upload your invoice document'),
      '#type' => 'file'
    );

    $form['creditor'] = array(
      '#title' => t('Creditor'),
      '#type' => 'textfield',
      '#description' => t('(e.g. "Great Pizza for Everyone Inc.")')
    );

    $form['amount'] = array(
      '#title' => t('Amount'),
      '#type' => 'number',
      '#description' => t('(e.g. "30.00")')
    );

    $form['invoice_category'] = array(
      '#title' => t('Invoice category'),
      '#type' => 'select',
      '#options' => array(
        '' => '',
        'Software license costs' => t('Software license costs'),
        'Travel expenses' => t('Travel expenses'),
        'Misc' => t('Misc')
      )
    );

    $form['invoice_number'] = array(
      '#title' => t('Invoice number'),
      '#type' => 'text',
      '#description' => t('(e.g. "I-12345")')
    );

    $form['send'] = array(
      '#type' => 'submit',
      '#value' => t('Submit')
    );
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    $validators = array();
    $destination = FALSE;

    $file = file_save_upload('file', $validators, $destination, 0);
    if ($file) {
      $form_state->setValue('file', $file);
      drupal_set_message(t('File @filepath was uploaded.', array('@filepath' => $file->getFileUri())));
      drupal_set_message(t('File name is @filename.', array('@filename' => $file->getFilename())));
      drupal_set_message(t('File MIME type is @mimetype.', array('@mimetype' => $file->getMimeType())));
      drupal_set_message(t('You WIN!'));
    }
    elseif ($file === FALSE) {
      $form_state->setError($form['file']);
      drupal_set_message(t('Epic upload FAIL!'), 'error');
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $file = $form_state->getValue('file');
    $uri = $file->getFileUri();
    $filepath = \Drupal::service('file_system')->realpath($uri);

    $encodedFile = base64_encode(file_get_contents($filepath));

    $variables = array(
      'amount' => array(
        'value' => $form_state->getValue('amount'),
        'type' => 'Double'
      ),
      'creditor' => array(
        'value' => $form_state->getValue('creditor'),
        'type' => 'String'
      ),
      'invoiceNumber' => array(
        'value' => $form_state->getValue('invoice_number'),
        'type' => 'String'
      ),
      'invoiceCategory' => array(
        'value' => $form_state->getValue('invoice_category'),
        'type' => 'String'
      ),
      'invoiceDocument' => array(
        'value' => $encodedFile,
        'type' => 'File',
        'valueInfo' => array(
          'filename' => $file->getFilename(),
          'mimeType' => $file->getMimeType()
        )
      )
    );

    $config = \Drupal::config('camunda_bpm_api.settings');
    $proc_id = $config->get('node_view_start_process_id');
    \Drupal::service('camunda_bpm_api.process_definition')->submitStartForm($proc_id, $variables, time());
  }
}