<?php
namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

// NE PAS OUBLIER DE RAJOUTER LES use POUR LES CLASSES
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Contact;
use App\Form\ContactType;

// POUR UTILISER TWIG ON CREE UN HERITAGE DE CLASSE 
// AVEC LA CLASSE PARENTE AbstractController
class VitrineController extends AbstractController
{
    /**
     * @Route("/", name="accueil")
     */    
    function accueil ()
    {
        // DEV2 AJOUTE SON CODE...
        return $this->render("vitrine/index.html.twig");
    }

    /**
     * @Route("/ma-galerie", name="galerie")
     */    
    function galerie ()
    {
        return $this->render("vitrine/galerie.html.twig");
    }

    /**
     * @Route("/annonces", name="annonces")
     */    
    function annonces ()
    {
        return $this->render("vitrine/annonces.html.twig");
    }

    /**
     * @Route("/contact", name="contact", methods={"GET","POST"})
     */    
    function contact (Request $request): Response
    {
        // INJECTION DE DEPENDANCE
        // => SYMFONY NOUS FOURNIT L'OBJET $request
        // => $request BOITE QUI CONTIENT LES INFOS DE FORMULAIRE ($_GET, $_POST, $_REQUEST)

        // ON CREE UN OBJET POUR STOCKER LES INFOS DU FORMULAIRE
        $contact = new Contact();

        // ON CREE LE FORMULAIRE
        $form = $this->createForm(ContactType::class, $contact);
        // ON RECUPERE LES INFOS ENVOYEES PAR LE FORMULAIRE
        $form->handleRequest($request);

        // ON VALIDE LES INFOS DU FORMULAIRE
        $messageConfirmation = "merci de remplir le formulaire";
        if ($form->isSubmitted() && $form->isValid()) {
            // IL FAUT COMPLETER LES INFOS MANQUANTES
            $contact->setDateMessage(new \DateTime());

            // SI LES INFOS SONT VALIDES
            // => ALORS ON AJOUTE UNE LIGNE DANS LA TABLE SQL
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($contact);
            $entityManager->flush();

            $messageConfirmation = "message bien reçu. Nous vous répondrons rapidement.";
            
            // return $this->redirectToRoute('contact_index');
        }

        // LA METHODE render VIENT DE LA CLASSE PARENTE AbstractController
        // ON VA CHARGER LE CODE DU TEMPLATE 
        // templates/vitrine/contact/html.twig
        // (DANS VSCODE AJOUTER UNE EXTENSION POUR LES FICHIERS .twig)
        return $this->render("vitrine/contact.html.twig", [
            // CLE => VARIABLE TWIG
            // VALEUR => VALEUR DE LA VARIABLE TWIG
            'info1'     => "COUCOU",
            'messageConfirmation'   => $messageConfirmation,
            'contact'   => $contact,
            'form'      => $form->createView(),
        ]);
    }

}
