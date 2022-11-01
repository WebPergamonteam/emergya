<?php

namespace Drupal\blazy;

/**
 * Implements BlazyFormatterInterface.
 *
 * @todo implements BlazyFormatterInterface at blazy:7.x-2.0.
 */
class BlazyFormatter extends BlazyManager {

  /**
   * The first image item found.
   *
   * @var object
   */
  protected $firstItem = NULL;

  /**
   * Checks if image dimensions are set.
   *
   * @var bool
   */
  private $isImageDimensionSet;

  /**
   * {@inheritdoc}
   */
  public function buildSettings(array &$build, $items, $entity) {
    $settings       = &$build['settings'];
    $count          = count($items);
    $entity_type_id = $settings['entity_type_id'];
    $entity_id      = $settings['entity_id'];
    $bundle         = $settings['bundle'];
    $field_name     = $settings['field_name'];
    $field_clean    = str_replace('field_', '', $field_name);
    $view_mode      = empty($settings['current_view_mode']) ? '_custom' : $settings['current_view_mode'];
    $namespace      = $settings['namespace'] = empty($settings['namespace']) ? 'blazy' : $settings['namespace'];
    $id             = isset($settings['id']) ? $settings['id'] : '';
    $gallery_id     = "{$namespace}-{$entity_type_id}-{$bundle}-{$field_clean}-{$view_mode}";
    $id             = Blazy::getHtmlId("{$gallery_id}-{$entity_id}", $id);
    $switch         = empty($settings['media_switch']) ? '' : $settings['media_switch'];
    $internal_path  = entity_uri($entity_type_id, $entity);
    $langcode       = $settings['langcode'];

    // Pass settings to child elements.
    $settings                  += $this->getCommonSettings();
    $settings['cache_metadata'] = ['keys' => [$id, $count, $langcode]];
    $settings['content_url']    = isset($internal_path['path']) ? $internal_path['path'] : '';
    $settings['count']          = $count;
    $settings['gallery_id']     = str_replace('_', '-', $gallery_id . '-' . $switch);
    $settings['id']             = $id;
    $settings['lightbox']       = ($switch && in_array($switch, $this->getLightboxes())) ? $switch : FALSE;
    $settings['entity']         = empty($settings['lightbox']) ? NULL : $entity;
    $settings['resimage']       = function_exists('picture_mapping_load') && $this->config('responsive_image', FALSE, 'blazy.settings') && !empty($settings['responsive_image_style']);

    // Don't bother with Vanilla on.
    if (!empty($settings['vanilla'])) {
      $settings = array_filter($settings);
      return;
    }

    // Don't bother if using Responsive image.
    $settings['breakpoints'] = isset($settings['breakpoints']) && empty($settings['responsive_image_style']) ? $settings['breakpoints'] : [];
    $settings['caption']     = empty($settings['caption']) ? [] : array_filter($settings['caption']);
    $settings['background']  = empty($settings['responsive_image_style']) && !empty($settings['background']);
    $settings['placeholder'] = $this->config('placeholder', '', 'blazy.settings');

    // @todo change to BlazyBreakpoint::cleanUpBreakpoints() at blazy:7.x-2.0.
    $this->cleanUpBreakpoints($settings);

    // Lazy load types: blazy, and slick: ondemand, anticipated, progressive.
    $settings['blazy'] = !empty($settings['blazy']) || $settings['background'] || !empty($settings['resimage']) || !empty($settings['breakpoints']);
    if ($settings['blazy']) {
      $settings['lazy'] = 'blazy';
    }

    // At D7, BlazyFilter can only attach globally, prevents blocking.
    // Allows lightboxes to provide its own optionsets.
    if ($switch) {
      $settings[$switch] = empty($settings[$switch]) ? $switch : $settings[$switch];
    }

    // Aspect ratio isn't working with Responsive image, yet.
    // However allows custom work to get going with an enforced.
    $ratio = FALSE;
    if (!empty($settings['ratio'])) {
      $ratio = empty($settings['responsive_image_style']);
      if ($settings['ratio'] == 'enforced' || $settings['background']) {
        $ratio = TRUE;
      }
    }

    $settings['ratio'] = $ratio ? $settings['ratio'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preBuildElements(array &$build, $items, $entity, array $entities = []) {
    $this->buildSettings($build, $items, $entity);
    $settings = &$build['settings'];

    // Pass first item to optimize sizes this time.
    if (isset($items[0]) && $item = $items[0]) {
      $this->extractFirstItem($settings, $item, reset($entities));
    }

    // Sets dimensions once, if cropped, to reduce costs with ton of images.
    // This is less expensive than re-defining dimensions per image.
    // @todo remove first_uri for _uri for consistency.
    if ((!empty($settings['_uri']) || !empty($settings['first_uri'])) && !$settings['resimage']) {
      $this->setImageDimensions($settings);
    }

    // Add the entity to formatter cache tags.
    // @todo $settings['cache_tags'][] = $settings['entity_type_id'] . ':' . $settings['entity_id'];
    // Sniffs for Views to allow block__no_wrapper, views_no_wrapper, etc.
    if (function_exists('views_get_current_view') && $view = views_get_current_view()) {
      $settings['view_name'] = $view->name;
      $settings['current_view_mode'] = $view->current_display;
      $settings['view_plugin_id'] = $view->style_plugin->plugin_name;
    }

    // Allows altering the settings.
    drupal_alter('blazy_settings', $build, $items);
  }

  /**
   * {@inheritdoc}
   */
  public function postBuildElements(array &$build, $items, $entity, array $entities = []) {
    // Rebuild the first item to build colorbox/zoom-like gallery.
    $build['settings']['first_item'] = $this->firstItem;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFirstItem(array &$settings, $item, $entity = NULL) {
    if ($settings['field_type'] == 'image') {
      $this->firstItem = (object) $item;
    }
    elseif ($settings['field_type'] == 'file' && $image = BlazyMedia::imageItem($item)) {
      $this->firstItem = $image;
    }

    // The first image dimensions to differ from individual item dimensions.
    $item = $this->firstItem;
    Blazy::imageDimensions($settings, $item, TRUE);
    // @todo remove first_uri for _uri for consistency.
    $settings['_uri'] = $settings['first_uri'] = $item && isset($item->uri) ? $item->uri : '';
  }

  /**
   * Sets dimensions once to reduce method calls, if image style contains crop.
   *
   * @param array $settings
   *   The settings being modified.
   */
  protected function setImageDimensions(array &$settings = []) {
    if (!isset($this->isImageDimensionSet[md5($settings['id'])])) {

      // If image style contains crop, sets dimension once, and let all inherit.
      if (!empty($settings['image_style']) && $this->isCrop($settings['image_style'])) {
        $settings = array_merge($settings, Blazy::transformDimensions($settings['image_style'], $this->firstItem));

        // Informs individual images that dimensions are already set once.
        // @todo re-enable $settings['_dimensions'] = TRUE;
        // @fixme Unlike D8, this makes the first item has different dimensions.
      }

      // Also sets breakpoint dimensions once, if cropped.
      // @todo TBD; for keeping or removal at blazy:7.x-2.0.
      if (!empty($settings['breakpoints'])) {
        $this->buildDataBlazy($settings, $this->firstItem);
      }

      $this->isImageDimensionSet[md5($settings['id'])] = TRUE;
    }
  }

}
