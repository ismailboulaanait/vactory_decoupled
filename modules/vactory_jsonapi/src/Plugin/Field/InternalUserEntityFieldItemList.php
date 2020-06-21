<?php

namespace Drupal\vactory_jsonapi\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\user\Entity\User;

/**
 * Defines a user list class for better normalization targeting.
 */
class InternalUserEntityFieldItemList extends FieldItemList
{

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue()
  {
    /** @var \Drupal\node\NodeInterface $node */
    $entity = $this->getEntity();
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    if (!in_array($entity_type, ['comment', 'node'])) {
      return;
    }

    $entityFieldManager = \Drupal::service('entity_field.manager');
    $fields = $entityFieldManager->getFieldDefinitions($entity_type, $bundle);

    $value = [];
    foreach ($fields as $name => $definition) {
      if ($definition->getType() !== 'entity_reference') {
        continue;
      }

      if ($definition->getSetting('target_type') !== 'user') {
        continue;
      }

      $uid = $entity->get($name)->getString();
      $user = User::load($uid);

      if (!$user) {
        continue;
      }

      // Process Image.
      $image_value = NULL;
      $image = $user->get('user_picture')->getValue();
      if (isset($image[0]['target_id']) && !empty($image[0]['target_id'])) {
        $fid = (int)$image[0]['target_id'];
        $file_entity = File::load($fid);
        $image_app_base_url = Url::fromUserInput('/app-image/')
          ->setAbsolute()->toString();
        $lqipImageStyle = ImageStyle::load('lqip');

        $uri = $file_entity->getFileUri();

        $image_value = [
          '_default' => file_create_url($uri),
          '_lqip' => $lqipImageStyle->buildUrl($uri),
          'uri' => StreamWrapperManager::getTarget($uri),
          'fid' => $file_entity->id(),
          'file_name' => $file_entity->label(),
          'base_url' => $image_app_base_url,
        ];
      }

      $value[$name] = [
        'id' => $user->id(),
        'name' => $user->getUsername(),
        'first_name' => $user->get('field_first_name')->getString(),
        'last_name' => $user->get('field_last_name')->getString(),
        'profession' => $user->get('field_profession')->getString(),
        'about' => $user->get('field_about')->getString(),
        'picture' => $image_value,
      ];
    }

    $this->list[0] = $this->createItem(0, $value);
  }
}
