<?php

namespace Drupal\user_revision\Plugin\migrate\destination;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\migrate\Plugin\migrate\destination\EntityContentComplete;

/**
 * Provides a destination for migrating the entire entity revision table.
 *
 * @MigrateDestination(
 *   id = "user_complete:user",
 * )
 */
class UserComplete extends EntityContentComplete {

  /**
   * {@inheritdoc}
   */
  protected function save(ContentEntityInterface $entity, array $old_destination_id_values = []) {
    parent::save($entity, $old_destination_id_values);
    return [
      $entity->id(),
      $entity->getRevisionId(),
      $entity->language()->getId(),
    ];
  }

}
