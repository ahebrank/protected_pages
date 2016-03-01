<?php

/**
 * @file
 * Contains \Drupal\protected_pages\Controller\ProtectedPagesController.
 */

namespace Drupal\protected_pages\Controller;

use Drupal\protected_pages\ProtectedPagesStorage;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Html;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\RendererInterface;

/**
 * Controller for listing protected pages.
 */
class ProtectedPagesController extends ControllerBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a ProtectedPagesController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('renderer')
    );
  }

  /**
   * Generate the list of protected page.
   */
  public function protectedPagesList() {
    $content = array();

    $content['message'] = array(
      '#markup' => $this->t('List of password protected pages.'),
    );

    $rows = array();
    $headers = array(t('#'), t('Relative Path'), t('Operations'));
    $count = 1;

    foreach (ProtectedPagesStorage::load() as $page) {
      $operation_drop_button = array(
        array(
          '#type' => 'dropbutton',
          '#links' =>
          array(
            'edit-protected-page' => array(
              'title' => $this->t('Edit'),
              'url' => Url::fromUri('internal:/admin/config/system/protected_pages/' . $page->pid . '/edit')
            ),
            'delete-protected-page' => array(
              'title' => $this->t('Delete'),
              'url' => Url::fromUri('internal:/admin/config/system/protected_pages/' . $page->pid . '/delete')
            ),
            'send-email' => array(
              'title' => $this->t('Send E-mail'),
              'url' => Url::fromUri('internal:/admin/config/system/protected_pages/' . $page->pid . '/send_email')
            )
          )
        )
      );


      $operations = $this->renderer->render($operation_drop_button);
      $rows[] = array(
        'data' =>
        array(
          $count,
          Html::escape($page->path),
          $operations,
        ),
      );
      $count++;
    }
    $content['table'] = array(
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => t('No records available.'),
    );
    // Don't cache this page.
    $content['#cache']['max-age'] = 0;

    return $content;
  }

}
