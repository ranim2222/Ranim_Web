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
        // Création du formulaire pour saisir le CIN
        $form = $this->createFormBuilder()
            ->add('cin', TextType::class, [
                'label' => 'Entrez votre CIN',
                'attr' => ['placeholder' => 'Entrez votre CIN'],
            ])
            ->getForm();

        $form->handleRequest($request);

        // Etape 1: Vérifier si le CIN existe
        if ($form->isSubmitted() && $form->isValid()) {
            $cin = $form->get('cin')->getData();

            // Vérifier si le CIN est valide
            if (!is_numeric($cin)) {
                return $this->render('chatbot/chatbot.html.twig', [
                    'form' => $form->createView(),
                    'message' => 'Le CIN doit être un nombre valide. Veuillez saisir un CIN valide.',
                ]);
            }

            // Recherche de l'absence et de la pénalité pour ce CIN
            $absenceRepository = $this->entityManager->getRepository(Absence::class);
            $penaliteRepository = $this->entityManager->getRepository(Penalite::class);

            $absences = $absenceRepository->findBy(['cin' => $cin]);
            $penalites = $penaliteRepository->findBy(['cin' => $cin]);

            if (empty($absences) && empty($penalites)) {
                return $this->render('chatbot/chatbot.html.twig', [
                    'form' => $form->createView(),
                    'message' => 'Aucune donnée trouvée pour ce CIN. Veuillez saisir un CIN valide.',
                ]);
            }

            // Si le CIN existe, proposer les choix
            return $this->redirectToRoute('chatbot_choices', ['cin' => $cin]);
        }

        return $this->render('chatbot/chatbot.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/chatbot/{cin}/choices', name: 'chatbot_choices')]
    public function chatbotChoices(int $cin, Request $request): Response
    {
        // Création du formulaire pour choisir une option
        $form = $this->createFormBuilder()
            ->add('choix', ChoiceType::class, [
                'choices' => [
                    'Le nombre d\'absences' => 1,
                    'Le type de pénalité' => 2,
                    'Le seuil de pénalité' => 3,
                    'Toutes les informations' => 4,
                    'Détection de fraudes' => 5,
                    'Quitter' => 6,
                ],
                'label' => 'Que voulez-vous savoir ?',
            ])
            ->getForm();

        $form->handleRequest($request);

        // Recherche des absences et pénalités liées à ce CIN
        $absenceRepository = $this->entityManager->getRepository(Absence::class);
        $penaliteRepository = $this->entityManager->getRepository(Penalite::class);

        $absences = $absenceRepository->findBy(['cin' => $cin]);
        $penalites = $penaliteRepository->findBy(['cin' => $cin]);

        $absenceData = [
            'nbr_abs' => count($absences),
            'penalite_type' => $penalites ? $penalites[0]->getType() : 'Aucune pénalité',
            'seuil' => $penalites ? $penalites[0]->getSeuil_abs() : 'Non défini',
        ];

        if ($form->isSubmitted() && $form->isValid()) {
            $choix = $form->get('choix')->getData();
            $message = '';

            // Traiter le choix
            switch ($choix) {
                case 1:
                    $message = 'Le nombre d\'absences est : ' . $absenceData['nbr_abs'];
                    break;
                case 2:
                    $message = 'Le type de pénalité est : ' . $absenceData['penalite_type'];
                    break;
                case 3:
                    $message = 'Le seuil de pénalité est : ' . $absenceData['seuil'];
                    break;
                case 4:
                    // Afficher toutes les informations
                    $message = 'Toutes les informations : <br>';
                    $message .= 'Nombre d\'absences : ' . $absenceData['nbr_abs'] . '<br>';
                    $message .= 'Type de pénalité : ' . $absenceData['penalite_type'] . '<br>';
                    $message .= 'Seuil de pénalité : ' . $absenceData['seuil'];
                    break;
                case 5:
                    // Implémenter la logique de détection de fraude
                    $fraudMessage = $this->detectFraud($absences);
                    $message = $fraudMessage;
                    break;
                case 6:
                    $message = 'À bientôt!';
                    break;
                default:
                    $message = 'Choix invalide. Veuillez entrer un numéro valide.';
                    break;
            }

            return $this->render('chatbot/chatbot.html.twig', [
                'message' => $message,
                'form' => $form->createView(),
                'cin' => $cin,
            ]);
        }

        // Affichage des choix si aucun choix n'a été fait
        return $this->render('chatbot/chatbot.html.twig', [
            'form' => $form->createView(),
            'cin' => $cin,
        ]);
    }

    // Méthode pour détecter les fraudes (lundi ou vendredi)
private function detectFraud(array $absences): string
{
    $fraudDetected = false;
    $fraudDates = [];

    foreach ($absences as $absence) {
        $dateAbsence = $absence->getDate();
        $dayOfWeek = $dateAbsence->format('l'); // 'l' retourne le jour de la semaine en anglais

        // Si l'absence tombe un vendredi ou un lundi, c'est une fraude
        if ($dayOfWeek === 'Friday' || $dayOfWeek === 'Monday') {
            $fraudDetected = true;
            $fraudDates[] = $dateAbsence->format('Y-m-d'); // Ajouter la date de l'absence
        }
    }

    if ($fraudDetected) {
        $fraudDateList = implode(', ', $fraudDates); // Liste des dates frauduleuses
        return "Une possibilité de fraude a été détectée pour la date du " . $fraudDateList . " .Veuillez vérifier cette information.";
    }

    return 'Aucune fraude détectée.';
}

}
