<?php

/**
 * @file
 * Contains \Drupal\components\Template\Loader\ComponentLibraryLoader.
 */

namespace Drupal\components\Template\Loader;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;

/**
 * Loads templates from the filesystem.
 *
 * This loader adds module and theme components paths as namespaces to the Twig
 * filesystem loader so that templates can be referenced by namespace, like
 * @mycomponents/box.html.twig or @mythemeComponents/page.html.twig.
 */
class ComponentLibraryLoader extends \Twig_Loader_Filesystem {

  /**
   * Constructs a new ComponentsLoader object.
   *
   * @param string|array $paths
   *   A path or an array of paths to check for templates.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler service.
   */
  public function __construct($paths = array(), ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    parent::__construct($paths);

    // Add namespaced paths for modules and themes.
    $namespaces = array();
    $existing_namespaces = array();
    foreach ($module_handler->getModuleList() as $name => $extension) {
      // The Drupal\Core\Template\Loader\FilesystemLoader makes a Twig namespace
      // for all modules and themes, so we re-create that list here.
      $existing_namespaces[] = $name;

      // For each library listed in the .info file's component_libraries
      // section, determine the namespace and the path.
      if (isset($extension->info['component_libraries'])) {
        foreach ($extension->info['component_libraries'] as $library) {
          // Modules MUST declare the namespace explicitly or the component
          // library will be ignored.
          if (isset($library['namespace'])) {
            $path = $extension->getPath() . '/' . (isset($library['directory']) ? $library['directory'] : 'components');
            if (is_dir($path)) {
              $namespaces[$library['namespace']] = $path;
            }
          }
        }
      }
    }
    foreach ($theme_handler->listInfo() as $name => $extension) {
      // The Drupal\Core\Template\Loader\FilesystemLoader makes a Twig namespace
      // for all modules and themes, so we re-create that list here.
      $existing_namespaces[] = $name;

      // For each library listed in the .info file's component_libraries
      // section, determine the namespace and the path.
      if (isset($extension->info['component_libraries'])) {
        foreach ($extension->info['component_libraries'] as $library) {
          $namespace = isset($library['namespace']) ? $library['namespace'] : $name . 'Components';
          $path = $extension->getPath() . '/' . (isset($library['directory']) ? $library['directory'] : 'components');
          if (is_dir($path)) {
            $namespaces[$namespace] = $path;
          }
        }
      }
    }

    foreach ($namespaces as $name => $path) {
      // Don't override an existing namespace.
      if (!in_array($name, $existing_namespaces)) {
        $this->addPath($path, $name);
      }
    }
  }
}
