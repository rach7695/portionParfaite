<?php

namespace App\Controller;

use App\Entity\Calcul;
use App\Form\CalculFormType;
use App\Service\AmazonAffiliationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CalculController extends AbstractController
{
    public function __construct(
        private AmazonAffiliationService $amazonService
    ) {}

    #[Route('/calcul', name: 'app_calcul')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $calcul = new Calcul();
        $calcul->setDateCalcul(new \DateTimeImmutable());

        $form = $this->createForm(CalculFormType::class, $calcul);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($calcul);
            $em->flush();

            return $this->redirectToRoute('app_calcul_resultat', [
                'id' => $calcul->getId()
            ]);
        }

        return $this->render('calcul/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/calcul/resultat/{id}', name: 'app_calcul_resultat', requirements: ['id' => '\d+'])]
    public function resultat(int $id, EntityManagerInterface $em): Response
    {
        // ✅ Récupération manuelle de l'entité
        $calcul = $em->getRepository(Calcul::class)->find($id);
        
        if (!$calcul) {
            throw $this->createNotFoundException('Calcul non trouvé');
        }

        // Calcul selon le type d'événement et nombre de personnes
        $nbPersonnes = $calcul->getNbPersonnes();
        $nbEnfants = $calcul->getNbEnfants();
        $typeEvenement = $calcul->getTypeEvenement();
        
        $resultats = $this->calculerQuantites($nbPersonnes, $nbEnfants, $typeEvenement, $calcul->isSansAlcool(), $calcul);

        // Sauvegarde du résultat en JSON dans l'entité
        $calcul->setResultat(json_encode($resultats));
        $em->flush();

        return $this->render('calcul/resultat.html.twig', [
            'calcul' => $calcul,
            'resultats' => $resultats,
            'amazonService' => $this->amazonService, // ✅ Passer le service au template
        ]);
    }

    /**
     * Logique de calcul basée sur votre JavaScript
     */
    private function calculerQuantites(int $nbPersonnes, int $nbEnfants, string $typeEvenement, bool $sansAlcool = false, ?Calcul $calcul = null): array
{
    $resultats = [];
    
    // Calcul des équivalents pour les quantités
    $totalNourriture = $nbPersonnes + ($nbEnfants * 0.7); // Enfants = 70% d'un adulte pour nourriture
    $totalBoissons = $nbPersonnes + ($nbEnfants * 0.5); // Enfants = 50% d'un adulte pour boissons
    $totalPersonnes = $nbPersonnes + $nbEnfants; // Total brut
    
    switch ($typeEvenement) {
        case 'mariage':
            if (!$sansAlcool) {
                $resultats['BOISSONS ALCOOLISÉES'] = [
                    'Vin rouge (75cl)' => ceil($nbPersonnes / 2) . ' bouteille(s)',
                    'Vin blanc (75cl)' => ceil($nbPersonnes / 3) . ' bouteille(s)',
                    'Rosé (75cl)' => ceil($nbPersonnes / 5) . ' bouteille(s)',
                    'Champagne (75cl)' => ceil($totalPersonnes / 7) . ' bouteille(s)', // Toast seulement
                    'Alcool fort' => ceil($nbPersonnes / 20) . ' bouteille(s)',
                ];
            }
            
            $multiplicateur = $sansAlcool ? 1.5 : 1;
            $resultats['BOISSONS NON ALCOOLISÉES'] = [
                'Soda (1,5L)' => ceil(($totalBoissons / 4) * $multiplicateur) . ' bouteille(s)',
                'Jus de fruit (1L)' => ceil(($totalBoissons / 5) * $multiplicateur) . ' bouteille(s)',
                'Eau (1,5L)' => ceil(($totalBoissons / 2) * $multiplicateur) . ' bouteille(s)',
                'Café/Thé' => ceil($nbPersonnes) . ' tasses',
            ];

            $resultats['PAIN & BASES'] = [
                'Petit pain' => ceil($totalNourriture) . ' pièces',
            ];

            $resultats['APÉRITIF'] = [
                'Toasts' => ceil(4 * $totalNourriture) . ' pièces', // 4 toasts/pers
                'Fromage' => ceil(30 * $totalNourriture) . 'g', // 30g/pers
                'Mini-bouchées' => ceil(4 * $totalNourriture) . ' pièces', // 4 mini-bouchées/pers
            ];
            // Total apéritif : 8 pièces par personne

            $resultats['DESSERTS'] = [
                'Dessert/Gâteau' => $totalPersonnes . ' parts',
                'Fruits (plateaux)' => ceil(100 * $totalNourriture) . 'g', // 100g/pers au lieu de 150g
            ];

            $resultats['MATÉRIEL'] = [
                'Serviettes en papier' => ceil(2 * $totalPersonnes) . ' pièces',
                'Assiettes jetables' => ceil(2 * $totalPersonnes) . ' pièces',
                'Gobelets' => ceil(2 * $totalPersonnes) . ' pièces',
                'Couverts' => $totalPersonnes . ' sets',
                'Nappes' => '1 par table',
            ];
            break;

        case 'bapteme':
            if (!$sansAlcool) {
                $resultats['BOISSONS ALCOOLISÉES'] = [
                    'Vin rouge (75cl)' => ceil($nbPersonnes / 3) . ' bouteille(s)',
                    'Vin blanc (75cl)' => ceil($nbPersonnes / 4) . ' bouteille(s)',
                    'Rosé (75cl)' => ceil($nbPersonnes / 6) . ' bouteille(s)',
                    'Champagne (75cl)' => ceil($totalPersonnes / 7) . ' bouteille(s)',
                ];
            }

            $multiplicateur = $sansAlcool ? 1.5 : 1;
            $resultats['BOISSONS NON ALCOOLISÉES'] = [
                'Soda (1,5L)' => ceil(($totalBoissons / 3) * $multiplicateur) . ' bouteille(s)',
                'Jus de fruit (1L)' => ceil(($totalBoissons / 4) * $multiplicateur) . ' bouteille(s)',
                'Eau (1,5L)' => ceil(($totalBoissons / 2) * $multiplicateur) . ' bouteille(s)',
                'Café/Thé' => ceil($nbPersonnes) . ' tasses',
            ];
                
            $resultats['PAIN & BASES'] = [
                'Petit pain' => ceil($totalNourriture) . ' pièces',
            ];
                
            $resultats['APÉRITIF'] = [
                'Toasts' => ceil(3 * $totalNourriture) . ' pièces', // 3 toasts
                'Chips (100g)' => ceil($totalNourriture / 5) . ' paquet(s)', // 20g/pers
                'Bonbons' => ceil(2 * $totalNourriture) . ' pièces', // 2 bonbons
                'Fromage' => ceil(25 * $totalNourriture) . 'g', // 25g/pers
                'Mini-bouchées' => ceil(3 * $totalNourriture) . ' pièces', // 3 mini-bouchées
            ];
            // Total apéritif : 8 pièces par personne

            $resultats['DESSERTS'] = [
                'Dessert/Gâteau' => $totalPersonnes . ' parts',
                'Fruits (plateaux)' => ceil(100 * $totalNourriture) . 'g',
            ];

            $resultats['MATÉRIEL'] = [
                'Serviettes en papier' => ceil(2 * $totalPersonnes) . ' pièces',
                'Assiettes jetables' => ceil(2 * $totalPersonnes) . ' pièces',
                'Gobelets' => ceil(2 * $totalPersonnes) . ' pièces',
                'Couverts' => $totalPersonnes . ' sets',
                'Nappes' => '1 par table',
            ];
            break;

        case 'birthday':
            if (!$sansAlcool) {
                $resultats['BOISSONS ALCOOLISÉES'] = [
                    'Vin rouge (75cl)' => ceil($nbPersonnes / 4) . ' bouteille(s)',
                    'Vin blanc (75cl)' => ceil($nbPersonnes / 4) . ' bouteille(s)',
                    'Rosé (75cl)' => ceil($nbPersonnes / 4) . ' bouteille(s)',
                    'Champagne (75cl)' => ceil($totalPersonnes / 7) . ' bouteille(s)',
                ];
            }
            
            $multiplicateur = $sansAlcool ? 1.5 : 1; 
            $resultats['BOISSONS NON ALCOOLISÉES'] = [
                'Soda (1,5L)' => ceil(($totalBoissons / 2.5) * $multiplicateur) . ' bouteille(s)', // Plus de soda pour anniversaire
                'Jus de fruit (1L)' => ceil(($totalBoissons / 3) * $multiplicateur) . ' bouteille(s)',
                'Eau (1,5L)' => ceil(($totalBoissons / 3) * $multiplicateur) . ' bouteille(s)',
            ];

            $resultats['APÉRITIF'] = [
                'Toasts' => ceil(2 * $totalNourriture) . ' pièces', // 2 toasts
                'Chips (100g)' => ceil($totalNourriture / 4) . ' paquet(s)', // 25g/pers
                'Bonbons' => ceil(3 * $totalNourriture) . ' pièces', // 3 bonbons
                'Fromage' => ceil(25 * $totalNourriture) . 'g', // 25g/pers
            ];
            // Total apéritif : 5 pièces (anniversaire = focus sur le gâteau)
                
            $resultats['DESSERTS'] = [
                'Crêpes' => ceil(1.5 * $totalNourriture) . ' pièces', // 1-2 crêpes/pers
                'Gâteau d\'anniversaire' => $totalPersonnes . ' parts (120g/pers)',
            ];

            $resultats['MATÉRIEL'] = [
                'Serviettes en papier' => ceil(2 * $totalPersonnes) . ' pièces',
                'Assiettes jetables' => $totalPersonnes . ' pièces',
                'Gobelets' => ceil(2 * $totalPersonnes) . ' pièces',
                'Couverts' => $totalPersonnes . ' sets',
            ];
            break;

        case 'apero':
            if (!$sansAlcool) {
                $resultats['BOISSONS ALCOOLISÉES'] = [
                    'Vin rouge (75cl)' => ceil($nbPersonnes / 4) . ' bouteille(s)',
                    'Vin blanc (75cl)' => ceil($nbPersonnes / 4) . ' bouteille(s)',
                    'Rosé (75cl)' => ceil($nbPersonnes / 4) . ' bouteille(s)',
                    'Champagne (75cl)' => ceil($nbPersonnes / 6) . ' bouteille(s)',
                    'Alcool fort' => ceil($nbPersonnes / 20) . ' bouteille(s)',
                ];
            }

            $multiplicateur = $sansAlcool ? 1.5 : 1;
            $resultats['BOISSONS NON ALCOOLISÉES'] = [
                'Soda (1,5L)' => ceil(($totalBoissons / 4) * $multiplicateur) . ' bouteille(s)',
                'Jus de fruit (1L)' => ceil(($totalBoissons / 5) * $multiplicateur) . ' bouteille(s)',
                'Eau (1,5L)' => ceil(($totalBoissons / 3) * $multiplicateur) . ' bouteille(s)',
            ];

            $resultats['PAIN & BASES'] = [
                'Baguette' => ceil($totalNourriture / 5) . ' baguette(s)', // 1 baguette pour 5 pers
            ];

            $resultats['APÉRITIF'] = [
                'Toasts' => ceil(3 * $totalNourriture) . ' pièces', // 3 toasts
                'Chips (100g)' => ceil($totalNourriture / 3) . ' paquet(s)', // 30g/pers
                'Fromage' => ceil(30 * $totalNourriture) . 'g', // 30g/pers
                'Mini-bouchées' => ceil(5 * $totalNourriture) . ' pièces', // 5 mini-bouchées
            ];
            // Total apéritif : 8 pièces par personne
            break;

        case 'diner':
            if (!$sansAlcool) {
                $resultats['BOISSONS ALCOOLISÉES'] = [
                    'Vin rouge (75cl)' => ceil($nbPersonnes / 3) . ' bouteille(s)',
                    'Vin blanc (75cl)' => ceil($nbPersonnes / 4) . ' bouteille(s)',
                    'Rosé (75cl)' => ceil($nbPersonnes / 6) . ' bouteille(s)',
                    'Champagne (75cl)' => ceil($nbPersonnes / 8) . ' bouteille(s)',
                ];
            }
            
            $multiplicateur = $sansAlcool ? 1.5 : 1;
            $resultats['BOISSONS NON ALCOOLISÉES'] = [
                'Soda (1,5L)' => ceil(($totalBoissons / 5) * $multiplicateur) . ' bouteille(s)',
                'Jus de fruit (1L)' => ceil(($totalBoissons / 6) * $multiplicateur) . ' bouteille(s)',
                'Eau (1,5L)' => ceil(($totalBoissons / 2) * $multiplicateur) . ' bouteille(s)',
                'Café/Thé' => ceil($nbPersonnes) . ' tasses',
            ];

            $resultats['PAIN & BASES'] = [
                'Baguette' => ceil($totalNourriture / 3) . ' baguette(s)', // 1 baguette pour 3 pers
            ];
            
            $resultats['PLATS'] = [
                'Entrée' => ceil($totalNourriture) . ' portions (120g/pers)',
                'Plat principal' => ceil($totalNourriture) . ' portions (250g/pers)',
                'Fromage' => ceil(50 * $totalNourriture) . 'g',
                'Dessert' => $totalPersonnes . ' portions',
            ];
            break;

        case 'barbecue':
            if (!$sansAlcool) {
                $resultats['BOISSONS ALCOOLISÉES'] = [
                    'Vin rouge (75cl)' => ceil($nbPersonnes / 4) . ' bouteille(s)',
                    'Vin blanc (75cl)' => ceil($nbPersonnes / 5) . ' bouteille(s)',
                    'Rosé (75cl)' => ceil($nbPersonnes / 4) . ' bouteille(s)', // Plus de rosé pour BBQ
                    'Bières (33cl)' => ceil($nbPersonnes * 2) . ' bouteilles', // 2 bières/adulte
                    'Alcool fort' => ceil($nbPersonnes / 15) . ' bouteille(s)',
                ];
            }

            $multiplicateur = $sansAlcool ? 1.5 : 1;
            $resultats['BOISSONS NON ALCOOLISÉES'] = [
                'Soda (1,5L)' => ceil(($totalBoissons / 2.5) * $multiplicateur) . ' bouteille(s)', // Plus de soda pour BBQ
                'Jus de fruit (1L)' => ceil(($totalBoissons / 4) * $multiplicateur) . ' bouteille(s)',
                'Eau (1,5L)' => ceil($totalBoissons) . ' bouteille(s)', // Plus d'eau (chaleur)
            ];
            
            $resultats['PAIN & BASES'] = [
                'Baguettes' => ceil($totalNourriture / 4) . ' pièces',
                'Pains burger/hot-dog' => ceil($totalNourriture * 0.8) . ' pièces', // Pas forcément 1/pers
            ];
            
            $resultats['APÉRITIF'] = [
                'Toasts' => ceil(2 * $totalNourriture) . ' pièces', // Léger apéro
                'Chips (100g)' => ceil($totalNourriture / 3) . ' paquet(s)',
            ];
            
            // Gestion des viandes sélectionnées
            $viandesSelectionnees = $calcul?->getViandesBarbecue() ?? [];

            if (!empty($viandesSelectionnees)) {
                $resultats['SPÉCIAL BARBECUE'] = [];
                
                foreach ($viandesSelectionnees as $viande) {
                    switch($viande) {
                        case 'merguez':
                            $resultats['SPÉCIAL BARBECUE']['Merguez'] = ceil(2 * $totalNourriture) . ' pièces'; // 2/pers
                            break;
                        case 'saucisses':
                            $resultats['SPÉCIAL BARBECUE']['Saucisses'] = ceil(2 * $totalNourriture) . ' pièces'; // 2/pers
                            break;
                        case 'brochettes':
                            $resultats['SPÉCIAL BARBECUE']['Brochettes'] = ceil(1.5 * $totalNourriture) . ' pièces'; // 1-2/pers
                            break;
                        case 'steaks':
                            $resultats['SPÉCIAL BARBECUE']['Steaks hachés'] = ceil($totalNourriture) . ' pièces (150g/adulte, 100g/enfant)';
                            break;
                        case 'cotes_porc':
                            $resultats['SPÉCIAL BARBECUE']['Côtes de porc'] = ceil(1.5 * $totalNourriture) . ' pièces'; // 1-2/pers
                            break;
                        case 'cotes_boeuf':
                            $resultats['SPÉCIAL BARBECUE']['Grosses côtes de bœuf'] = ceil($totalNourriture / 3) . ' pièces (300-400g, à partager)';
                            break;
                        case 'pilons_poulet':
                            $resultats['SPÉCIAL BARBECUE']['Pilons de poulet'] = ceil(2 * $totalNourriture) . ' pièces'; // 2/pers
                            break;
                        case 'cuisses_poulet':
                            $resultats['SPÉCIAL BARBECUE']['Cuisses de poulet'] = ceil(1 * $totalNourriture) . ' pièces'; // 1/pers
                            break;
                        case 'travers_porc':
                            $resultats['SPÉCIAL BARBECUE']['Travers de porc'] = ceil(250 * $totalNourriture) . 'g'; // 250g/pers
                            break;
                        case 'chipolatas':
                            $resultats['SPÉCIAL BARBECUE']['Chipolatas'] = ceil(3 * $totalNourriture) . ' pièces'; // 3/pers (petites)
                            break;
                    }
                }
                $resultats['SPÉCIAL BARBECUE']['Légumes grillés'] = ceil(120 * $totalNourriture) . 'g'; // 120g/pers
            } else {
                // Calcul par défaut si aucune viande sélectionnée
                $resultats['SPÉCIAL BARBECUE'] = [
                    'Merguez' => ceil(2 * $totalNourriture) . ' pièces',
                    'Saucisses' => ceil(2 * $totalNourriture) . ' pièces',
                    'Brochettes' => ceil(1.5 * $totalNourriture) . ' pièces',
                    'Steaks hachés' => ceil($totalNourriture) . ' pièces',
                    'Légumes grillés' => ceil(120 * $totalNourriture) . 'g',
                ];
            }

            $resultats['CONDIMENTS'] = [
                'Moutarde' => ceil($totalPersonnes / 15) . ' pot(s)',
                'Ketchup' => ceil($totalPersonnes / 10) . ' bouteille(s)',
                'Mayonnaise' => ceil($totalPersonnes / 12) . ' pot(s)',
                'Sauce barbecue' => ceil($totalPersonnes / 12) . ' bouteille(s)',
            ];
            
            $resultats['MATÉRIEL'] = [
                'Serviettes en papier' => ceil(3 * $totalPersonnes) . ' pièces',
                'Assiettes jetables' => ceil(2 * $totalPersonnes) . ' pièces',
                'Gobelets' => ceil(3 * $totalPersonnes) . ' pièces',
                'Couverts' => $totalPersonnes . ' sets complets',
            ];
            break;

        default:
            if (!$sansAlcool) {
                $resultats['BOISSONS']['Vin'] = ceil($nbPersonnes / 3) . ' bouteilles';
            }
            $resultats['BOISSONS']['Eau'] = ceil($totalBoissons) . ' bouteilles';
    }

    return $resultats;
}

}