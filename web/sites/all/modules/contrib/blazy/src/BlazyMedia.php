<?php

namespace Drupal\blazy;

/**
 * Provides extra utilities to work with Media.
 */
class BlazyMedia {

  /**
   * The preprocessed/ formatted variables based on media settings.
   *
   * @var array
   */
  private static $mediaData;

  /**
   * Prepares the Blazy iframe as a structured array ready for ::renderer().
   *
   * @param array $element
   *   The renderable array being modified.
   *
   * @todo support other Media file entities like at D8: Media Facebook, etc.
   */
  public static function build(array &$element = []) {
    $attributes            = &$element['#attributes'];
    $settings              = &$element['#settings'];
    $settings['player']    = empty($settings['lightbox']) && $settings['media_switch'] == 'media';
    $settings['use_image'] = !empty($settings['media_switch']);
    $iframe_attributes     = [
      'data-src' => $settings['embed_url'],
      'src' => 'about:blank',
      'class' => ['b-lazy', 'media__element'],
      'allowfullscreen' => '',
    ];

    // Prevents broken iframe when aspect ratio is empty.
    if (empty($settings['ratio']) && $settings['width']) {
      $iframe_attributes['width'] = $settings['width'];
      $iframe_attributes['height'] = $settings['height'];
    }

    // Adds specific Youtube attributes, related to mobile apps.
    $allow = '';
    if (strpos($settings['embed_url'], 'youtu') !== FALSE) {
      $allow = $iframe_attributes['allow'] = 'autoplay; accelerometer; encrypted-media; gyroscope; picture-in-picture';
    }

    $player_attributes = [
      'class'    => 'media__icon media__icon--play',
      'data-url' => $settings['autoplay_url'],
    ];

    if ($settings['use_media']) {
      $iframe = [
        '#type' => 'html_tag',
        '#tag' => 'iframe',
        '#value' => '',
        '#attributes' => $iframe_attributes,
      ];

      // No need to print iframe when media switcher is on to save bytes.
      if ($settings['player']) {
        $icon = '<span class="media__icon media__icon--close"></span>';
        $iframe = ['#markup' => $icon . '<span' . drupal_attributes($player_attributes) . '></span>'];
      }

      $element['#iframe'] = $iframe;
      $attributes['class'][] = 'media--player';

      // Support browser native lazy loading as per 8/2019, Chrome 76+.
      // See https://web.dev/native-lazy-loading/
      $attributes['loading'] = 'lazy';
    }

    // Iframe is removed on lazyloaded, puts data at non-removable storage.
    $attributes['data-media'] = drupal_json_encode([
      'type'   => $settings['type'],
      'scheme' => $settings['scheme'],
      'allow'  => $allow,
    ]);
  }

  /**
   * Gets the faked image item out of file entity, or ER, if applicable.
   *
   * This should only be called for type video as file image has all
   * the needed info to get the image from.
   *
   * @param object $file
   *   The expected file entity, or ER, to get image item from.
   *
   * @return object
   *   The image item or FALSE.
   */
  public static function imageItem($file) {
    // Prevents edge case EntityMalformedException: Missing bundle property.
    if (!isset($file->uri)) {
      return FALSE;
    }

    try {
      $wrapper = file_stream_wrapper_get_instance_by_uri($file->uri);
      // No need for checking MediaReadOnlyStreamWrapper.
      if (!is_object($wrapper)) {
        throw new \Exception('Unable to find matching wrapper');
      }

      // If a video, uri points to a video scheme, not local thumbnail.
      $uri = $file->type == 'image' ? $file->uri : $wrapper->getLocalThumbnailPath();
    }
    catch (\Exception $e) {
      // Ignore.
    }

    if (!isset($uri)) {
      return FALSE;
    }

    list($type) = explode('/', file_get_mimetype($uri), 2);

    if ($type == 'image' && ($image = image_get_info($uri))) {
      $item            = new \stdClass();
      $item->target_id = $file->fid;
      $item->width     = $image['width'];
      $item->height    = $image['height'];
      $item->alt       = $file->filename;
      $item->uri       = $uri;

      return $item;
    }

    return FALSE;
  }

  /**
   * Returns the preprocessed/ formatted variables based on media settings.
   *
   * Known working media integration: Youtube, Vimeo. Others might differ.
   */
  public static function getMediaData(array $settings = []) {
    if (!isset(static::$mediaData[hash('md2', $settings['media_uri'])])) {
      $media_settings = self::getMediaSettings($settings);
      $scheme = $settings['scheme'];

      $theme_function = 'media_' . $scheme . '_theme';
      if ($media_settings && is_callable($theme_function)) {
        // Safe to use ctools, since file_entity depends on it.
        ctools_include('media_' . $scheme . '.theme', 'media_' . $scheme, 'themes');

        $function = 'media_' . $scheme . '_preprocess_media_' . $scheme . '_video';
        if (is_callable($function)) {
          $media_settings['captions'] = 0;
          $variables = ['options' => $media_settings, 'uri' => $settings['media_uri']];

          // @todo compare against Slick 2.x, #3084848#comment-13283135.
          $function($variables);

          // Allows local devs or sites without SSL to still work.
          $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';

          $variables['url'] = str_replace(['http://', 'https://'], $protocol .'://', $variables['url']);
          static::$mediaData[hash('md2', $settings['media_uri'])] = $variables;
        }
      }
    }

    return static::$mediaData[hash('md2', $settings['media_uri'])];
  }

  /**
   * Returns the {file_display} settings for the file type and view mode.
   */
  public static function getMediaSettings(array $settings = []) {
    ctools_include('export');

    $formatter = 'media_' . $settings['scheme'] . '_' . $settings['type'];
    $name = $settings['type'] . '__' . $settings['view_mode'] . '__' . $formatter;
    // Example: `video__slick_carousel__media_youtube_video`.
    $displays = ctools_export_load_object('file_display', 'names', [$name]);
    return $displays[$name] ? $displays[$name]->settings : FALSE;
  }

}
