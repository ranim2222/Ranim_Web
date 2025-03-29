<?php
namespace App\Controller;

use App\Entity\Absence;
use App\Entity\Penalite;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ChatbotController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/chatbot', name: 'chatbot')]
public function chatbot(Request $request): Response
{
    $form = $this->createFormBuilder()
        ->add('cin', TextType::class, [
            'label' => 'Entrez votre CIN',
            'attr' => [
                'placeholder' => 'Entrez votre CIN',
                'class' => 'form-control'
            ],
        ])
        ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $cin = $form->get('cin')->getData();

        // Vérification : Le CIN doit être un nombre
        if (!ctype_digit($cin)) {
            return $this->render('FrontOffice/chatbot/index.html.twig', [
                'form' => $form->createView(),
                'step' => 'cin',
                'message' => '❌ Le CIN doit être un nombre valide. Veuillez réessayer.',
            ]);
        }

        // Vérification si l'employé existe via les absences et pénalités
        $absenceRepository = $this->entityManager->getRepository(Absence::class);
        $penaliteRepository = $this->entityManager->getRepository(Penalite::class);

        $absences = $absenceRepository->findBy(['cin' => $cin]);
        $penalites = $penaliteRepository->findBy(['cin' => $cin]);

        if (empty($absences) && empty($penalites)) {
            return $this->render('FrontOffice/chatbot/index.html.twig', [
                'form' => $form->createView(),
                'step' => 'cin',
                'message' => '⚠ Aucun employé trouvé pour ce CIN. Veuillez entrer un CIN valide.',
            ]);
        }

        // CIN valide -> Redirection vers les choix
        return $this->redirectToRoute('chatbot_choices', ['cin' => $cin]);
    }

    return $this->render('FrontOffice/chatbot/index.html.twig', [
        'form' => $form->createView(),
        'step' => 'cin'
    ]);
}


    #[Route('/chatbot/{cin}/choices', name: 'chatbot_choices')]
    public function chatbotChoices(int $cin, Request $request): Response
    {
        // Définition des choix disponibles
        $choices = [
            'Le nombre d\'absences' => 1,
            'Le type de pénalité' => 2,
            'Le seuil de pénalité' => 3,
            'Toutes les informations' => 4,
            'Détection de fraudes' => 5,
            'Quitter' => 6,
        ];

        // Création du formulaire avec les choix (masqué dans le template)
        $form = $this->createFormBuilder()
            ->add('choix', ChoiceType::class, [
                'choices' => $choices,
                'label' => false,
                'expanded' => true,
                'multiple' => false,
                'attr' => ['class' => 'd-none'],
            ])
            ->getForm();

        $form->handleRequest($request);

        // Récupération des données
        $absenceRepository = $this->entityManager->getRepository(Absence::class);
        $penaliteRepository = $this->entityManager->getRepository(Penalite::class);

        $absences = $absenceRepository->findBy(['cin' => $cin]);
        $penalites = $penaliteRepository->findBy(['cin' => $cin]);

        // Préparation des données pour les réponses
        $absenceData = [
            'nbr_abs' => count($absences),
            'penalite_type' => $penalites ? $penalites[0]->getType() : 'Aucune pénalité',
            'seuil' => $penalites ? $penalites[0]->getSeuil_abs() : 'Non défini',
        ];

        // Traitement de la soumission du formulaire
        if ($form->isSubmitted() && $form->isValid()) {
            $choix = $form->get('choix')->getData();
            $message = $this->generateResponse($choix, $absenceData, $absences);

            return $this->render('FrontOffice/chatbot/index.html.twig', [
                'message' => $message,
                'form' => $form->createView(),
                'cin' => $cin,
                'step' => 'choices',
                'choices' => $choices
            ]);
        }

        return $this->render('FrontOffice/chatbot/index.html.twig', [
            'form' => $form->createView(),
            'cin' => $cin,
            'step' => 'choices',
            'choices' => $choices
        ]);
    }

    /**
     * Génère la réponse en fonction du choix sélectionné
     */
    private function generateResponse(int $choix, array $absenceData, array $absences): string
{
    switch ($choix) {
        case 1:
            return 'Le nombre d\'absences est : ' . $absenceData['nbr_abs'];
        case 2:
            return 'Le type de pénalité est : ' . $absenceData['penalite_type'];
        case 3:
            return 'Le seuil de pénalité est : ' . $absenceData['seuil'];
        case 4:
            return 'Toutes les informations : ' . 
                   '• Nombre d\'absences : ' . $absenceData['nbr_abs'] . ' ; ' . 
                   '• Type de pénalité : ' . $absenceData['penalite_type'] . ' ; ' . 
                   '• Seuil de pénalité : ' . $absenceData['seuil'];
        case 5:
            return $this->detectFraud($absences);
        case 6:
            return 'Merci d\'avoir utilisé notre service. À bientôt !';
        default:
            return 'Choix invalide. Veuillez sélectionner une option valide.';
    }
}

    /**
     * Détecte les fraudes potentielles
     */
    private function detectFraud(array $absences): string
    {
        $fraudDates = array_filter($absences, function($absence) {
            $day = $absence->getDate()->format('N');
            return in_array($day, [1, 5]); // Lundi=1, Vendredi=5
        });

        if (count($fraudDates) > 0) {
            $dates = array_map(fn($a) => $a->getDate()->format('d/m/Y'), $fraudDates);
            return "⚠ Alerte : Fraude potentielle détectée les jours suivants : " . implode(', ', $dates);
        }

        return 'Aucune fraude détectée dans les absences.';
    }
}
