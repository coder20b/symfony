<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// NE PAS OUBLIER DE RAJOUTER LES use 
// POUR LES FORMULAIRES
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Annonce;
use App\Form\AnnonceMemberType;
// POUR LE READ
use App\Repository\AnnonceRepository;

/**
 * @Route("/member")
 */
class MemberController extends AbstractController
{
    /**
     * @Route("/", name="member", methods={"GET","POST"})
     */
    public function index(Request $request, AnnonceRepository $annonceRepository): Response
    {
        $annonce = new Annonce();

        $form = $this->createForm(AnnonceMemberType::class, $annonce);
        $form->handleRequest($request);

        // METHODE GETTER DU CONTROLLER POUR RECUPERER LE USER CONNECTE
        $userConnecte = $this->getUser();                   
        // DEBUG => AFFICHE LE CONTENU DES VARIABLES DANS LE PROFILER (BANDEAU EN BAS DE PAGE...)
        dump($userConnecte);

        if ($form->isSubmitted() && $form->isValid()) {

            // COMPLETER LES INFOS MANQUANTES
            $annonce->setDatePublication(new \DateTime());      // DATE D'ENREGISTREMENT DE L'ANNONCE
            $annonce->setUser($userConnecte);                   // ON ENREGISTRE L'ANNONCE AVEC COMME AUTEUR L'UTILISATEUR CONNECTE

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($annonce);
            $entityManager->flush();

            // DESACTIVER LA REDIRECTION POUR RESTER SUR LA MEME PAGE
            // return $this->redirectToRoute('annonce_index');
        }


        // FILTRE POUR CLAUSE WHERE POUR SEULEMENT AFFICHER LES ANNONCES DE L'UTILISATEUR CONNECTE
        $listeAnnonce = $annonceRepository->findBy(
            [ "user"  => $userConnecte],            
            [ "datePublication" => "DESC" ]
        );

        return $this->render('member/index.html.twig', [
            'annonce'   => $annonce,
            'form'      => $form->createView(),
            'annonces'  => $listeAnnonce,
        ]);
    }
}
