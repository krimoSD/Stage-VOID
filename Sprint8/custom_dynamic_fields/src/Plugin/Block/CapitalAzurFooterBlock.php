<?php

namespace Drupal\custom_dynamic_fields\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Capital Azur footer block.
 *
 * @Block(
 *   id = "capital_azur_footer_block",
 *   admin_label = @Translation("Capital Azur Footer"),
 *   category = @Translation("Capital Azur")
 * )
 */
class CapitalAzurFooterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'social_linkedin_url' => '#',
      'social_youtube_url' => '#',
      'social_twitter_url' => '#',
      'nav_1_label' => 'NOUS CONTACTER',
      'nav_1_url' => '#',
      'nav_2_label' => 'MENTIONS LEGALES',
      'nav_2_url' => '#',
      'nav_3_label' => 'PLAN DU SITE',
      'nav_3_url' => '#',
      'copyright_text' => '@2019 CAPITAL AZUR',
      'dev_text' => 'Conception et developpement',
      'dev_url' => '#',
      'dev_badge_text' => 'VOID',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['social_linkedin_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('LinkedIn URL'),
      '#default_value' => $config['social_linkedin_url'] ?? '#',
    ];
    $form['social_youtube_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('YouTube URL'),
      '#default_value' => $config['social_youtube_url'] ?? '#',
    ];
    $form['social_twitter_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter URL'),
      '#default_value' => $config['social_twitter_url'] ?? '#',
    ];

    $form['nav_1_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nav 1 label'),
      '#default_value' => $config['nav_1_label'] ?? '',
    ];
    $form['nav_1_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nav 1 URL'),
      '#default_value' => $config['nav_1_url'] ?? '#',
    ];
    $form['nav_2_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nav 2 label'),
      '#default_value' => $config['nav_2_label'] ?? '',
    ];
    $form['nav_2_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nav 2 URL'),
      '#default_value' => $config['nav_2_url'] ?? '#',
    ];
    $form['nav_3_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nav 3 label'),
      '#default_value' => $config['nav_3_label'] ?? '',
    ];
    $form['nav_3_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nav 3 URL'),
      '#default_value' => $config['nav_3_url'] ?? '#',
    ];

    $form['copyright_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Copyright text'),
      '#default_value' => $config['copyright_text'] ?? '',
    ];
    $form['dev_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Development text'),
      '#default_value' => $config['dev_text'] ?? '',
    ];
    $form['dev_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Development URL'),
      '#default_value' => $config['dev_url'] ?? '#',
    ];
    $form['dev_badge_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Development badge text'),
      '#default_value' => $config['dev_badge_text'] ?? 'VOID',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['social_linkedin_url'] = $form_state->getValue('social_linkedin_url');
    $this->configuration['social_youtube_url'] = $form_state->getValue('social_youtube_url');
    $this->configuration['social_twitter_url'] = $form_state->getValue('social_twitter_url');
    $this->configuration['nav_1_label'] = $form_state->getValue('nav_1_label');
    $this->configuration['nav_1_url'] = $form_state->getValue('nav_1_url');
    $this->configuration['nav_2_label'] = $form_state->getValue('nav_2_label');
    $this->configuration['nav_2_url'] = $form_state->getValue('nav_2_url');
    $this->configuration['nav_3_label'] = $form_state->getValue('nav_3_label');
    $this->configuration['nav_3_url'] = $form_state->getValue('nav_3_url');
    $this->configuration['copyright_text'] = $form_state->getValue('copyright_text');
    $this->configuration['dev_text'] = $form_state->getValue('dev_text');
    $this->configuration['dev_url'] = $form_state->getValue('dev_url');
    $this->configuration['dev_badge_text'] = $form_state->getValue('dev_badge_text');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'capital_azur_footer_block',
      '#social_linkedin_url' => $this->configuration['social_linkedin_url'] ?? '#',
      '#social_youtube_url' => $this->configuration['social_youtube_url'] ?? '#',
      '#social_twitter_url' => $this->configuration['social_twitter_url'] ?? '#',
      '#nav_1_label' => $this->configuration['nav_1_label'] ?? 'NOUS CONTACTER',
      '#nav_1_url' => $this->configuration['nav_1_url'] ?? '#',
      '#nav_2_label' => $this->configuration['nav_2_label'] ?? 'MENTIONS LEGALES',
      '#nav_2_url' => $this->configuration['nav_2_url'] ?? '#',
      '#nav_3_label' => $this->configuration['nav_3_label'] ?? 'PLAN DU SITE',
      '#nav_3_url' => $this->configuration['nav_3_url'] ?? '#',
      '#copyright_text' => $this->configuration['copyright_text'] ?? '@2019 CAPITAL AZUR',
      '#dev_text' => $this->configuration['dev_text'] ?? 'Conception et developpement',
      '#dev_url' => $this->configuration['dev_url'] ?? '#',
      '#dev_badge_text' => $this->configuration['dev_badge_text'] ?? 'VOID',
      '#attached' => [
        'library' => [
          'custom_dynamic_fields/ca_header_tailwind',
        ],
      ],
    ];
  }

}
