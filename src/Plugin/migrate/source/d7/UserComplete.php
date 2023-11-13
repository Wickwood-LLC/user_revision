<?php

namespace Drupal\user_revision\Plugin\migrate\source\d7;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\user\Plugin\migrate\source\d7\User;

/**
 * Drupal 7 all user revisions source, including translation revisions.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d7_user_complete",
 *   source_module = "user_revision"
 * )
 */
class UserComplete extends User {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    // Use all the user fields plus the vid that identifies the version.
    return parent::fields() + [
      'vid' => $this->t('The primary identifier for this version.'),
      'log' => $this->t('Revision Log message'),
      'timestamp' => $this->t('Revision timestamp'),
      'authorid' => $this->t('Revision User ID'),
    ];
  }

  /**
   * The join options between the node and the node_revisions_table.
   */
  const JOIN = 'u.uid = ur.uid';

  /**
   * {@inheritdoc}
   */
  public function query() {

    // Select node in its last revision.
    $query = $this->select('user_revision', 'ur')
      ->fields('u', [
        'uid',
        'created',
        'changed',
        'login',
      ])
      ->fields('ur', [
        'vid',
        'name',
        'log',
        'timestamp',
        'authorid',
        'mail',
        'status',
        'timezone',
        'language',
        'ip',
      ]);
    $query->innerJoin('users', 'u', static::JOIN);

    $query->condition('ur.uid', 0, '>');


    $query->orderBy('ur.vid');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'uid' => [
        'type' => 'integer',
        'alias' => 'u',
      ],
      'vid' => [
        'type' => 'integer',
        'alias' => 'ur',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function handleTranslations(SelectInterface $query) {}

}
