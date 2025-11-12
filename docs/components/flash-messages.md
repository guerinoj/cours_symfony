# Composant Flash Messages

Ce composant Twig permet d'afficher les messages flash de manière cohérente dans toute l'application.

## Utilisation de base

```twig
{# Utilisation simple #}
{{ component('flash_messages') }}
```

## Options de configuration

```twig
{# Avec toutes les options #}
{{ component('flash_messages', {
    dismissible: true,        # Les messages peuvent être fermés (par défaut: true)
    showIcons: true,          # Afficher les icônes (par défaut: true)  
    showTitles: true,         # Afficher les titres (par défaut: false)
    containerClass: 'mb-4'    # Classes CSS du conteneur (par défaut: 'mb-3')
}) }}
```

## Types de messages supportés

- `success` : Messages de succès (vert)
- `error` : Messages d'erreur (rouge)
- `warning` : Messages d'avertissement (orange)
- `info` : Messages d'information (bleu)

## Exemples d'utilisation dans les contrôleurs

```php
// Message de succès
$this->addFlash('success', 'Article créé avec succès !');

// Message d'erreur
$this->addFlash('error', 'Une erreur est survenue.');

// Message d'avertissement
$this->addFlash('warning', 'Le formulaire contient des erreurs.');

// Message d'information
$this->addFlash('info', 'Nouvel article en cours de révision.');
```

## Intégration

Le composant est automatiquement inclus dans le template `base.html.twig` pour un affichage global, mais peut aussi être utilisé localement dans des templates spécifiques avec des options personnalisées.