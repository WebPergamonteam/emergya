<?php

namespace Drupal\blazy;

/**
 * Defines common field formatter-related methods: Blazy, Slick.
 */
interface BlazyFormatterInterface extends BlazyManagerInterface {

  /**
   * Modifies the field formatter settings inherited by child elements.
   *
   * @param array $build
   *   The array containing: settings, or potential optionset for extensions.
   * @param object $items
   *   The items to prepare settings for.
   * @param object $entity
   *   The entity this field belongs to.
   */
  public function buildSettings(array &$build, $items, $entity);

  /**
   * Modifies the field formatter settings inherited by child elements.
   *
   * @param array $build
   *   The array containing: settings, or potential optionset for extensions.
   * @param object $items
   *   The Drupal\Core\Field\FieldItemListInterface items.
   * @param object $entity
   *   The entity this field belongs to.
   * @param array $entities
   *   The optional entities array, not available for non-entities: text, image.
   */
  public function preBuildElements(array &$build, $items, $entity, array $entities = []);

  /**
   * Modifies the field formatter settings not inherited by child elements.
   *
   * @param array $build
   *   The array containing: items, settings, or a potential optionset.
   * @param object $items
   *   The Drupal\Core\Field\FieldItemListInterface items.
   * @param object $entity
   *   The entity this field belongs to.
   * @param array $entities
   *   The optional entities array, not available for non-entities: text, image.
   */
  public function postBuildElements(array &$build, $items, $entity, array $entities = []);

  /**
   * Extract the first image item to build colorbox/zoom-like gallery.
   *
   * @param array $settings
   *   The $settings array being modified.
   * @param object $item
   *   The Drupal\image\Plugin\Field\FieldType\ImageItem item.
   * @param object $entity
   *   The optional media entity.
   */
  public function extractFirstItem(array &$settings, $item, $entity = NULL);

}
