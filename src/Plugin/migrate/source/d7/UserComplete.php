<?php

namespace Drupal\user_revision\Plugin\migrate\source\d7;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Row;
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
   * {@inheritdoc}
   */
  // public function getIds() {
  //   $ids['vid']['type'] = 'integer';
  //   $ids['vid']['alias'] = 'ur';
  //   return $ids;
  // }

  /**
   * The join options between the node and the node_revisions_table.
   */
  const JOIN = 'u.uid = ur.uid';

  /**
   * {@inheritdoc}
   */
  public function query() {
    // $query = parent::query();

    // Select node in its last revision.
    $query = $this->select('user_revision', 'ur')
      ->fields('u', [
        'uid',
        // 'type',
        // 'language',
        // 'status',
        'created',
        'changed',
        'login',
        // 'promote',
        // 'sticky',
        // 'tnid',
        // 'translate',
      ])
      ->fields('ur', [
        'vid',
        // 'title',
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
    // $query->addField('n', 'uid', 'node_uid');
    // $query->addField('nr', 'uid', 'revision_uid');
    $query->innerJoin('users', 'u', static::JOIN);

    // // If the content_translation module is enabled, get the source langcode
    // // to fill the content_translation_source field.
    // if ($this->moduleHandler->moduleExists('content_translation')) {
    //   $query->leftJoin('node', 'nt', '[n].[tnid] = [nt].[nid]');
    //   $query->addField('nt', 'language', 'source_langcode');
    // }
    // $this->handleTranslations($query);

    // if (isset($this->configuration['node_type'])) {
    //   $query->condition('n.type', (array) $this->configuration['node_type'], 'IN');
    // }
    $query->condition('ur.uid', 0, '>');


    $query->orderBy('ur.vid');

    // Get any entity translation revision data.
    // if ($this->getDatabase()->schema()
    //   ->tableExists('entity_translation_revision')) {
    //   $query->leftJoin('entity_translation_revision', 'etr', '[nr].[nid] = [etr].[entity_id] AND [nr].[vid] = [etr].[revision_id]');
    //   $query->fields('etr', [
    //     'entity_type',
    //     'entity_id',
    //     'revision_id',
    //     'source',
    //     'translate',
    //   ]);
    //   $conditions = $query->orConditionGroup();
    //   $conditions->condition('etr.entity_type', 'node');
    //   $conditions->isNull('etr.entity_type');
    //   $query->condition($conditions);
    //   $query->addExpression("COALESCE([etr].[language], [n].[language])", 'language');
    //   $query->addField('etr', 'uid', 'etr_uid');
    //   $query->addField('etr', 'status', 'etr_status');
    //   $query->addField('etr', 'created', 'etr_created');
    //   $query->addField('etr', 'changed', 'etr_changed');

    //   $query->orderBy('etr.revision_id');
    //   $query->orderBy('etr.language');
    // }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Override properties when this is an entity translation revision. The tnid
    // will be set in d7_node source plugin to the value of 'nid'.
    // if ($row->getSourceProperty('etr_created')) {
    //   $row->setSourceProperty('vid', $row->getSourceProperty('revision_id'));
    //   $row->setSourceProperty('created', $row->getSourceProperty('etr_created'));
    //   $row->setSourceProperty('timestamp', $row->getSourceProperty('etr_changed'));
    //   $row->setSourceProperty('revision_uid', $row->getSourceProperty('etr_uid'));
    //   $row->setSourceProperty('source_langcode', $row->getSourceProperty('source'));
    // }
    return parent::prepareRow($row);
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
