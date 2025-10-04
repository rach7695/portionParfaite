<?php

namespace App\Service;

class AmazonAffiliationService
{
    private string $amazonTag;
    private string $baseUrl = 'https://www.amazon.fr';

    public function __construct(string $amazonTag = 'votre-tag-21')
    {
        $this->amazonTag = $amazonTag;
    }
    
    /**
     * Génère un lien d'affiliation Amazon pour un produit
     */
    public function genererLienAffilie(string $produit): string
    {
        $motsCles = $this->optimiserMotsCles($produit);
        
        $parametres = [
            'k' => $motsCles,
            'tag' => $this->amazonTag,
            'linkCode' => 'ur2',
            'linkId' => uniqid(), // ID unique pour le tracking
            'camp' => '1642',
            'creative' => '6746'
        ];
        
        return $this->baseUrl . '/s?' . http_build_query($parametres);
    }

    /**
     * Optimise les mots-clés pour améliorer les résultats Amazon
     */
    private function optimiserMotsCles(string $produit): string
    {
        // Supprime les mentions de quantité et grammes
        $produit = preg_replace('/\(\d+g?\/pers\)/', '', $produit);
        $produit = preg_replace('/\d+\s*g$/', '', $produit);
        
        // Mappings pour améliorer les résultats de recherche
        $mappings = [
            'Bouteilles de vin rouge' => 'vin rouge',
            'Bouteilles de vin blanc' => 'vin blanc',
            'Bouteilles de vin rosé' => 'vin rosé',
            'Bouteilles de champagne' => 'champagne',
            'Bouteilles d\'eau' => 'eau minérale',
            'Bouteilles de soda' => 'soda coca cola',
            'Bouteilles de jus de fruits' => 'jus de fruits',
            'Batonnets de crudités' => 'légumes crudités',
            'Tzatziki, houmous et/ou tapenade' => 'houmous tapenade tzatziki',
            'Olives (vertes, noires, marinées)' => 'olives apéritif',
        ];

        // Recherche une correspondance exacte
        if (isset($mappings[$produit])) {
            return $mappings[$produit];
        }

        // Nettoyage général
        $produit = str_replace(['(', ')', '/'], ' ', $produit);
        $produit = preg_replace('/\s+/', ' ', $produit);
        
        return trim($produit);
    }

    /**
     * Génère des liens spécialisés selon la catégorie
     */
    public function genererLienSpecialise(string $produit, string $typeEvenement): string
    {
        $motsCles = $this->optimiserMotsCles($produit);
        
        // Ajoute des mots-clés contextuels selon l'événement
        $contexte = match($typeEvenement) {
            'mariage' => $motsCles . ' mariage réception',
            'bapteme' => $motsCles . ' fête famille',
            'birthday' => $motsCles . ' anniversaire fête',
            'apero' => $motsCles . ' apéritif',
            default => $motsCles
        };
        
        return $this->genererLienAffilie($contexte);
    }

    /**
     * Crée un lien vers une catégorie Amazon spécifique
     */
    public function genererLienCategorie(string $categorie): string
    {
        $categories = [
            'vin' => 'node=537504031',
            'champagne' => 'node=537504031&rh=n%3A537504031%2Cp_n_feature_twenty-one_browse-bin%3A14876845031',
            'aperitif' => 'node=537504031',
            'epicerie' => 'node=340858031',
            'boissons' => 'node=340862031'
        ];
        
        $nodeParam = $categories[$categorie] ?? 'node=340858031';
        
        return $this->baseUrl . '/s?' . http_build_query([
            'tag' => $this->amazonTag,
            'linkCode' => 'ur2'
        ]) . '&' . $nodeParam;
    }

    /**
     * Génère un bouton HTML avec lien d'affiliation
     */
    public function genererBoutonAchat(string $produit, string $typeEvenement = null, array $options = []): string
    {
        $lien = $typeEvenement ? 
            $this->genererLienSpecialise($produit, $typeEvenement) : 
            $this->genererLienAffilie($produit);
            
        $classe = $options['class'] ?? 'btn btn-outline-success btn-sm';
        $texte = $options['text'] ?? 'Acheter sur Amazon';
        $icone = $options['icon'] ?? '<i class="bi bi-cart-plus"></i>';
        
        return sprintf(
            '<a href="%s" target="_blank" class="%s" rel="nofollow noopener">%s %s</a>',
            htmlspecialchars($lien),
            htmlspecialchars($classe),
            $icone,
            htmlspecialchars($texte)
        );
    }

    /**
     * Track les clics pour analytics (optionnel)
     */
    public function trackClic(string $produit, string $typeEvenement): void
    {
        // Vous pouvez logger les clics pour analyser les performances
        // file_put_contents('amazon_clicks.log', 
        //     date('Y-m-d H:i:s') . " - {$typeEvenement} - {$produit}\n", 
        //     FILE_APPEND
        // );
    }

    /**
     * Configure le tag Amazon (à appeler depuis un .env)
     */
    public function setAmazonTag(string $tag): void
    {
        $this->amazonTag = $tag;
    }

    public function getAmazonTag(): string
    {
        return $this->amazonTag;
    }
}