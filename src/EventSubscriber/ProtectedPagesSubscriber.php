<?php

namespace Drupal\protected_pages\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\protected_pages\ProtectedPagesStorage;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Url;

/**
 * Redirects user to protected page login screen.
 */
class ProtectedPagesSubscriber implements EventSubscriberInterface {

  /**
   * Alias Manager Service.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Protected Pages Storage service.
   *
   * @var \Drupal\protected_pages\ProtectedPagesStorage
   */
  protected $protectedPagesStorage;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Page Cache Kill Switch Service.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\ResponsePolicyInterface
   */
  protected $killSwitch;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   For getting the alias manager service.
   * @param \Drupal\protected_pages\ProtectedPagesStorage $protected_pages_storage
   *   For getting the protected_pages storage service.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   * @param \Drupal\Core\PageCache\ResponsePolicy\ResponsePolicyInterface $kill_switch
   *   For getting the page cache kill switch service.
   */
  public function __construct(AccountInterface $current_user, AliasManagerInterface $alias_manager, ProtectedPagesStorage $protected_pages_storage, CurrentPathStack $current_path, RequestStack $request_stack, ResponsePolicyInterface $kill_switch) {
    $this->currentUser = $current_user;
    $this->aliasManager = $alias_manager;
    $this->protectedPagesStorage = $protected_pages_storage;
    $this->currentPath = $current_path;
    $this->requestStack = $request_stack;
    $this->killSwitch = $kill_switch;
  }

  /**
   * Redirects user to protected page login screen.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function checkProtectedPage(FilterResponseEvent $event) {
    if ($this->currentUser->hasPermission('bypass pages password protection')) {
      return;
    }

    $current_path = Unicode::strtolower($this->aliasManager->getAliasByPath($this->currentPath->getPath()));
    $normal_path = Unicode::strtolower($this->aliasManager->getPathByAlias($current_path));
    $pid = $this->protectedPagesIsPageLocked($current_path, $normal_path);

    if (!$pid) {
      $page_node = $this->requestStack->attributes->get('node');
      if (is_object($page_node)) {
        $nid = $page_node->id();
        if (isset($nid) && is_numeric($nid)) {
          $path_to_node = '/node/' . $nid;
          $current_path = Unicode::strtolower($this->aliasManager->getAliasByPath($path_to_node));
          $normal_path = Unicode::strtolower($this->aliasManager->getPathByAlias($current_path));
          $pid = $this->protectedPagesIsPageLocked($current_path, $normal_path);
        }
      }
    }

    if ($pid) {
      $this->killSwitch->trigger();
      $query = \Drupal::destination()->getAsArray();
      $query['protected_page'] = $pid;
      $response = new RedirectResponse(Url::fromUri('internal:/protected-page', ['query' => $query])
        ->toString());
      $response->send();
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['checkProtectedPage'];
    return $events;
  }

  /**
   * Returns protected page id.
   *
   * @param string $current_path
   *   Current path alias.
   * @param string $normal_path
   *   Current normal path.
   *
   * @return int
   *   The protected page id.
   */
  public function protectedPagesIsPageLocked($current_path, $normal_path) {
    $fields = ['pid'];
    $conditions = [];
    $conditions['or'][] = [
      'field' => 'path',
      'value' => $normal_path,
      'operator' => '=',
    ];
    $conditions['or'][] = [
      'field' => 'path',
      'value' => $current_path,
      'operator' => '=',
    ];
    $pid = $this->protectedPagesStorage->loadProtectedPage($fields, $conditions, TRUE);

    if (isset($_SESSION['_protected_page']['passwords'][$pid]['expire_time'])) {
      if (time() >= $_SESSION['_protected_page']['passwords'][$pid]['expire_time']) {
        unset($_SESSION['_protected_page']['passwords'][$pid]['request_time']);
        unset($_SESSION['_protected_page']['passwords'][$pid]['expire_time']);
      }
    }
    if (isset($_SESSION['_protected_page']['passwords'][$pid]['request_time'])) {
      return FALSE;
    }
    return $pid;
  }

}
