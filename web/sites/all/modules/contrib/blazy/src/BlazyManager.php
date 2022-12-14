<?php

namespace Drupal\blazy;

use Drupal\blazy\Utility\NestedArray;

/**
 * Implements a public facing blazy manager.
 *
 * A few modules re-use this: GridStack, Mason, Slick...
 */
class BlazyManager extends BlazyManagerBase {

  /**
   * {@inheritdoc}
   */
  public function config($key = '', $default = NULL, $id = 'blazy.settings', array $defaults = []) {
    return parent::config($key, $default, $id, $id == 'blazy.settings' ? BlazyDefault::formSettings() : $defaults);
  }

  /**
   * {@inheritdoc}
   */
  public function typecast(array &$config, $id = 'blazy.settings') {
    if ($id == 'blazy.settings') {
      $defaults = BlazyDefault::formSettings();
      foreach ($defaults as $key => $value) {
        if (isset($config[$key])) {
          if ($key == 'blazy' || $key == 'io') {
            foreach ($defaults[$key] as $k => $v) {
              settype($config[$key][$k], gettype($v));
            }
          }
          elseif ($key == 'filters') {
            foreach ($defaults[$key] as $k => $v) {
              // Has nested array [filters][format][grid|column|media_switch].
              foreach ($config[$key] as $sk => $format) {
                foreach ($format as $ssk => $ignore) {
                  settype($config[$key][$sk][$ssk], gettype($v));
                }
              }
            }
          }
          else {
            settype($config[$key], gettype($value));
          }
        }
      }
    }
  }

  /**
   * Returns the enforced content, or image using theme_blazy().
   *
   * The image_style property is not directly used by theme_blazy() but for
   * a quick reference such as from a Views style plugin rather than deep drill.
   * Will be removed if we figure out a better way, or no longer useful.
   *
   * @param array $build
   *   The array containing:
   *     - item: The image / file entity item object converted from array.
   *     - settings: An array of settings to instruct Blazy what to do.
   *     - captions: Optional captions for both inline, or lightbox as per name.
   *
   * @return array
   *   The alterable and renderable array of enforced content, or theme_blazy().
   */
  public function getBlazy(array $build = []) {
    foreach (BlazyDefault::themeProperties() as $key) {
      $build[$key] = isset($build[$key]) ? $build[$key] : [];
    }

    $settings = &$build['settings'];
    $settings += BlazyDefault::itemSettings();

    $item = $build['item'];
    $settings['uri'] = $settings['uri'] ?: ($item && isset($item->uri) ? $item->uri : '');

    $content = [
      '#theme'       => 'blazy',
      '#image_style' => $settings['image_style'],
      '#item'        => $item,
      '#build'       => $build,
      '#pre_render'  => ['blazy_pre_render'],
    ];

    drupal_alter('blazy', $content, $settings);
    return empty($settings['uri']) ? [] : $content;
  }

  /**
   * Builds the Blazy as a structured array ready for ::renderer().
   *
   * @param array $element
   *   The pre-rendered element.
   *
   * @return array
   *   The renderable array of pre-rendered element.
   */
  public function preRender(array $element) {
    $build = $element['#build'];
    unset($element['#build']);

    // Prepare the main image.
    $this->prepareImage($element, $build);

    // Fetch the newly modified settings.
    $settings = &$element['#settings'];

    // Provides optional media video if so configured.
    // Does it look familiar, `module_load_include()`, only native?
    // Allows a hybrid of media switcher and quasi-lightbox like Zooming, etc.
    if ($settings['use_media'] && empty($settings['_noiframe'])) {
      BlazyMedia::build($element);
    }

    // Image is optional for Video, and Blazy CSS background images.
    // Must run after image or video setups.
    if ($settings['background']) {
      $settings['use_image'] = FALSE;
    }

    // Provides optional link to content or lightboxes if so configured.
    if (!empty($settings['media_switch'])) {
      if ($settings['media_switch'] == 'content' && !empty($settings['content_url'])) {
        $element['#url'] = $settings['content_url'];
      }
      elseif (!empty($settings['lightbox'])) {
        BlazyLightbox::build($element);
      }
    }

    return $element;
  }

  /**
   * Prepares the Blazy image as a structured array ready for ::renderer().
   *
   * @param array $element
   *   The renderable array being modified.
   * @param array $build
   *   The array of information containing the required Image or File item
   *   object, settings, optional container attributes.
   */
  protected function prepareImage(array &$element, array $build) {
    $item = $build['item'];
    $settings = &$build['settings'];

    // Decides if to use image or video, or both.
    $settings['_api'] = TRUE;
    $settings['ratio'] = $settings['ratio'] ? str_replace(':', '', $settings['ratio']) : FALSE;
    $settings['ratio'] = $settings['background'] && empty($settings['ratio']) ? 'fluid' : $settings['ratio'];
    $settings['use_media'] = $settings['embed_url'] && in_array($settings['type'], ['audio', 'video']) && empty($settings['lightbox']);

    foreach (BlazyDefault::themeAttributes() as $key) {
      $key = $key . '_attributes';
      $build[$key] = isset($build[$key]) ? $build[$key] : [];
    }

    // Blazy has these 3 attributes, yet provides optional ones far below.
    // Sanitize potential user-defined attributes such as from BlazyFilter.
    // Skip attributes via $item, or by module, as they are not user-defined.
    $attributes = &$build['attributes'];

    // Build thumbnail and optional placeholder based on thumbnail.
    $this->thumbnailAndPlaceholder($attributes, $settings);

    // Prepare image URL and its dimensions.
    Blazy::urlAndDimensions($settings, $item);

    // Build out the media.
    $this->buildMedia($element, $build);

    // Provides extra attributes as needed, excluding url, item, done above.
    foreach (['caption', 'media', 'wrapper'] as $key) {
      $element["#$key" . '_attributes'] = empty($build[$key . '_attributes']) ? [] : Blazy::sanitize($build[$key . '_attributes']);
    }

    $captions = empty($build['captions']) ? [] : $this->buildCaption($build['captions'], $settings);
    $element['#caption_attributes']['class'][] = $settings['item_id'] . '__caption';

    // Provides data for the renderable elements.
    $element['#captions']       = $captions;
    $element['#attributes']     = $attributes;
    $element['#postscript']     = $build['postscript'];
    $element['#url_attributes'] = $build['url_attributes'];
    $element['#settings']       = $settings;
  }

  /**
   * Build out (Responsive) image.
   */
  private function buildMedia(array &$element, array &$build) {
    $item = $build['item'];
    $settings = &$build['settings'];
    $attributes = &$build['attributes'];

    // (Responsive) image with item attributes, might be RDF.
    $item_attributes = empty($build['item_attributes']) ? [] : Blazy::sanitize($build['item_attributes']);

    // Provides image attributes, also for Picture.
    Blazy::imageAttributes($item_attributes, $settings, $item);

    // Picture integration.
    if (!empty($settings['resimage'])) {
      $this->buildResponsiveImage($attributes, $settings);
    }

    // If no picture found.
    if (empty($settings['picture'])) {
      $this->buildImage($attributes, $item_attributes, $settings, $item);
    }

    // Provides media type and switcher attributes for JS works.
    $attributes['class'][] = 'media--' . $settings['type'];
    if ($settings['media_switch']) {
      $attributes['class'][] = 'media--switch media--switch--' . str_replace('_', '-', $settings['media_switch']);
    }

    $element['#item_attributes'] = $item_attributes;
  }

  /**
   * Build out Responsive image.
   */
  private function buildResponsiveImage(array &$attributes, array &$settings) {
    if ($mappings = picture_mapping_load($settings['responsive_image_style'])) {
      $settings['picture'] = picture_get_mapping_breakpoints($mappings, $settings['image_style']);
      $attributes['class'][] = 'media--picture';
    }
  }

  /**
   * Build out image, or anything related, including cache, CSS background, etc.
   */
  private function buildImage(array &$attributes, array &$item_attributes, array &$settings, $item = NULL) {
    // Aspect ratio to fix layout reflow with lazyloaded images responsively.
    // This is outside 'lazy' to allow non-lazyloaded iframes use this too.
    $settings['ratio'] = empty($settings['width']) ? '' : $settings['ratio'];
    if ($settings['ratio']) {
      Blazy::aspectRatioAttributes($attributes, $settings);
    }

    // Supports both lazyloaded or regular image.
    $item_attributes['class'][] = 'media__element';

    // Overrides lazy with blazy for explicit call to reduce another param.
    if (!empty($settings['blazy'])) {
      $settings['lazy'] = 'blazy';
    }

    if ($settings['lazy']) {
      $item_attributes['src'] = empty($settings['placeholder']) ? Blazy::PLACEHOLDER : $settings['placeholder'];
      if ($settings['use_loading']) {
        $attributes['class'][] = 'media--loading';
      }

      // Attach data attributes to either IMG tag, or DIV as background.
      if ($settings['background']) {
        Blazy::lazyAttributes($attributes, $settings);
        Blazy::buildBreakpointAttributes($attributes, $settings, $item);
        $attributes['class'][] = 'b-bg media--background';

        if (!empty($settings['urls'])) {
          $attributes['data-backgrounds'] = drupal_json_encode($settings['urls']);
        }
      }
      else {
        Blazy::lazyAttributes($item_attributes, $settings);
        Blazy::buildBreakpointAttributes($item_attributes, $settings, $item);
      }

      // Multi-breakpoint aspect ratio only applies if lazyloaded.
      if (!empty($settings['blazy_data']['dimensions'])) {
        $attributes['data-dimensions'] = drupal_json_encode($settings['blazy_data']['dimensions']);
      }
    }
  }

  /**
   * Build thumbnails, also to provide placeholder for blur effect.
   */
  protected function thumbnailAndPlaceholder(array &$attributes, array &$settings) {
    $path = $style = '';
    // With CSS background, IMG may be empty, add thumbnail to the container.
    if (!empty($settings['thumbnail_style'])) {
      $path = image_style_path($settings['thumbnail_style'], $settings['uri']);
      $style = image_style_load($settings['thumbnail_style']);
      $attributes['data-thumb'] = image_style_url($settings['thumbnail_style'], $settings['uri']);
    }

    // Supports unique thumbnail different from main image, such as logo for
    // thumbnail and main image for company profile.
    if (!empty($settings['thumbnail_uri'])) {
      $path = $settings['thumbnail_uri'];
      $attributes['data-thumb'] = file_create_url($path);
    }

    if (isset($style) && ($path && !is_file($path) && file_valid_uri($path))) {
      image_style_create_derivative($style, $settings['uri'], $path);
    }

    // Provides image effect if so configured.
    if (!empty($settings['fx'])) {
      $this->createPlaceholder($settings, $style, $path);
      $attributes['class'][] = 'media--fx--' . str_replace('_', '-', $settings['fx']);
    }
  }

  /**
   * Build thumbnails, also to provide placeholder for blur effect.
   */
  protected function createPlaceholder(array &$settings, $style = NULL, $path = '') {
    if (empty($path) && ($style = image_style_load('thumbnail')) && file_valid_uri($settings['uri'])) {
      $path = image_style_path('thumbnail', $settings['uri']);
    }

    if ($path && file_valid_uri($path)) {
      // Ensures the thumbnail exists before creating a dataURI.
      if (!is_file($path) && isset($style)) {
        image_style_create_derivative($style, $settings['uri'], $path);
      }

      // Overrides placeholder with data URI based on configured thumbnail.
      if (is_file($path)) {
        $settings['placeholder'] = 'data:image/' . pathinfo($path, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($path));
      }
    }
  }

  /**
   * Build captions for both old image, or media entity.
   */
  public function buildCaption(array $captions, array $settings) {
    $content = [];
    foreach ($captions as $key => $caption_content) {
      if ($caption_content) {
        // Sanitization is performed by implementors (formatters).
        $content[$key]['content'] = $caption_content;
        $content[$key]['tag'] = strpos($key, 'title') !== FALSE ? 'h2' : 'div';
        $class = $key == 'alt' ? 'description' : str_replace('field_', '', $key);
        $content[$key]['attributes'] = [];
        $content[$key]['attributes']['class'][] = $settings['item_id'] . '__caption--' . str_replace('_', '-', $class);
      }
    }

    return $content ? ['inline' => $content] : [];
  }

  /**
   * Returns the entire contents using theme_field(), or theme_item_list().
   *
   * @param array $build
   *   The array containing: settings, children elements, or optional items.
   *
   * @return array
   *   The alterable and renderable array of contents.
   */
  public function build(array $build = []) {
    $settings = &$build['settings'];
    $settings['_grid'] = isset($settings['_grid']) ? $settings['_grid'] : (!empty($settings['style']) && !empty($settings['grid']));

    // If not a grid, pass the items as regular index children to theme_field().
    // Separated since #pre_render doesn't work if called from Views results.
    if (empty($settings['_grid'])) {
      $settings = $this->prepareBuild($build);
      $build['#blazy'] = $settings;
      $this->setAttachments($build, $settings);
    }
    else {
      // Take over theme_field() with a theme_item_list(), if so configured.
      // The reason: this is not only fed by field items, but also Views rows.
      $content = [
        '#build'      => $build,
        '#pre_render' => ['blazy_pre_render_build'],
      ];

      // Yet allows theme_field(), if so required, such as for linked_field.
      $build = empty($settings['use_field']) ? $content : [$content];
    }

    drupal_alter('blazy_build', $build, $settings);
    return $build;
  }

  /**
   * Builds the Blazy outputs as a structured array ready for ::renderer().
   */
  public function preRenderBuild(array $element) {
    $build = $element['#build'];
    unset($element['#build']);

    // Checks if we got some signaled attributes.
    $commerce = isset($element['#ajax_replace_class']);
    $attributes = isset($element['#attributes']) ? $element['#attributes'] : [];
    $attributes = isset($element['#theme_wrappers'], $element['#theme_wrappers']['container']['#attributes']) ? $element['#theme_wrappers']['container']['#attributes'] : $attributes;
    $settings = $this->prepareBuild($build);

    // Take over elements for a grid display as this is all we need, learned
    // from the issues such as: #2945524, or product variations.
    // We'll selectively pass or work out $attributes far below.
    $element = BlazyGrid::build($build, $settings);
    $this->setAttachments($element, $settings);

    if ($attributes) {
      // Signals other modules if they want to use it.
      // Cannot merge it into BlazyGrid (wrapper_)attributes, done as grid.
      // Use case: Product variations, best served by ElevateZoom Plus.
      if ($commerce) {
        $element['#container_attributes'] = $attributes;
      }
      else {
        // Use case: VIS, can be blended with UL element safely down here.
        $element['#attributes'] = NestedArray::mergeDeep($element['#attributes'], $attributes);
      }
    }

    return $element;
  }

  /**
   * Provides attachment and cache for both theme_field() and theme_item_list().
   */
  private function setAttachments(array &$element, array $settings) {
    $attachments = $this->attach($settings);
    $element['#attached'] = empty($element['#attached']) ? $attachments : NestedArray::mergeDeep($element['#attached'], $attachments);
  }

  /**
   * Prepares Blazy outputs, extract items, and returns updated $settings.
   */
  protected function prepareBuild(array &$build) {
    // If children are stored within items, reset.
    // Blazy comes late to the party after sub-modules decided what they want.
    $settings = isset($build['settings']) ? $build['settings'] : [];
    $settings += $this->getCommonSettings() + BlazyDefault::htmlSettings();
    $build = isset($build['items']) ? $build['items'] : $build;

    // Supports Blazy multi-breakpoint images if provided, updates $settings.
    // Cases: Blazy within Views gallery, or references without direct image.
    if (!empty($settings['first_image']) && !empty($settings['check_blazy'])) {
      // Views may flatten out the array, bail out.
      // What we do here is extract the formatter settings from the first found
      // image and pass its settings to this container so that Blazy Grid which
      // lacks of settings may know if it should load/ display a lightbox, etc.
      // Lightbox should work without `Use field template` checked.
      if (is_array($settings['first_image'])) {
        $this->isBlazy($settings, $settings['first_image']);
      }
    }

    unset($build['items'], $build['settings']);
    return $settings;
  }

}
