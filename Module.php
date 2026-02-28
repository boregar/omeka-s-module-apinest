<?php
namespace APINest;

use APINest\Form\ConfigForm;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Omeka\Module\AbstractModule;
use Omeka\Api\Representation\ItemRepresentation;
use Omeka\Logger;

class Module extends AbstractModule {

  protected $isNesting = false;

  public function setNesting($isNesting) {
    $this->isNesting = $isNesting;
  }

  public function getNesting() {
    return $this->$isNesting;
  }

  public function getConfig() {
    return include __DIR__ . '/config/module.config.php';
  }

  public function getConfigForm(PhpRenderer $renderer) {
    $services = $this->getServiceLocator();
    $settings = $services->get('Omeka\Settings');
    $form = new ConfigForm;
    $form->init();
    $form->setData([
      'apinest_allowed_apis' => $settings->get('apinest_allowed_apis'),
      'apinest_default_merge_key' => $settings->get('apinest_default_merge_key'),
    ]);
    return $renderer->formCollection($form, false);
  }

  public function handleConfigForm(AbstractController $controller) {
    $services = $this->getServiceLocator();
    $settings = $services->get('Omeka\Settings');
    $form = new ConfigForm;
    $form->init();
    $form->setData($controller->params()->fromPost());
    if (!$form->isValid()) {
      $controller->messenger()->addErrors($form->getMessages());
      return false;
    }
    $formData = $form->getData();
    $settings->set('apinest_allowed_apis', $formData['apinest_allowed_apis']);
    $settings->set('apinest_default_merge_key', $formData['apinest_default_merge_key']);
    return true;
  }

  public function attachListeners(SharedEventManagerInterface $sharedEventManager) {
    $sharedEventManager->attach('Omeka\Api\Representation\ItemRepresentation', 'rep.resource.json', [$this, 'hookRepResourceJSON']);
  }

  public function hookRepResourceJSON(Event $event) {

    $services = $this->getServiceLocator();
    $logger = $services->get('Omeka\Logger');
    // get the module settings
    $settings = $services->get('Omeka\Settings');
    // check if the current API is allowed
    $allowedAPIs = $settings->get('apinest_allowed_apis');
    $currentAPI = preg_match('/^\/api\/items[\?|\/].+$/', $_SERVER['REQUEST_URI']) ? 'REST' : 'PHP';
    if ((gettype($allowedAPIs) !== 'array') || !in_array($currentAPI, $allowedAPIs)) {
      $logger->notice(sprintf('APINest: requesting the %s API is not allowed (%s)', $currentAPI, $_SERVER['REQUEST_URI']));
      return;
    }
    // identify the requested linked data
    $nest = isset($_GET['nest']) ? explode(',', $_GET['nest']) : [];
    // empty request
    if (!count($nest)) {
      $logger->notice(sprintf('APINest: no requested linked data (%s)', $_SERVER['REQUEST_URI']));
      return;
    }
    // initialize the linked data array
    $jsonLd = $event->getParam('jsonLd');
    $linkedData = [];
    foreach ($nest as $key) {
      // check if the requested key is present in the original tree
      if (isset($jsonLd[$key])) {
        $linkedData[$key] = null;
      }
    }
    // no requested key found in the original tree
    if (!count($linkedData)) {
      $logger->notice(sprintf('APINest: could not find any requested key (%s)', $_SERVER['REQUEST_URI']));
      return;
    }
    // get the merge mode: 0 --> add the linked data to a the "apinest" key, 1 --> merge the linked data with the existing key
    $merge = isset($_GET['merge']) ? ($_GET['merge'] === '1') : $settings->get('apinest_default_merge_key');
    // get the version of the module
    $version = $services->get('Omeka\ModuleManager')->getModule('APINest')->getIni('version');
    // get the item representation
    $item = $event->getTarget();
    $jsonLd['o:apinest'] = ['version' => $version];
    // get the linked data and add its identifier in a "o:id" key as it is not returned by the getJsonLd() method
    foreach ($linkedData as $key => $data) {
      switch ($key) {
        case 'o:item_set':
          $linkedData[$key] = [];
          foreach ($item->itemSets() as $itemSet) {
            $linkedData[$key][] = array_merge(['o:id' => $itemSet->id()], $itemSet->getJsonLd());
          }
          break;
        case 'o:media':
          $linkedData[$key] = [];
          foreach ($item->media() as $media) {
            $linkedData[$key][] = array_merge(['o:id' => $media->id()], $media->getJsonLd());
          }
          break;
        case 'o:owner':
          if ($owner = $item->owner()) {
            $linkedData[$key] = array_merge(['o:id' => $owner->id()], $owner->getJsonLd());
          }
          break;
        case 'o:primary_media':
          if ($primaryMedia = $item->primaryMedia()) {
            $linkedData[$key] = array_merge(['o:id' => $primaryMedia->id()], $primaryMedia->getJsonLd());
          }
          break;
        case 'o:resource_class':
          if ($resourceClass = $item->resourceClass()) {
            $linkedData[$key] = array_merge(['o:id' => $resourceClass->id()], $resourceClass->getJsonLd());
          }
          break;
        case 'o:resource_template':
          if ($resourceTemplate = $item->resourceTemplate()) {
            $linkedData[$key] = array_merge(['o:id' => $resourceTemplate->id()], $resourceTemplate->getJsonLd());
          }
          break;
        case 'o:site':
          $linkedData[$key] = [];
          foreach ($item->sites() as $site) {
            $linkedData[$key][] = array_merge(['o:id' => $site->id()], $site->getJsonLd());
          }
          break;
        default:
          // if the key is a property of the item, the following is useless because the properties are already present in the tree --> comment
          /*
          if ($values = $item->value($key, ['all' => true])) {
            $linkedData[$key] = [];
            foreach ($values as $value) {
              $linkedData[$key][] = $value->jsonSerialize();
            }
          }
          */
      }
    }
    // add the linked data to the original tree
    foreach ($linkedData as $key => $data) {
      // skip keys with no data
      if ($data) {
        // the linked data is merged with the subtree of the corresponding key
        if ($merge) {
          //$jsonLd[$key] = array_merge($jsonLd[$key]->jsonSerialize(), $data);
          $jsonLd[$key] = $data;
        }
        // the linked data is added to the 'apinest' key
        else {
          $jsonLd['o:apinest'][$key] = $data;
        }
      }
    }
    // return the new nested tree
    $event->setParam('jsonLd', $jsonLd);
    $logger->notice(sprintf('APINest: processed item %s (%s)', $item->id(), $_SERVER['REQUEST_URI']));
  }

  public function install(ServiceLocatorInterface $serviceLocator) {
    $settings = $serviceLocator->get('Omeka\Settings');
    $settings->set('apinest_allowed_apis', ['REST']);
    $settings->set('apinest_default_merge_key', 1);
  }

  public function upgrade($oldVersion, $newVersion, ServiceLocatorInterface $serviceLocator) {
    $settings = $serviceLocator->get('Omeka\Settings');
    if (version_compare($oldVersion, '0.1.1', '<')) {
      $settings->delete('apinest_roles');
    }
    if (version_compare($oldVersion, '0.1.2', '<')) {
      $settings->delete('apinest_merge');
    }
    if (version_compare($oldVersion, '0.1.3', '<')) {
      $settings->set('apinest_allowed_apis', ['REST']);
      $settings->set('apinest_default_merge_key', 1);
    }
  }

  public function uninstall(ServiceLocatorInterface $serviceLocator) {
    $settings = $serviceLocator->get('Omeka\Settings');
    $settings->delete('apinest_allowed_apis');
    $settings->delete('apinest_default_merge_key');
  }
}
