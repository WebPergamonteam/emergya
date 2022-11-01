
# <a name="top"> </a>CONTENTS OF THIS FILE

 * [Introduction](#introduction)
 * [Requirements](#requirements)
 * [Recommended modules](#recommended-modules)
 * [Installation](#installation)
 * [Configuration](#configuration)
 * [Features](#features)
 * [Updating](#updating)
 * [Troubleshooting](#troubleshooting)
 * [FAQ](#faq)
 * [Aspect ratio template](#aspect-ratio-template)
 * [Contribution](#contribution)
 * [Maintainers](#maintainers)  

.
***
***
.
# <a name="introduction"></a>INTRODUCTION
Provides integration with bLazy and or Intersection Observer API to lazy load
and multi-serve images to save bandwidth and server requests. The user will have
faster load times and save data usage if they don't browse the whole page.  

.
***
***
.
# <a name="requirements"> </a>REQUIREMENTS
1. bLazy library:
   * [Download bLazy](https://github.com/dinbror/blazy)
   * Extract it as is, rename **blazy-master** to **blazy**, so the assets are:

      + **/sites/../libraries/blazy/blazy.min.js**

2. PHP 5.6+ (let us know if Blazy can go lower?)

3. Any of autoloader modules:
   * [registry_autoload](https://www.drupal.org/project/registry_autoload)
   * [psr0](https://www.drupal.org/project/psr0)
   * [xautoload](https://www.drupal.org/project/xautoload)
   * [autoload](https://www.drupal.org/project/autoload)
   * Others?

  Skip if you already one have, I meant, have one. If you have others, please
  create a feature to include it in the module `hook_requirements()`. The order
  implies the recommendation priority.


.
***
***
.
# <a name="installation"> </a>INSTALLATION

[Installing Drupal 7 module](http://drupal.org/documentation/install/modules-themes/modules-7)

### Two steps below are crucial, otherwise Blazy complains missing dependencies:
1. Visit **/admin/modules** and install one of autoloader as mentioned above.

   Save! Do not install Blazy, yet, since Blazy has no hard dependency on any.
   The order above indicates priority. The first found will be locked.
   You can install another, and uninstall the other which suits you better.

2. Install Blazy. Once an autoloader is installed, it will be locked for
   Blazy usage later to avoid accidental removal.

As long as you stick to these 2 steps, it should be just fine like regular
modules with hard dependencies.

### Known issues:
* **autoload**: must run `drush aur` and `drush cc` on Blazy activation, or
  fatal. The same procedure applies whenever blazy-related modules are
  activated, or adding new classes. Especially during DEV, Alpha, Beta,
  before RC. If not using drush, consider the other two:

  **registry_autoload**, **xautoload**.

If any issue with other autoloaders, kindly let us know. Blazy doesn't have a
hard dependency on any, it is on your own discretion.

**At any rate, solution is available:**

* Before any update, open `/admin/config/development/performance`, so to clear
  cache easily before running into issues.
* Know how to run `drush cc all` or clear cache.
* Only **at worst** case, clear registry, or if you don't drush, install and
  know how to use registry_rebuild.module safely.

This particular issue never happens at D8, please bear with D7, and current
Blazy limitation and development process.

### <del>WTF</del> FTW?
Blazy uses classes as a direct backport of 8.x.


.
***
***
.
# <a name="features"> </a>FEATURES
* Supports core Image.
* Supports Picture.
* Supports Colorbox/ Photobox/ PhotoSwipe, etc, also multimedia lightboxes.
* Multi-serving lazyloaded images, including multi-breakpoint CSS backgrounds.
* Lazyload video iframe urls via custom coded, or Media.
* Supports inline images and iframes with lightboxes, and grid or CSS3 Masonry
  via Blazy Filter. Enable Blazy filter at **/admin/config/content/formats**,
  and check out instruction at **/filter/tips**.
* Blazy Grid formatter for Image, Media and Text with multi-value.
* Delay loading for below-fold images until 100px (configurable) before they are
  visible at viewport.
* A simple effortless CSS loading indicator.
* It doesn't take over all images, so it can be enabled as needed via Blazy
  formatters, or its supporting modules.


## OPTIONAL FEATURES
* Views fields for File Entity and Media integration, see Slick Browser.
* Views style plugin Blazy Grid for Grid Foundation or CSS3 Masonry.
* Field formatters: Blazy with Media integration.


.
***
***
.
# <a name="configuration"> </a>CONFIGURATION
Be sure to enable Blazy UI which can be uninstalled at production later.

* Go to Manage display page, e.g.:

  [Admin page displays](/admin/structure/types/manage/page/display)

* Find **Blazy** formatter under **Manage display**.

* Go to [Blazy UI](/admin/config/media/blazy) to manage few global options,
  including enabling support to bring Picture into blazy-related formatters.


### USAGES: BLAZY FOR MULTIMEDIA GALLERY VIA VIEWS UI
1. Add a Views style **Blazy Grid** for entities containing Media or Image.
2. Add a Blazy formatter for the Media or Image field.
3. Add any lightbox under **Media switcher** option.
4. Limit the values to 1 under **Multiple field settings** > **Display**.
5. Be sure to leave **Use field template** under **Style settings** unchecked.
   If checked, the gallery is locked to a single entity, that is no Views
   gallery, but gallery per field.

Check out the relevant sub-module docs for details.


.
***
***
.
# <a name="recommended-modules"> </a>RECOMMENDED MODULES
* [Markdown](https://www.drupal.org/project/markdown)

  To make reading this README a breeze at [Blazy help](/admin/help/blazy_ui)

## MODULES THAT INTEGRATE WITH OR REQUIRE BLAZY
* [Ajaxin](https://www.drupal.org/project/ajaxin)
* [Intersection Observer](https://www.drupal.org/project/io)
* [Blazy PhotoSwipe](https://www.drupal.org/project/blazy_photoswipe)
* [GridStack](https://www.drupal.org/project/gridstack)
* [Outlayer](https://www.drupal.org/project/outlayer)
* [Intense](https://www.drupal.org/project/intense)
* [Mason](https://www.drupal.org/project/mason)
* [Slick](https://www.drupal.org/project/slick)
* [Slick Lightbox](https://www.drupal.org/project/slick_lightbox)
* [Slick Views](https://www.drupal.org/project/slick_views)
* [Slick Paragraphs](https://www.drupal.org/project/slick_paragraphs)
* [Jumper](https://www.drupal.org/project/jumper)
* [Zooming](https://www.drupal.org/project/zooming)
* [ElevateZoom Plus](https://www.drupal.org/project/elevatezoomplus)


Most duplication efforts from the above modules will be merged into
\Drupal\blazy\Dejavu or anywhere else namespace.

**What dups?**

The most obvious is the removal of formatters from Intense, Zooming,
Slick Lightbox, Blazy PhotoSwipe, and other (quasi-)lightboxes. Any lightbox
supported by Blazy can use Blazy, or Slick formatters if applicable instead.
We do not have separate formatters when its prime functionality is embedding
a lightbox, or superceded by Blazy.

Blazy provides a versatile and reusable formatter for a few known lightboxes
with extra advantages:

lazyloading, grid, multi-serving images, Responsive image,
CSS background, captioning, etc.

Including making those lightboxes available for free at Views Field for
File entity, Media and Blazy Filter for inline images.

If you are developing lightboxes and using Blazy, I would humbly invite you
to give Blazy a try, and consider joining forces with Blazy, and help improve it
for the above-mentioned advantages. We are also continuously improving and
solidifying the API to make advanced usages a lot easier, and DX friendly.
Currently, of course, not perfect, but have been proven to play nice with at
least 7 lightboxes, and likely more.


## SIMILAR MODULES
[Lazyloader](https://www.drupal.org/project/lazyloader)


.
***
***
.
# <a name="maintainers"> </a>MAINTAINERS/CREDITS
* [Gaus Surahman](https://www.drupal.org/user/159062)
* [Contributors](https://www.drupal.org/node/2663268/committers)
* CHANGELOG.txt for helpful souls with their patches, suggestions and reports.


## READ MORE
See the project page on drupal.org:

[Blazy module](http://drupal.org/project/blazy)

See the bLazy docs at:

* [Blazy library](https://github.com/dinbror/blazy)
* [Blazy website](http://dinbror.dk/blazy/)
