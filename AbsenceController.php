<?php namespace App\Controller\GestionAbsence;

use App\Entity\Absence;
use App\Form\AbsenceType;
use App\Repository\AbsenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/absence')]
final class AbsenceController extends AbstractController
{
    // Route pour afficher l'index des absences
    #[Route(name: 'app_absence_index', methods: ['GET'])]
    public function index(AbsenceRepository $absenceRepository): Response
    {
        $absences = $absenceRepository->findAll();

        // Appel API météo (OpenWeatherMap)
        $apiKey = '0fd5aaecc16ab5d3e79343dfc0434089'; // Remplace par ta clé API ici
        $city = 'Tunis'; // Change la ville si nécessaire
        $url = "https://api.openweathermap.org/data/2.5/weather?q=$city&appid=$apiKey&units=metric&lang=fr";

        $weatherData = null;
        try {
            $response = file_get_contents($url);
            $weatherData = json_decode($response, true);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la récupération de la météo.');
        }

        return $this->render('FrontOffice/chatbot/index.html.twig', [
            'absences' => $absences,
            'weather' => $weatherData,
        ]);
    }

    // Route pour afficher la météo actuelle sur une page séparée
    #[Route('/meteo', name: 'app_meteo', methods: ['GET'])]
    public function showMeteo(): Response
    {
        // Appel API météo (OpenWeatherMap)
        $apiKey = '0fd5aaecc16ab5d3e79343dfc0434089'; // Remplace par ta clé API ici
        $city = 'Tunisia'; // Change la ville si nécessaire
        $url = "https://api.openweathermap.org/data/2.5/weather?q=$city&appid=$apiKey&units=metric&lang=fr";

        $weatherData = null;
        try {
            $response = file_get_contents($url);
            $weatherData = json_decode($response, true);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la récupération de la météo.');
        }

        return $this->render('FrontOffice/chatbot/meteo.html.twig', [
            'weather' => $weatherData,
        ]);
    }

    // Route pour créer une nouvelle absence
    #[Route('/new', name: 'app_absence_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $absence = new Absence();
        $form = $this->createForm(AbsenceType::class, $absence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->get('image_path')->getData();
            $type = $form->get('type')->getData();

            if ($type === 'justifiee' && $file) {
                $filename = uniqid() . '.' . $file->guessExtension();
                try {
                    $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads';
                    $file->move($uploadsDirectory, $filename);
                    $absence->setImagePath('uploads/' . $filename);
                    $this->addFlash('success', 'Image téléchargée avec succès.');
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
            } elseif ($type !== 'justifiee') {
                $absence->setImagePath(null);
            }

            $entityManager->persist($absence);
            $entityManager->flush();

            $this->addFlash('success', 'L\'absence a été ajoutée avec succès.');

            return $this->redirectToRoute('app_absence_index');
        }

        return $this->render('GestionAbsence/absence/new.html.twig', [
            'absence' => $absence,
            'form' => $form->createView(),
        ]);
    }

    // Route pour afficher une absence
    #[Route('/{ID_abs}', name: 'app_absence_show', methods: ['GET'])]
    public function show(Absence $absence): Response
    {
        return $this->render('GestionAbsence/absence/show.html.twig', [
            'absence' => $absence,
        ]);
    }

    // Route pour éditer une absence
    #[Route('/{ID_abs}/edit', name: 'app_absence_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Absence $absence, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AbsenceType::class, $absence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('image_path')->getData();
            $type = $form->get('type')->getData();

            if ($type === 'justifiee' && $file) {
                $filename = uniqid() . '.' . $file->guessExtension();
                try {
                    $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads';
                    $file->move($uploadsDirectory, $filename);
                    $absence->setImagePath('uploads/' . $filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
            } elseif ($type !== 'justifiee') {
                $absence->setImagePath(null);
            }

            $entityManager->flush();

            $this->addFlash('success', 'L\'absence a été mise à jour avec succès.');

            return $this->redirectToRoute('app_absence_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('GestionAbsence/absence/edit.html.twig', [
            'absence' => $absence,
            'form' => $form->createView(),
        ]);
    }

    // Route pour supprimer une absence
    #[Route('/{ID_abs}', name: 'app_absence_delete', methods: ['POST'])]
    public function delete(Request $request, Absence $absence, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $absence->getIDAbs(), $request->request->get('_token'))) {
            $entityManager->remove($absence);
            $entityManager->flush();

            $this->addFlash('success', 'L\'absence a été supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Erreur lors de la suppression de l\'absence.');
        }

        return $this->redirectToRoute('app_absence_index', [], Response::HTTP_SEE_OTHER);
    }

    // Route pour générer un PDF des absences
    #[Route('/absence/pdf', name: 'app_absence_pdf', methods: ['GET'])]
    public function generatePdf(AbsenceRepository $absenceRepository, Pdf $knpSnappy): Response
    {
        $absences = $absenceRepository->findAll();

        foreach ($absences as $absence) {
            if (!$absence->getIDAbs()) {
                $this->addFlash('error', 'Une absence a un ID invalide.');
                return $this->redirectToRoute('app_absence_index');
            }
        }

        $html = $this->renderView('GestionAbsence/absence/pdf.html.twig', [
            'absences' => $absences,
        ]);

        $pdfContent = $knpSnappy->getOutputFromHtml($html);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="absences.pdf"',
        ]);
    }
}
