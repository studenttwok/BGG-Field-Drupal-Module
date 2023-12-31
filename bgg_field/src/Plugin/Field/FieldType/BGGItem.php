<?php

namespace Drupal\bgg_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

// https://www.drupal.org/docs/creating-custom-modules/creating-custom-field-types-widgets-and-formatters/overview-creating-a-custom-field

/**
 * Plugin implementation of the 'snippets' field type.
 *
 * @FieldType(
 *   id = "bgg_field",
 *   label = @Translation("BGG ID"),
 *   description = @Translation("This field stores BGG ID in DB."),
 *   default_widget = "bgg_field_default_widget",
 *   default_formatter = "bgg_field_default_formatter"
 * )
 */
class BGGItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 10,
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('BGG ID'));

    return $properties;
  }

}
