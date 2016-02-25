<?php

/**
 * @file
 * Contains \Drupal\protected_pages\Form\ProtectedPagesDeleteConfirmForm.
 */
namespace Drupal\protected_pages\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\protected_pages\ProtectedPagesStorage;

class ProtectedPagesDeleteConfirmForm extends ConfirmFormBase {

  /**
   * The protected page id
   *
   * @var int
   */
  protected $pid;

  /**
   * Returns the question to ask the user.
   *
   * @return string
   *   The form question. The page title will be set to this value.
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this page?');
  }

  /**
   * Returns the route to go to if the user cancels the action.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public function getCancelUrl() {
    return new Url('protected_pages_list');
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'protected_pages_delete_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete page');
  }

  /**
   * {@inheritdoc}
   *
   * @param int $pid
   *   (optional) The ID of the protected page to be deleted.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $pid = NULL) {
    $this->pid = $pid;
    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    ProtectedPagesStorage::deletePage($this->pid);
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}

