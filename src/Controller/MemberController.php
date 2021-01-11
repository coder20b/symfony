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
// POUR L'UPLOAD
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;


/**
 * @Route("/member")
 */
class MemberController extends AbstractController
{
    /**
     * @Route("/", name="member", methods={"GET","POST"})
     */
    public function index(Request $request, AnnonceRepository $annonceRepository, SluggerInterface $slugger): Response
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

            // UPLOAD DE PHOTO
            // https://symfony.com/doc/current/controller/upload_file.html
            $photoFile = $form->get('photo')->getData();
            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $photoFile->move(
                        $this->getParameter('photos_directory'),        // NE PAS OUBLIER DE CREER LE DOSSIER
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $annonce->setPhoto($newFilename);       // ON ENREGISTRE LE NOM DU FICHIER
            }

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


    /**
     * @Route("/{id}", name="annonce_delete_member", methods={"DELETE"})
     */
    public function delete(Request $request, Annonce $annonce): Response
    {

        if ($this->isCsrfTokenValid('delete'.$annonce->getId(), $request->request->get('_token'))) {

            // COMPLETER LES VERIFICATIONS
            // METHODE GETTER DU CONTROLLER POUR RECUPERER LE USER CONNECTE
            $userConnecte   = $this->getUser();                   
            $auteurAnnonce  = $annonce->getUser();

            // VERIFIER QUE L'ANNONCE APPARTIENT A L'UTILISATEUR CONNECTE
            if (($userConnecte != null) && ($auteurAnnonce != null) &&
                    ($userConnecte->getId() == $auteurAnnonce->getId()) )
            {
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->remove($annonce);
                $entityManager->flush();    
            }
        }

        // ON REVIENT SUR L'ESPACE MEMBRE
        return $this->redirectToRoute('member');
    }

    /**
     * @Route("/{id}/edit", name="annonce_edit_member", methods={"GET","POST"})
     */
    public function edit(Request $request, Annonce $annonce): Response
    {
        $form = $this->createForm(AnnonceMemberType::class, $annonce);
        $form->handleRequest($request);

        // COMPLETER LES VERIFICATIONS
        // METHODE GETTER DU CONTROLLER POUR RECUPERER LE USER CONNECTE
        $userConnecte   = $this->getUser();                   
        $auteurAnnonce  = $annonce->getUser();
        // VERIFIER QUE L'ANNONCE APPARTIENT A L'UTILISATEUR CONNECTE
        if (($userConnecte != null) && ($auteurAnnonce != null) &&
                ($userConnecte->getId() == $auteurAnnonce->getId()) )
        {
            if ($form->isSubmitted() && $form->isValid()) {
                // ENREGISTRER LES MODIFS DANS LA DATABASE
                $this->getDoctrine()->getManager()->flush();
    
                return $this->redirectToRoute('member');
            }
    
        }

        return $this->render('member/edit.html.twig', [
            'annonce'   => $annonce,
            'form'      => $form->createView(),
        ]);
    }

}
