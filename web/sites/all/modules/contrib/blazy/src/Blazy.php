<?php

namespace Drupal\blazy;

use Drupal\blazy\Utility\NestedArray;

/**
 * Implements BlazyInterface.
 *
 * @todo implements BlazyInterface at blazy:7.x-2.0.
 */
class Blazy {

  /**
   * Defines constant placeholder Data URI image.
   *
   * @todo move it to BlazyInterface.
   */
  const PLACEHOLDER = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

  /**
   * The blazy HTML ID.
   *
   * @var int
   */
  private static $blazyId;

  /**
   * Returns common content with prefix and suffix containers.
   */
  public static function container($content, $attributes, $tag = 'div') {
    $build = array_filter($content);
    // Supports DIV only without $content such as for CSS background.
    if ($build || $attributes) {
      $build['#prefix'] = '<' . $tag . drupal_attributes($attributes) . '>';
      $build['#suffix'] = '</' . $tag . '>';
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function urlAndDimensions(array &$settings, $item = NULL) {
    // VEF without image style, or image style with crop, may already set these.
    self::imageDimensions($settings, $item);
    self::imageUrl($settings, $item);
  }

  /**
   * {@inheritdoc}
   */
  public static function imageDimensions(array &$settings, $item = NULL, $initial = FALSE) {
    $width = $initial ? '_width' : 'width';
    $height = $initial ? '_height' : 'height';

    if (empty($settings[$width])) {
      $settings[$width] = $item && isset($item->width) ? $item->width : NULL;
      $settings[$height] = $item && isset($item->height) ? $item->height : NULL;
    }
  }

  /**
   * Provides image url based on the given settings.
   */
  public static function imageUrl(array &$settings, $item = NULL) {
    // Provides image URL expected by lazy load.
    $uri = $settings['uri'];
    if ($settings['image_style']) {
      $settings['image_url'] = image_style_url($settings['image_style'], $uri);

      // Only re-calculate dimensions if not cropped, nor already set once.
      if (empty($settings['_dimensions'])) {
        $settings = array_merge($settings, self::transformDimensions($settings['image_style'], $item));
      }
    }
    else {
      $image_url = file_valid_uri($uri) ? file_create_url($uri) : $uri;
      $settings['image_url'] = $settings['image_url'] ?: $image_url;
    }

    // Just in case, an attempted kidding gets in the way, relevant for UGC.
    $data_uri = !empty($settings['use_data_uri']) && substr($settings['image_url'], 0, 10) === 'data:image';
    if (!empty($settings['_check_protocol']) && !$data_uri) {
      $settings['image_url'] = drupal_strip_dangerous_protocols($settings['image_url']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function transformDimensions($image_style, $item = NULL) {
    $dimensions['width'] = $item && isset($item->width) ? $item->width : NULL;
    $dimensions['height'] = $item && isset($item->height) ? $item->height : NULL;

    image_style_transform_dimensions($image_style, $dimensions);
    return $dimensions;
  }

  /**
   * Returns the URI from the given image URL, relevant for unmanaged files.
   *
   * @todo recheck if any core function for this aside from file_build_uri().
   */
  public static function buildUri($image_url) {
    if (!url_is_external($image_url) && $path = drupal_parse_url($image_url)['path']) {
      $normal_path = drupal_get_normal_path($path);
      $public_path = variable_get('file_public_path', 'sites/default/files');

      // Only concerns for the correct URI, not image URL which is already being
      // displayed via SRC attribute. Don't bother language prefixes for IMG.
      if ($public_path && strpos($normal_path, $public_path) !== FALSE) {
        $rel_path = str_replace($public_path, '', $normal_path);
        return file_build_uri($rel_path);
      }
    }
    return FALSE;
  }

  /**
   * Modifies image attributes.
   */
  public static function imageAttributes(array &$attributes, array $settings, $item = NULL) {
    // Unlike D8, we have no free $item->_attributes from RDF, provide one here.
    // With or without rdf enabled, no need to check for module_exists().
    $attributes['typeof'] = ['foaf:Image'];

    // Extract field item attributes for the theme function, and unset them
    // from the $item so that the field template does not re-render them.
    if ($item && isset($item->_attributes)) {
      $attributes += $item->_attributes;
      unset($item->_attributes);
    }

    // Respects hand-coded image attributes.
    if ($settings['width'] && !isset($attributes['width'])) {
      $attributes['height'] = $settings['height'];
      $attributes['width'] = $settings['width'];
    }

    // The fallback must run as fallback, also for Picture.
    if ($item) {
      foreach (['width', 'height', 'alt', 'title'] as $key) {
        if (isset($item->{$key})) {
          // Respects hand-coded image attributes, image style, and set once.
          if (array_key_exists($key, $attributes)) {
            continue;
          }

          // Do not output an empty 'title' attribute.
          if ($key == 'title' && (strlen($item->title) != 0)) {
            $attributes['title'] = $item->title;
          }
          // Ensures to not override dimensions set once, or via image_style.
          elseif (!isset($attributes[$key])) {
            $attributes[$key] = $item->{$key};
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function lazyAttributes(array &$attributes, array $settings = []) {
    // Defines attributes, builtin, or supported lazyload such as Slick.
    $attributes['class'][] = $settings['lazy_class'];
    $attributes['data-' . $settings['lazy_attribute']] = $settings['image_url'];

    // Support browser native lazy loading as per 8/2019 specific to Chrome 76+.
    // See https://web.dev/native-lazy-loading/
    $attributes['loading'] = 'lazy';
  }

  /**
   * Modifies container attributes with aspect ratio.
   */
  public static function aspectRatioAttributes(array &$attributes, array &$settings) {
    $attributes['class'][] = 'media--ratio media--ratio--' . $settings['ratio'];

    if ($settings['width'] && in_array($settings['ratio'], ['enforced', 'fluid'])) {
      // If "lucky", Blazy/ Slick Views galleries may already set this once.
      // Lucky when you don't flatten out the Views output earlier.
      $padding = $settings['padding_bottom'] ?: round((($settings['height'] / $settings['width']) * 100), 2);
      self::inlineStyle($attributes, 'padding-bottom: ' . $padding . '%;');

      // Provides hint to breakpoints to work with multi-breakpoint ratio.
      $settings['_breakpoint_ratio'] = $settings['ratio'];

      // Views rewrite results or Twig inline_template may strip out `style`
      // attributes, provide hint to JS.
      $attributes['data-ratio'] = $padding;
    }
  }

  /**
   * Provides container attributes for .blazy container: .field, .view, etc.
   *
   * Blazy output can be passed as theme_field() or theme_item_list().
   * At D7, attributes are stored as classes_array and attributes_array.
   * as commonly found at template_preproces_BLAH, unless without preprocess.
   * The $variables here is interchanged as $variables or $attributes for D7.
   */
  public static function containerAttributes(array &$variables, array $settings = []) {
    $settings += ['namespace' => 'blazy'];
    $is_vars = isset($variables['classes_array']);
    $classes = $is_vars ? $variables['classes_array'] : (empty($variables['class']) ? [] : $variables['class']);
    $attrs['data-blazy'] = empty($settings['blazy_data']) ? '' : drupal_json_encode($settings['blazy_data']);

    // Provides data-LIGHTBOX-gallery to not conflict with original modules.
    if (!empty($settings['media_switch']) && $settings['media_switch'] != 'content') {
      $switch = str_replace('_', '-', $settings['media_switch']);
      $attrs['data-' . $switch . '-gallery'] = '';
      $classes[] = 'blazy--' . $switch;
    }

    // Provides contextual classes relevant to the container: .field, or .view.
    // Sniffs for Views to allow block__no_wrapper, views__no_wrapper, etc.
    foreach (['field', 'view'] as $key) {
      if (!empty($settings[$key . '_name'])) {
        $name = str_replace('_', '-', $settings[$key . '_name']);
        $name = $key == 'view' ? 'view--' . $name : $name;
        $classes[] = $settings['namespace'] . '--' . $key;
        $classes[] = $settings['namespace'] . '--' . $name;
        if (!empty($settings['current_view_mode'])) {
          $view_mode = str_replace('_', '-', $settings['current_view_mode']);
          $classes[] = $settings['namespace'] . '--' . $name . '--' . $view_mode;
        }
      }
    }

    // While D8 makes themers' life easier, bear with D7 limitations.
    if ($is_vars) {
      // Hence theme_field() expects attributes_array with (pre)process.
      $variables['attributes_array'] = isset($variables['attributes_array']) ? NestedArray::mergeDeep($variables['attributes_array'], $attrs) : $attrs;
    }
    else {
      // Hence theme_item_list() expects attributes without (pre)process.
      $variables = NestedArray::mergeDeep($variables, $attrs);
    }
    $variables[$is_vars ? 'classes_array' : 'class'] = array_merge(['blazy'], $classes);
  }

  /**
   * {@inheritdoc}
   */
  public static function sanitize(array $attributes = []) {
    $clean_attributes = [];
    $tags = ['href', 'poster', 'src', 'about', 'data', 'action', 'formaction'];
    foreach ($attributes as $key => $value) {
      if (is_array($value)) {
        // Respects array item containing space delimited classes: aaa bbb ccc.
        $value = implode(' ', $value);
        $clean_attributes[$key] = array_map('drupal_clean_css_identifier', explode(' ', $value));
      }
      else {
        // Since Blazy is lazyloading known URLs, sanitize attributes which make
        // no sense to stick around within IMG or IFRAME tags relevant for UGC.
        $kid = substr($key, 0, 2) === 'on' || in_array($key, $tags);
        $key = $kid ? 'data-' . $key : $key;
        $clean_attributes[$key] = $kid ? drupal_clean_css_identifier($value) : check_plain($value);
      }
    }
    return $clean_attributes;
  }

  /**
   * Returns the trusted HTML ID of a single instance.
   */
  public static function getHtmlId($string = 'blazy', $id = '') {
    if (!isset(static::$blazyId)) {
      static::$blazyId = 0;
    }

    // Do not use dynamic Html::getUniqueId, otherwise broken AJAX.
    $id = empty($id) ? ($string . '-' . ++static::$blazyId) : $id;
    return trim(str_replace('_', '-', strip_tags($id)));
  }

  /**
   * Modifies inline style to not nullify others.
   */
  public static function inlineStyle(array &$attributes, $css) {
    $attributes['style'] = (isset($attributes['style']) ? $attributes['style'] : '') . $css;
  }

  /**
   * Gets the numeric "width" part from a descriptor.
   *
   * @todo deprecate for BlazyBreakpoint::widthFromDescriptors() blazy:7.x-2.0.
   */
  public static function widthFromDescriptors($descriptor = '') {
    if (empty($descriptor)) {
      return FALSE;
    }

    // Dynamic multi-serving aspect ratio with backward compatibility.
    $descriptor = trim($descriptor);
    if (is_numeric($descriptor)) {
      return (int) $descriptor;
    }

    // Cleanup w descriptor to fetch numerical width for JS aspect ratio.
    $width = strpos($descriptor, "w") !== FALSE ? str_replace('w', '', $descriptor) : $descriptor;

    // If both w and x descriptors are provided.
    if (strpos($descriptor, " ") !== FALSE) {
      // If the position is expected: 640w 2x.
      list($width, $px) = array_pad(array_map('trim', explode(" ", $width, 2)), 2, NULL);

      // If the position is reversed: 2x 640w.
      if (is_numeric($px) && strpos($width, "x") !== FALSE) {
        $width = $px;
      }
    }
    return is_numeric($width) ? (int) $width : FALSE;
  }

  /**
   * Provides re-usable breakpoint data-attributes for IMG or DIV element.
   *
   * @todo deprecate for BlazyBreakpoint::attributes() at blazy:7.x-2.0.
   */
  public static function buildBreakpointAttributes(array &$attributes, array &$settings, $item = NULL) {
    // Only provide multi-serving image URLs if breakpoints are provided.
    if (empty($settings['breakpoints'])) {
      return;
    }

    $srcset = $json = $bg_sources = [];
    // https://css-tricks.com/sometimes-sizes-is-quite-important/
    // For older iOS devices that don't support w descriptors in srcset, the
    // first source item in the list will be used.
    foreach ($settings['breakpoints'] as $breakpoint) {
      $url = image_style_url($breakpoint['image_style'], $settings['uri']);

      // Supports multi-breakpoint aspect ratio with irregular sizes.
      // Yet, only provide individual dimensions if not already set.
      // See Drupal\blazy\BlazyFormatter::setImageDimensions().
      $width = self::widthFromDescriptors($breakpoint['width']);
      if (!empty($settings['_breakpoint_ratio']) && empty($settings['blazy_data']['dimensions'])) {
        $dim = self::transformDimensions($breakpoint['image_style'], $item);

        if ($width) {
          $ratio = round((($dim['height'] / $dim['width']) * 100), 2);
          $json[$width] = $ratio;
        }
      }
      else {
        $ratio = $settings['blazy_data']['dimensions'][$width];
      }

      // @todo deprecate $settings['breakpoints'][$key]['url'] = $url;
      // Recheck library if multi-styled BG is still supported anyway.
      // Confirmed: still working with GridStack multi-image-style per item.
      if ($settings['background']) {
        // @todo deprecate $attributes['data-src-' . $key] = $url;
        // A new background approach for both Blazy and IO, likely picture,
        // either due to deprecated breakpoints, or picture integration.
        $bg_sources[$width] = ['src' => $url, 'ratio' => $ratio];
      }
      else {
        $width = is_numeric($width) ? $width . 'w' : $width;
        $srcset[] = $url . ' ' . $width;
      }
    }

    if ($srcset) {
      $settings['srcset'] = implode(', ', $srcset);

      $attributes['srcset'] = '';
      $attributes['data-srcset'] = $settings['srcset'];
      $attributes['sizes'] = '100w';

      if (!empty($settings['sizes'])) {
        $attributes['sizes'] = trim($settings['sizes']);
        unset($attributes['height'], $attributes['width']);
      }
    }

    if ($json) {
      $settings['blazy_data']['dimensions'] = $json;
    }

    if ($bg_sources) {
      ksort($bg_sources);
      $settings['urls'] = $bg_sources;
    }
  }

}
