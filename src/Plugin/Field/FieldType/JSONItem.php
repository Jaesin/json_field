<?php

/**
 * @file
 * Contains \Drupal\json_field\Plugin\Field\FieldType\JSONItem.
 */

namespace Drupal\json_field\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'JSON' field type.
 *
 * @FieldType(
 *   id = "json",
 *   label = @Translation("JSON"),
 *   description = @Translation("This field stores JSON text."),
 *   category = @Translation("Data"),
 *   default_widget = "json_textarea",
 *   default_formatter = "json",
 *   constraints = {"valid_json" = {}}
 * )
 */
class JSONItem extends FieldItemBase {

  /**
   * 2^8-1
   */
  const SIZE_SMALL = 255;

  /**
   * 2^16-1
   */
  const SIZE_NORMAL = 65535;

  /**
   * 2^24-1
   */
  const SIZE_MEDIUM = 16777215;

  /**
   * 2^32-1
   */
  const SIZE_BIG = 4294967295;

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(
      'size' => static::SIZE_BIG,
    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = parent::storageSettingsForm($form, $form_state, $has_data);

    $elements['size'] = [
      '#type' => 'select',
      '#title' => $this->t('Maximum size'),
      '#options' => [
        static::SIZE_SMALL => t('255 Byte'),
        static::SIZE_NORMAL - 1 => t('64 KB'),
        static::SIZE_MEDIUM - 1 => t('16 MB'),
        static::SIZE_BIG - 1 => t('4 GB'),
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('JSON Value'))
      ->setRequired(TRUE);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema['columns']['value'] = [];

    $size = $field_definition->getSetting('size');
    switch ($size) {
      case static::SIZE_SMALL:
        $schema['columns']['value']['type'] = 'varchar';
        $schema['columns']['value']['length'] = static::SIZE_SMALL;
        break;
      case static::SIZE_NORMAL:
        // We use utf8mb4 so the maximum length is size / 4, so we cannot use
        // juse type 'varchar' with size of 65535.
        $schema['columns']['value']['type'] = 'text';
        $schema['columns']['value']['size'] = 'normal';
        break;
      case static::SIZE_MEDIUM:
        $schema['columns']['value']['type'] = 'text';
        $schema['columns']['value']['size'] = 'medium';
        break;
      case static::SIZE_BIG:
        $schema['columns']['value']['type'] = 'text';
        $schema['columns']['value']['size'] = 'big';
        break;
    }

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['value'] = '{"foo": "' . $random->word(mt_rand(1, 2000)). '""}';
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }
}
