# Admin — Niveaux de validation

## Vue d'ensemble

Les niveaux de validation définissent le **circuit d'approbation** des commandes. Ils sont parcourus séquentiellement, du niveau le plus bas (ordre 1) au plus élevé. L'application supporte un nombre illimité de niveaux.

**Contrôleur** : `app/Http/Controllers/Admin/ValidationLevelController.php`  
**Accès** : Admin uniquement

---

## Champs

| Champ | Type | Obligatoire | Contrainte | Description |
|-------|------|:-----------:|------------|-------------|
| Nom | string (255) | Oui | — | Ex : "Manager", "Direction", "Finance" |
| Ordre | integer | Oui | Unique, ≥ 1 | Détermine la séquence de passage |
| Description | string (500) | Non | — | Précisions sur le rôle de ce niveau |

---

## Séquence de validation

Les commandes soumises traversent les niveaux dans l'ordre croissant :

```
Ordre 1 → Ordre 2 → Ordre 3 → ... → Approuvée
```

Le système identifie le niveau suivant via `ValidationLevel::nextAfter($currentOrder)` (requête `order > $currentOrder` ORDER BY order ASC LIMIT 1).

Le premier niveau (`order = 1`) est automatiquement assigné à toute nouvelle commande soumise.

---

## Règles métier

- L'**ordre** est unique : deux niveaux ne peuvent pas avoir le même numéro.
- L'ordre doit être un entier ≥ 1.
- **Suppression impossible** si des validateurs sont assignés à ce niveau.
- Si un seul niveau existe, une commande approuvée par ce niveau est directement validée.
- L'application suggère automatiquement le prochain ordre disponible lors de la création (`max(order) + 1`).

---

## Lien avec les utilisateurs

Chaque utilisateur de rôle `validateur` est associé à **exactement un** niveau de validation via `validation_level_id`. Il ne voit et ne peut traiter que les commandes au niveau correspondant.

Un admin peut valider à n'importe quel niveau.

---

## Exemple de configuration typique

| Ordre | Nom | Qui valide |
|-------|-----|------------|
| 1 | Manager | Responsables d'équipe |
| 2 | Direction | Directeurs de département |
| 3 | Finance | Contrôleur financier |

---

## Données affichées dans la liste

- Ordre, Nom, Description
- Nombre de validateurs assignés à ce niveau

---

## Routes

| Méthode | URL | Nom | Action |
|---------|-----|-----|--------|
| GET | `/admin/validation-levels` | `admin.validation-levels.index` | Liste |
| GET | `/admin/validation-levels/create` | `admin.validation-levels.create` | Formulaire création |
| POST | `/admin/validation-levels` | `admin.validation-levels.store` | Enregistrement |
| GET | `/admin/validation-levels/{id}/edit` | `admin.validation-levels.edit` | Formulaire édition |
| PUT | `/admin/validation-levels/{id}` | `admin.validation-levels.update` | Mise à jour |
| DELETE | `/admin/validation-levels/{id}` | `admin.validation-levels.destroy` | Suppression |
