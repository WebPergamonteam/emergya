<?php

/**
 * @file
 * Default theme implementation to display a single Drupal page.
 *
 * The doctype, html, head and body tags are not in this template. Instead they
 * can be found in the html.tpl.php template in this directory.
 *
 * Available variables:
 *
 * General utility variables:
 * - $base_path: The base URL path of the Drupal installation. At the very
 *   least, this will always default to /.
 * - $directory: The directory the template is located in, e.g. modules/system
 *   or themes/bartik.
 * - $is_front: TRUE if the current page is the front page.
 * - $logged_in: TRUE if the user is registered and signed in.
 * - $is_admin: TRUE if the user has permission to access administration pages.
 *
 * Site identity:
 * - $front_page: The URL of the front page. Use this instead of $base_path,
 *   when linking to the front page. This includes the language domain or
 *   prefix.
 * - $logo: The path to the logo image, as defined in theme configuration.
 * - $site_name: The name of the site, empty when display has been disabled
 *   in theme settings.
 * - $site_slogan: The slogan of the site, empty when display has been disabled
 *   in theme settings.
 *
 * Navigation:
 * - $main_menu (array): An array containing the Main menu links for the
 *   site, if they have been configured.
 * - $secondary_menu (array): An array containing the Secondary menu links for
 *   the site, if they have been configured.
 * - $breadcrumb: The breadcrumb trail for the current page.
 *
 * Page content (in order of occurrence in the default page.tpl.php):
 * - $title_prefix (array): An array containing additional output populated by
 *   modules, intended to be displayed in front of the main title tag that
 *   appears in the template.
 * - $title: The page title, for use in the actual HTML content.
 * - $title_suffix (array): An array containing additional output populated by
 *   modules, intended to be displayed after the main title tag that appears in
 *   the template.
 * - $messages: HTML for status and error messages. Should be displayed
 *   prominently.
 * - $tabs (array): Tabs linking to any sub-pages beneath the current page
 *   (e.g., the view and edit tabs when displaying a node).
 * - $action_links (array): Actions local to the page, such as 'Add menu' on the
 *   menu administration interface.
 * - $feed_icons: A string of all feed icons for the current page.
 * - $node: The node object, if there is an automatically-loaded node
 *   associated with the page, and the node ID is the second argument
 *   in the page's path (e.g. node/12345 and node/12345/revisions, but not
 *   comment/reply/12345).
 *
 * Regions:
 * - $page['help']: Dynamic help text, mostly for admin pages.
 * - $page['highlighted']: Items for the highlighted content region.
 * - $page['content']: The main content of the current page.
 * - $page['sidebar_first']: Items for the first sidebar.
 * - $page['sidebar_second']: Items for the second sidebar.
 * - $page['header']: Items for the header region.
 * - $page['footer']: Items for the footer region.
 *
 * @see template_preprocess()
 * @see template_preprocess_page()
 * @see template_process()
 * @see html.tpl.php
 *
 * @ingroup themeable
 */
?>
<header>
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-2 col-md-6 col-sm-6 col-7">
        <a href="<?php print $front_page; ?>" title="<?php print t('Home'); ?>" rel="home" id="logo">
          <img class="logo-wrapper" src="<?php print $logo; ?>" alt="<?php print t('Home'); ?>" />
        </a>
      </div>

      <div class="col-lg-9 col-md-1 col-sm-1 col-1">
        <?php if ($page['nav_main']): ?>
          <div class="main-nav">
              <?php print render($page['nav_main']); ?>
          </div>              
        <?php endif; ?>
      </div>

      <div class="col-lg-1 col-md-5 col-sm-5 col-4">
        <?php if ($page['nav_additional']): ?>
          <div class="nav-additional">
              <?php print render($page['nav_additional']); ?>
              </div>                 
        <?php endif; ?>
      </div>
    </div>
  </div>
</header>

<main role="main">
  <a id="main-content"></a>
  <div class="main-wrapper">
    <div class="container-fluid">
      <div class="row">
          <?php print render($title_prefix); ?>
          <?php if ($title): ?><h1 class="title" id="page-title"><?php print $title; ?></h1><?php endif; ?>
          <?php print render($title_suffix); ?>
          <!-- <?php if ($tabs): ?><div class="tabs"><?php print render($tabs); ?></div><?php endif; ?>
          <?php print render($page['help']); ?>
          <?php if ($action_links): ?><ul class="action-links"><?php print render($action_links); ?></ul><?php endif; ?>
          <?php print render($page['content']); ?>
          <?php print $feed_icons; ?> -->
      </div>
    </div>
  </div>
</main>

<div class="container">
  <?php if ($page['slick_slider']): ?>
        <?php print render($page['slick_slider']); ?>
  <?php endif; ?>
</div>

<div class="container">
  <?php if ($page['blog_block']): ?>
        <?php print render($page['blog_block']); ?>
  <?php endif; ?>
</div>

<footer class="site-footer">
  <div class="container-fluid">
    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-6 col-12">
          <div class="footer-logo-wrapper">
            <a href="#" title="Home">
              <span>Logo Emergya</span>
            </a>
          </div>
        </div>
        <div class="col-lg-6 col-md-6 col-sm-6 col-12">
          <div class="social-media-wrapper">
            <div class="social-media">
              <div class="ico-facebook"><a href="https://es.linkedin.com/company/emergya" title="Facebook">Facebook</a></div>
              <div class="ico-twitter"><a href="https://twitter.com/emergya" title="Twitter">Twitter</a></div>
            </div>
          </div> 
        </div>
    </div>
  </div>
</footer>