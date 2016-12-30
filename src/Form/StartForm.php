<?php

namespace Drupal\cam_invoice_demo\Form;

use Behat\Mink\Exception\Exception;
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
        // case is sensitive!
        'Software License costs' => t('Software License Costs'),
        'Travel Expenses' => t('Travel Expenses'),
        'Misc' => t('Misc')
      )
    );

    $form['invoice_number'] = array(
      '#title' => t('Invoice number'),
      '#type' => 'textfield',
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
    // Make sure there's an upload to process.
    $all_files = \Drupal::request()->files->get('files', array());
    if (empty($all_files['file'])) {
      $form_state->setError($form['file'], t('An invoice document is required'));
    }
    else {
      $validators = array();
      $destination = FALSE;
      $file = file_save_upload('file', $validators, $destination, 0);

      if ($file) {
        $form_state->setValue('file', $file);
      }
      elseif ($file === FALSE) {
        $form_state->setError($form['file'], t('Something is wrong with the file.'));
      }
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

    $business_key = time();
    $variables = array(
      'amount' => array(
        'value' => $form_state->getValue('amount'),
        'type' => 'Double'
      ),
      'creditor' => array(
        'value' => $form_state->getValue('creditor'),
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
      ),
      'invoiceNumber' => array(
        'value' => $form_state->getValue('invoice_number'),
        'type' => 'String'
      )
    );

    $config = \Drupal::config('cam_invoice_demo.settings');
    $proc_id = $config->get('invoice_process_id');

    $process_definition_service = \Drupal::service('camunda_bpm_api.process_definition');
    $data = $process_definition_service->submitStartForm($proc_id, $variables, $business_key);
    if (!empty($data)) {
      drupal_set_message(t('The invoice process has been started.', array(
        '@procInstId' => $data['id']
      )));

      $module_handler = \Drupal::service('module_handler');
      if ($module_handler->moduleExists('camunda_bpm_browser')){
        // display the process instance within Drupal context
        $form_state->setRedirect('camunda_bpm_browser.process_instance_details', array(
          'procInstId' => $data['id']
        ));
      }
      else {
        drupal_set_message(t('Check the <a href="http://localhost:8080/camunda/app/cockpit/default/#process-instance/@procInstId">process instance</a> in Cockpit', array(
          '@procInstId' => $data['id']
        )));
      }
    }
    else {
      drupal_set_message(t('Something went wront with the invoice submission.'), 'error');
    }
  }
}