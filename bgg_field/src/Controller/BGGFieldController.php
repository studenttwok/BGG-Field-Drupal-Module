<?php

namespace Drupal\bgg_field\Controller;

use Drupal\Core\Controller\ControllerBase;

class BGGFieldController extends ControllerBase{

  public function description() {
    $template_path = $this->getDescriptionTemplatePath();
    $template = file_get_contents($template_path);
    $build = [
      'description' => [
        '#type' => 'inline_template',
        '#template' => $template,
        '#context' => $this->getDescriptionVariables(),
      ],
    ];
    return $build;
  }
  
   /**
   * Name of our module.
   *
   * @return string
   *   A module name.
   */
  protected function getModuleName() {
    return 'bgg_field';
  }

  /**
   * Variables to act as context to the twig template file.
   *
   * @return array
   *   Associative array that defines context for a template.
   */
  protected function getDescriptionVariables() {
    $variables = [
      'module' => $this->getModuleName(),
    ];
    return $variables;
  }

  /**
   * Get full path to the template.
   *
   * @return string
   *   Path string.
   */
  protected function getDescriptionTemplatePath() {
    return \Drupal::service('extension.list.module')
      ->getPath($this->getModuleName()) . '/templates/description.html.twig';
  }
  
}
