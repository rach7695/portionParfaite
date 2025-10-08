<?php

namespace App\Service;

class AmazonAffiliationService
{
    // Produits spécifiques qui vont vers Marmiton
    private const PRODUITS_MARMITON = [
        'Mini-bouchées',
        'Toasts',
    ];
    
    // Mapping des catégories vers leurs destinations
    private const CATEGORIES = [
        'MATÉRIEL' => 'amazon',
        'CONDIMENTS' => 'amazon',
        'BOISSONS ALCOOLISÉES' => 'default', // En attente caviste
        'BOISSONS NON ALCOOLISÉES' => 'default',
        'DESSERTS' => 'default',
        'PAIN & BASES' => 'default',
        'SPÉCIAL BARBECUE' => 'default',
        'APÉRITIF' => 'default', // Géré produit par produit
        'ENFANTS (2-12 ans)' => 'default',
    ];

    /**
     * Détermine vers quel site rediriger selon la catégorie et le produit
     */
    public function genererLien(string $produit, string $categorie): ?string
    {
        // Vérifier d'abord si c'est un produit Marmiton spécifique
        foreach (self::PRODUITS_MARMITON as $produitMarmiton) {
            if (stripos($produit, $produitMarmiton) !== false) {
                return $this->genererLienMarmiton($produit);
            }
        }
        
        // Sinon, utiliser la catégorie
        $type = self::CATEGORIES[$categorie] ?? 'default';
        
        return match($type) {
            'amazon' => $this->genererLienAmazon($produit),
            'default' => null, // Pas de lien pour le moment
        };
    }

    /**
     * Génère un lien Amazon avec affiliation (à configurer avec ton ID partenaire)
     */
    private function genererLienAmazon(string $produit): string
    {
        $query = urlencode($produit);
        // TODO: Remplace 'TON_TAG_AFFILIATION' par ton vrai tag Amazon Partenaires
        return "https://www.amazon.fr/s?k={$query}&tag=TON_TAG_AFFILIATION";
    }

    /**
     * Génère un lien Marmiton pour les recettes spécifiques
     */
    private function genererLienMarmiton(string $produit): string
    {
        // Détermine la recette selon le produit
        if (stripos($produit, 'Mini-bouchées') !== false || stripos($produit, 'Mini-bouchée') !== false) {
            // Lien vers une page de recettes de mini-bouchées
            return "https://www.marmiton.org/recettes/recherche.aspx?aqt=mini-bouch%C3%A9es";
        }
        
        if (stripos($produit, 'Toast') !== false) {
            // Lien vers une page de recettes de toasts
            return "https://www.marmiton.org/recettes/recherche.aspx?aqt=bouch%C3%A9es-ap%C3%A9ritives-chaudes";
        }
        
        // Fallback : recherche générale apéritif
        return "https://www.marmiton.org/recettes/recherche.aspx?qs=ap%C3%A9ritif";
    }

    /**
     * Extrait les mots-clés pertinents d'un nom de produit
     */
    private function extraireMotsCles(string $produit): string
    {
        // Retire les informations de quantité et parenthèses
        $produit = preg_replace('/\([^)]+\)/', '', $produit);
        $produit = preg_replace('/\d+g|\/pers|pièces/', '', $produit);
        
        return trim($produit);
    }

    /**
     * Génère le bouton HTML pour le template Twig
     */
    public function genererBoutonAchat(string $produit, string $categorie, array $options = []): string
    {
        $lien = $this->genererLien($produit, $categorie);
        
        // Si pas de lien disponible, retourne un message
        if (!$lien) {
            return '<span class="text-muted small">Bientôt disponible</span>';
        }

        // Options par défaut
        $class = $options['class'] ?? 'btn btn-success btn-sm';
        $text = 'Acheter';
        $icon = '<i class="bi bi-cart-plus"></i>';
        
        // Personnalisation pour Marmiton
        foreach (self::PRODUITS_MARMITON as $produitMarmiton) {
            if (stripos($produit, $produitMarmiton) !== false) {
                $text = 'Voir recettes';
                $icon = '<i class="bi bi-book"></i>';
                $class = str_replace('btn-success', 'btn-warning', $class);
                break;
            }
        }

        return sprintf(
            '<a href="%s" target="_blank" class="%s" rel="noopener noreferrer">%s %s</a>',
            htmlspecialchars($lien),
            htmlspecialchars($class),
            $icon,
            htmlspecialchars($text)
        );
    }

    /**
     * Vérifie si un produit a un lien disponible
     */
    public function aLienDisponible(string $categorie): bool
    {
        $type = self::CATEGORIES[$categorie] ?? 'default';
        return in_array($type, ['amazon', 'marmiton']);
    }
}