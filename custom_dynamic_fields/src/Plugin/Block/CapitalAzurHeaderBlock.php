<?php

namespace Drupal\custom_dynamic_fields\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Capital Azur header block.
 *
 * @Block(
 *   id = "capital_azur_header_block",
 *   admin_label = @Translation("Capital Azur Header"),
 *   category = @Translation("Capital Azur")
 * )
 */
class CapitalAzurHeaderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'logo_text' => 'CAPITAL AZUR',
      'logo_subtitle' => 'VOTRE INVESTISSEUR AVANT-GARDE',
      'menu_1_label' => 'PRODUITS & SERVICES',
      'menu_1_url' => '#',
      'menu_2_label' => 'NOUS CONNAITRE',
      'menu_2_url' => '#',
      'menu_3_label' => 'INSIGHTS',
      'menu_3_url' => '#',
      'cta_label' => 'BANQUE DIGITALE',
      'cta_url' => '#',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['logo_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Logo text'),
      '#default_value' => $config['logo_text'] ?? '',
    ];
    $form['logo_subtitle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Logo subtitle'),
      '#default_value' => $config['logo_subtitle'] ?? '',
    ];

    $form['menu_1_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Menu 1 label'),
      '#default_value' => $config['menu_1_label'] ?? '',
    ];
    $form['menu_1_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Menu 1 URL'),
      '#default_value' => $config['menu_1_url'] ?? '',
    ];
    $form['menu_2_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Menu 2 label'),
      '#default_value' => $config['menu_2_label'] ?? '',
    ];
    $form['menu_2_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Menu 2 URL'),
      '#default_value' => $config['menu_2_url'] ?? '',
    ];
    $form['menu_3_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Menu 3 label'),
      '#default_value' => $config['menu_3_label'] ?? '',
    ];
    $form['menu_3_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Menu 3 URL'),
      '#default_value' => $config['menu_3_url'] ?? '',
    ];

    $form['cta_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CTA label'),
      '#default_value' => $config['cta_label'] ?? '',
    ];
    $form['cta_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CTA URL'),
      '#default_value' => $config['cta_url'] ?? '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['logo_text'] = $form_state->getValue('logo_text');
    $this->configuration['logo_subtitle'] = $form_state->getValue('logo_subtitle');
    $this->configuration['menu_1_label'] = $form_state->getValue('menu_1_label');
    $this->configuration['menu_1_url'] = $form_state->getValue('menu_1_url');
    $this->configuration['menu_2_label'] = $form_state->getValue('menu_2_label');
    $this->configuration['menu_2_url'] = $form_state->getValue('menu_2_url');
    $this->configuration['menu_3_label'] = $form_state->getValue('menu_3_label');
    $this->configuration['menu_3_url'] = $form_state->getValue('menu_3_url');
    $this->configuration['cta_label'] = $form_state->getValue('cta_label');
    $this->configuration['cta_url'] = $form_state->getValue('cta_url');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'capital_azur_header_block',
      '#logo_text' => $this->configuration['logo_text'] ?? 'CAPITAL AZUR',
      '#logo_subtitle' => $this->configuration['logo_subtitle'] ?? 'VOTRE INVESTISSEUR AVANT-GARDE',
      '#menu_1_label' => $this->configuration['menu_1_label'] ?? 'PRODUITS & SERVICES',
      '#menu_1_url' => $this->configuration['menu_1_url'] ?? '#',
      '#menu_2_label' => $this->configuration['menu_2_label'] ?? 'NOUS CONNAITRE',
      '#menu_2_url' => $this->configuration['menu_2_url'] ?? '#',
      '#menu_3_label' => $this->configuration['menu_3_label'] ?? 'INSIGHTS',
      '#menu_3_url' => $this->configuration['menu_3_url'] ?? '#',
      '#cta_label' => $this->configuration['cta_label'] ?? 'BANQUE DIGITALE',
      '#cta_url' => $this->configuration['cta_url'] ?? '#',
      '#attached' => [
        'library' => [
          'custom_dynamic_fields/ca_header_tailwind',
        ],
      ],
    ];
  }

}
