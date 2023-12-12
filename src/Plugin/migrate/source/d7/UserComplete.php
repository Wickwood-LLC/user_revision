<?php

namespace Drupal\user_revision\Plugin\migrate\source\d7;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\user\Plugin\migrate\source\d7\User;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

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


  /**
   * {@inheritdoc}
   * It has only change from the parent version. To call getFieldValues() with user revision id passed.
   */
  public function prepareRow(Row $row) {
    $uid = $row->getSourceProperty('uid');
    $vid = $row->getSourceProperty('vid');

    $roles = $this->select('users_roles', 'ur')
      ->fields('ur', ['rid'])
      ->condition('ur.uid', $uid)
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('roles', $roles);

    $row->setSourceProperty('data', unserialize($row->getSourceProperty('data') ?? ''));

    // If this entity was translated using Entity Translation, we need to get
    // its source language to get the field values in the right language.
    // The translations will be migrated by the d7_user_entity_translation
    // migration.
    $entity_translatable = $this->isEntityTranslatable('user');
    $source_language = $this->getEntityTranslationSourceLanguage('user', $uid);
    $language = $entity_translatable && $source_language ? $source_language : $row->getSourceProperty('language');
    $row->setSourceProperty('entity_language', $language);

    // Get Field API field values.
    foreach ($this->getFields('user') as $field_name => $field) {
      // Ensure we're using the right language if the entity and the field are
      // translatable.
      $field_language = $entity_translatable && $field['translatable'] ? $language : NULL;
      $row->setSourceProperty($field_name, $this->getFieldValues('user', $field_name, $uid, $vid, $field_language));
    }

    // Get profile field values. This code is lifted directly from the D6
    // ProfileFieldValues plugin.
    if ($this->getDatabase()->schema()->tableExists('profile_value')) {
      $query = $this->select('profile_value', 'pv')
        ->fields('pv', ['fid', 'value']);
      $query->leftJoin('profile_field', 'pf', '[pf].[fid] = [pv].[fid]');
      $query->fields('pf', ['name', 'type']);
      $query->condition('uid', $row->getSourceProperty('uid'));
      $results = $query->execute();

      foreach ($results as $profile_value) {
        if ($profile_value['type'] == 'date') {
          $date = unserialize($profile_value['value']);
          $date = date('Y-m-d', mktime(0, 0, 0, $date['month'], $date['day'], $date['year']));
          $row->setSourceProperty($profile_value['name'], ['value' => $date]);
        }
        elseif ($profile_value['type'] == 'list') {
          // Explode by newline and comma.
          $row->setSourceProperty($profile_value['name'], preg_split("/[\r\n,]+/", $profile_value['value']));
        }
        else {
          $row->setSourceProperty($profile_value['name'], [$profile_value['value']]);
        }
      }
    }

    return SourcePluginBase::prepareRow($row);
  }

}
