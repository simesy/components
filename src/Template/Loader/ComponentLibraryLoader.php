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

  // Keep track of libraries that we attempt to register.
  protected $libraries = array();

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

    // The Drupal\Core\Template\Loader\FilesystemLoader makes a Twig namespace
    // for each module and theme, so we re-create that list here.
    $existing_namespaces = array();

    // Look at each module.
    foreach ($module_handler->getModuleList() as $name => $extension) {
      $existing_namespaces[] = $name;

      // For each library listed in the .info file's component_libraries
      // section, determine the namespace and the path.
      if (isset($extension->info['component_libraries'])) {
        foreach ($extension->info['component_libraries'] as $library) {
          $this->libraries[] = array(
            'type' => 'module',
            'name' => $name,
            'namespace' => isset($library['namespace']) ? $library['namespace'] : '[undefined]',
            'directory' => $extension->getPath() . '/' . (isset($library['directory']) ? $library['directory'] : 'components'),
            // Modules MUST declare the namespace explicitly or the component
            // library will be ignored.
            'error' => !isset($library['namespace']) ? 'Namespace not specified.' : false
          );
        }
      }
    }

    // Look at each theme.
    foreach ($theme_handler->listInfo() as $name => $extension) {
      $existing_namespaces[] = $name;

      // For each library listed in the .info file's component_libraries
      // section, determine the namespace and the path.
      if (isset($extension->info['component_libraries'])) {
        foreach ($extension->info['component_libraries'] as $library) {
          $this->libraries[] = array(
            'type' => 'theme',
            'name' => $name,
            'namespace' => isset($library['namespace']) ? $library['namespace'] : $name . 'Components',
            'directory' => $extension->getPath() . '/' . (isset($library['directory']) ? $library['directory'] : 'components'),
            'error' => false
          );
        }
      }
    }

    // Since the components module does not have any Twig templates, we can
    // safely let a component library override its namespace.
    $existing_namespaces = array_diff($existing_namespaces, array('components'));

    // Decide if we should register each component library found.
    foreach ($this->libraries as &$library) {
      if (!$library['error']) {
        // The component library's directory must exist.
        if (!is_dir($library['directory'])) {
          $library['error'] = 'Directory does not exist.';
        }

        // Don't override an existing namespace.
        elseif (in_array($library['namespace'], $existing_namespaces)) {
          $library['error'] = 'Namespace already exists.';
        }
      }

      // Register the Twig namespace if no errors.
      if (!$library['error']) {
        $this->addPath($library['directory'], $library['namespace']);
        $existing_namespaces[] = $library['namespace'];
      }
    }
  }
}
