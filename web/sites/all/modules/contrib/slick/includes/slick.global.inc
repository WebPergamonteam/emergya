<?php

/**
 * @file
 * This file is not used by Slick 3.x.
 *
 * @deprecated in slick:7.x-3.0 and is removed from slick:7.x-4.0. Use
 *   the provided substitutions instead.
 * @see https://www.drupal.org/node/3031759
 */

use Drupal\slick\SlickDefault;

/**
 * To be removed.
 */
function slick_get_global_default_settings() {
  return SlickDefault::extendedSettings();
}

/**
 * To be removed.
 */
function slick_build_grids(array $build, &$settings) {
  // No longer relevant, SlickManager::slick() takes care of it now, but kept
  // for direct call till removed.
  return slick()->buildGrid($build, $settings);
}
