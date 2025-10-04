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
        
        $resultats = $this->calculerQuantites($nbPersonnes, $nbEnfants, $typeEvenement);

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
    private function calculerQuantites(int $nbPersonnes, int $nbEnfants, string $typeEvenement): array
    {
         // ✅ Calcul avec pondération enfants (comme dans votre JS original)
        $guests = $nbPersonnes + ($nbEnfants * 0.5);
        $resultats = [];
        
        switch ($typeEvenement) {
            case 'apero':
                $resultats = [
                    "Bouteilles de vin (1 pour 3 personnes)" => ceil($nbPersonnes / 3),
                    "Bouteilles d'eau (1,5L/pers)" => ceil($nbPersonnes / 2),
                    "Toasts (5/pers)" => ceil($nbPersonnes * 5),
                    "Chips (30g/pers)" => ceil($nbPersonnes * 30) . " g",
                    "Tarama (20g/pers)" => ceil($nbPersonnes * 20) . " g",
                    "Foie gras (30g/pers)" => ceil($nbPersonnes * 30) . " g",
                    "Verres" => ceil($nbPersonnes * 2),
                    "Assiettes" => ceil($nbPersonnes * 1.5),
                    "Cacahuètes (30g/pers)" => ceil($nbPersonnes * 30) . " g",
                    "Pistaches (30g/pers)" => ceil($nbPersonnes * 30) . " g",
                    "Olives (30g/pers)" => ceil($nbPersonnes * 30) . " g",
                    "Saucissons (30g/pers)" => ceil($nbPersonnes * 30) . " g"
                ];
                break;

            case 'diner':
                $resultats = [
                    "Vin rouge (1 pour 2-3 pers)" => ceil($nbPersonnes / 2.5),
                    "Vin blanc (1 pour 3-4 pers)" => ceil($nbPersonnes / 3.5),
                    "Bouteilles d'eau (1,5L/ pour 2 personnes)" => ceil($nbPersonnes / 2),
                    "Entrée (150g/pers)" => ceil($nbPersonnes * 150) . " g",
                    "Plat principal (300g/pers)" => ceil($nbPersonnes * 300) . " g",
                    "Fromage (80g/pers)" => ceil($nbPersonnes * 80) . " g",
                    "Dessert (150g/pers)" => ceil($nbPersonnes * 150) . " g",
                    "Pain (50g/pers)" => ceil($nbPersonnes * 50) . " g",
                    "Verres" => ceil($nbPersonnes * 2.5),
                    "Assiettes" => ceil($nbPersonnes * 3)
                ];
                break;

            case 'birthday':
                $resultats = [
                    "Bouteilles de vin (1 pour 2 adultes)" => ceil($nbPersonnes / 2),
                    "Bouteilles de soda (1 pour 3 invités)" => ceil($nbPersonnes / 3),
                    "Bouteilles de jus de fruits (1 pour 3 invités)" => ceil($nbPersonnes / 3),
                    "Bouteilles d'eau (1,5l/2 pers)" => ceil($nbPersonnes / 2),
                    "Gâteau (150g/pers)" => ceil($nbPersonnes * 150) . " g",
                    "Bonbons/chocolats (50g/pers)" => ceil($nbPersonnes * 50) . " g",
                    "Bonbons pour enfants" => $nbEnfants > 0 ? ceil($nbEnfants * 50) . " g" : "0 g",
                    "Verres" => ceil($nbPersonnes * 2),
                    "Assiettes" => ceil($nbPersonnes * 1)
                ];
                break;

            case 'mariage':
                $resultats = [
                    "Bouteilles de vin rouge (1 pour 2 personnes)" => ceil($nbPersonnes / 2),
                    "Bouteilles de vin blanc (1 pour 3 personnes)" => ceil($nbPersonnes / 3),
                    "Bouteilles de vin rosé (1 pour 5 personnes)" => ceil($nbPersonnes / 5),
                    "Bouteilles de champagne (1 pour 7 personnes)" => ceil($nbPersonnes / 7),
                    "Bouteilles d'alcool fort (1 pour 20 personnes)" => ceil($nbPersonnes / 20),
                    "Bouteilles de soda 1,5l (1 pour 4 invités)" => ceil($nbPersonnes / 4),
                    "Bouteilles de jus de fruits 1l (1 pour 5 invités)" => ceil($nbPersonnes / 5),
                    "Bouteilles d'eau (1,5l/2 pers)" => ceil($nbPersonnes / 2),
                    "Café/thé (1 tasse/personne)" => ceil($nbPersonnes),
                    "Menu enfants spéciaux" => $nbEnfants > 0 ? $nbEnfants : 0,
                    "Petit pain (1/personne)" => ceil($nbPersonnes),
                    "Verres" => ceil($nbPersonnes * 2),
                    "Assiettes" => ceil($nbPersonnes * 1)
                ];
                break;
            
            case 'bapteme':
                $resultats = [
                    "Bouteille de vin rouge (1 pour 3 personnes)" => ceil($nbPersonnes / 3),
                    "Bouteilles de vin blanc (1 pour 4 personnes)" => ceil($nbPersonnes / 4),
                    "Bouteilles de vin rosé (1 pour 6 personnes)" => ceil($nbPersonnes / 6),
                    "Bouteilles de champagne (1 pour 8 personnes)" => ceil($nbPersonnes / 8),
                    "Bouteilles de soda 1,5l (1 pour 3 invités)" => ceil($nbPersonnes / 3),
                    "Bouteilles de jus de fruits 1l (1 pour 4 invités)" => ceil($nbPersonnes / 4),
                    "Bouteilles d'eau (1,5l/2 pers)" => ceil($nbPersonnes / 2),
                    "Café/thé (1 tasse/personne)" => ceil($nbPersonnes),
                    "Toast divers (8 à 10 toast / personne)" => ceil($nbPersonnes * 9),
                    "Petits fours sucrés (3-4/pers)" => ceil($nbPersonnes * 3.5),
                ];
                break;

            case 'barbecue':
                $resultats = [
                    "Bouteilles de soda 1,5l (1 pour 3 invités)" => ceil($nbPersonnes / 3),
                    "Bouteilles de jus de fruits 1l (1 pour 4 invités)" => ceil($nbPersonnes / 4),
                    "Bouteilles d'eau (1,5l/ pers)" => ceil($nbPersonnes),
                    "Merguez (2 merguez / personnes)" => ceil($nbPersonnes * 2),
                    "Pilons de poulet (1 / personne)" => ceil($nbPersonnes),
                    "Brochettes (1 brochettes / personnes)" => ceil($nbPersonnes),
                    "Baguettes (1 / 4 personnes)" => ceil($nbPersonnes / 2),

                ];
                break;

            default:
                $resultats = [
                    "Bouteilles de vin" => ceil($nbPersonnes / 3),
                    "Bouteilles d'eau" => ceil($guests),
                    "Verres" => ceil($guests),
                    "Assiettes" => ceil($guests)
                ];
        }

        return $resultats;
    }
}