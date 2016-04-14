<?php

/**
 * @file
 * Contains \Drupal\component_libraries\Template\Loader\ComponentLibraryLoader.
 */

namespace Drupal\component_libraries\Template\Loader;

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
    foreach ($module_handler->getModuleList() as $name => $extension) {
      // Modules MUST declare the components -> namespace in their .info.yml
      // file or the component library will be ignored.
      if (isset($extension['info']['components']['namespace'])) {
        $namespace = $extension['info']['components']['namespace'];
        $path = $extension->getPath() . '/' . (isset($extension['info']['components']['directory']) ? $extension['info']['components']['directory'] : 'components');
        if (is_dir($path)) {
          $namespaces[$namespace] = $path;
        }
      }
    }
    foreach ($theme_handler->listInfo() as $name => $extension) {
      $namespace = isset($extension['info']['components']['namespace']) ? $extension['info']['components']['namespace'] : $name . 'Components';
      $path = $extension->getPath() . '/' . (isset($extension['info']['components']['directory']) ? $extension['info']['components']['directory'] : 'components');
      if (is_dir($path)) {
        $namespaces[$namespace] = $path;
      }
    }

    $existing_namespaces = $this->getNamespaces();

    foreach ($namespaces as $name => $path) {
      // Don't override an existing namespace.
      if (!in_array($name, $existing_namespaces)) {
        $this->addPath($path, $name);
      }
    }
  }
}
