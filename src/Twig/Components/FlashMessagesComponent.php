<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('flash_messages')]
final class FlashMessagesComponent
{
  /**
   * Types de messages flash supportés avec leurs configurations
   */
  private const FLASH_CONFIG = [
    'success' => [
      'alert_class' => 'alert-success',
      'icon' => 'fa-check-circle',
      'title' => 'Succès'
    ],
    'error' => [
      'alert_class' => 'alert-danger',
      'icon' => 'fa-exclamation-circle',
      'title' => 'Erreur'
    ],
    'warning' => [
      'alert_class' => 'alert-warning',
      'icon' => 'fa-exclamation-triangle',
      'title' => 'Attention'
    ],
    'info' => [
      'alert_class' => 'alert-info',
      'icon' => 'fa-info-circle',
      'title' => 'Information'
    ],
  ];

  public bool $dismissible = true;
  public bool $showIcons = true;
  public bool $showTitles = false;
  public string $containerClass = 'mb-3';

  /**
   * Retourne la configuration pour un type de message donné
   */
  public function getFlashConfig(string $type): array
  {
    return self::FLASH_CONFIG[$type] ?? self::FLASH_CONFIG['info'];
  }

  /**
   * Retourne tous les types de messages supportés
   */
  public function getSupportedTypes(): array
  {
    return array_keys(self::FLASH_CONFIG);
  }
}
