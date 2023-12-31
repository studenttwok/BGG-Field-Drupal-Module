<?php
/**
 * @file
 * Contains \Drupal\bgg_field\Plugin\Field\FieldWidget\BGGFieldDefaultWidget.
 */
 
namespace Drupal\bgg_field\Plugin\Field\FieldWidget;


use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'snippets_default' widget.
 *
 * @FieldWidget(
 *   id = "bgg_field_default_widget",
 *   label = @Translation("BGG Field Form Default Widget"),
 *   field_types = {
 *     "bgg_field"
 *   }
 * )
 */
class BGGFieldDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = $items[$delta]->value ?? '';
    $element += [
      '#type' => 'textfield',
      '#default_value' => $value,
      '#size' => 7,
      '#maxlength' => 7,
      '#element_validate' => [
        [$this, 'validate'],
      ],
    ];
    return ['value' => $element];
  }

  /**
   * Validate the color text field.
   */
  public function validate($element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if (strlen($value) === 0) {
      $form_state->setValueForElement($element, '');
      return;
    }
//    if (!Color::validateHex($value)) {
//      $form_state->setError($element, $this->t('Color must be a 3- or 6-digit hexadecimal value, suitable for CSS.'));
//    }
  }

}
