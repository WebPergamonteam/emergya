<?php

namespace Drupal\blazy;

/**
 * Defines re-usable services and functions for blazy plugins.
 */
interface BlazyManagerInterface {

  /**
   * Returns array of needed assets suitable for #attached property.
   *
   * @return array
   *   Returns the required library array.
   */
  public function attach(array $attach);

  /**
   * Typecast the needed settings, blazy-related module can override.
   *
   * Performance wise, typecasting should be at the form submit as calling
   * self::config() can be called multiple times. See sample below.
   *
   * @see Drupal\blazy_ui\Form\BlazySettingsForm::submitForm()
   * @see Drupal\slick_ui\Form\SlickSettingsForm::submitForm()
   */
  public function typecast(array &$config, $id = 'blazy.settings');

  /**
   * Gets the supported lightboxes.
   *
   * @return array
   *   The supported lightboxes.
   */
  public function getLightboxes();

  /**
   * Checks for Blazy formatter such as from within a Views style plugin.
   *
   * Ensures the settings traverse up to the container where Blazy is clueless.
   * The supported plugins can add [data-blazy] attribute into its container
   * containing $settings['blazy_data'] converted into [data-blazy] JSON.
   * This allows Blazy Grid, or other Views styles, lacking of UI, to have
   * additional settings extracted from the first Blazy formatter found.
   * Such as media switch/ lightbox. This way the container can add relevant
   * attributes to its container, etc. Also applies to entity references where
   * Blazy is not the main formatter, instead embedded as part of the parent's.
   *
   * @param array $settings
   *   The settings being modified.
   * @param array $item
   *   The item containing settings or item keys, not image item.
   */
  public function isBlazy(array &$settings, array $item = []);

}
