<?php

/**
 * @file
 * To be removed once Slick views, etc. have 3.x, or before Slick full release.
 *
 * You can disable this file inclusion at Slick UI after all-slick related
 * modules (Slick Views, Slick Extras, etc.) migrated into 3.x.
 *
 * @todo remove post slick:7.x-3.0.
 * @see https://www.drupal.org/node/3031759
 */

use Drupal\blazy\Blazy;
use Drupal\slick\Entity\Slick;

/**
 * To be removed.
 *
 * @deprecated in slick:7.x-3.0 and is removed from slick:7.x-4.0. Use
 *   Slick::defaultSettings() instead.
 * @see https://www.drupal.org/node/3031759
 */
function slick_get_options() {
  return Slick::defaultSettings();
}

/**
 * To be removed.
 *
 * @deprecated in slick:7.x-3.0 and is removed from slick:7.x-4.0. Use
 *   SlickManager::getSkins() instead.
 * @see https://www.drupal.org/node/3031759
 */
function slick_skins() {
  // @todo cannot use return slick()->getSkins(); for BC layer which
  // may use MY_MODULE.slick.inc instead of class file.
  $skins = &drupal_static(__FUNCTION__, NULL);
  if (!isset($skins)) {
    $skins = module_invoke_all('slick_skins_info');
    drupal_alter('slick_skins_info', $skins);
  }
  return $skins;
}

/**
 * To be removed.
 *
 * @deprecated in slick:7.x-3.0 and is removed from slick:7.x-4.0. Use
 *   SlickManager::getSkinsByGroup() instead.
 * @see https://www.drupal.org/node/3031759
 */
function slick_get_skins_by_group($group = '', $select = FALSE) {
  return slick()->getSkinsByGroup($group, $select);
}

/**
 * To be removed.
 *
 * @deprecated in slick:7.x-3.0 and is removed from slick:7.x-4.0. Use
 *   Blazy::getHtmlId() instead.
 * @see https://www.drupal.org/node/3031759
 */
function slick_html_id($string = 'slick', $id = '') {
  return Blazy::getHtmlId($string, $id);
}

/**
 * To be removed.
 *
 * @deprecated in slick:7.x-3.0 and is removed from slick:7.x-4.0. Use
 *   Slick::create() instead.
 * @see https://www.drupal.org/node/3031759
 */
function slick_optionset_create(array $values = []) {
  return Slick::create($values);
}

/**
 * To be removed.
 *
 * @deprecated in slick:7.x-3.0 and is removed from slick:7.x-4.0. Use
 *   SlickManager::build() instead.
 * @see https://www.drupal.org/node/3031759
 */
function slick_build(array $items, array $options, array $settings = [], array $attach = [], $id = NULL, $optionset = NULL) {
  $loaded = isset($attach['css']) || isset($attach['js']) || isset($attach['library']);
  $attachments = $loaded ? $attach : slick_attach($attach, $settings);
  $build = [
    'items'     => $items,
    'settings'  => $settings,
    'options'   => $options,
    'optionset' => $optionset,
    'attached'  => $attachments,
  ];
  return slick()->slick($build);
}

/**
 * To be removed.
 *
 * @deprecated in slick:7.x-3.0 and is removed from slick:7.x-4.0. Use
 *   SlickManager::build() instead.
 * @see https://www.drupal.org/node/3031759
 */
function slick_render_cache($settings = []) {
  return FALSE;
}

/**
 * To be removed.
 */
function slick_get_image(array $settings = [], array &$media = [], array $item = []) {
  $build = [
    'item' => (object) $item,
    'settings' => array_merge($settings, $media),
  ];

  return slick('formatter')->getBlazy($build);
}

/**
 * To be removed.
 */
function slick_get_dimensions($media = [], $image_style = 'large') {
  return [];
}
