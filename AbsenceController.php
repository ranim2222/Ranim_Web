<?php

namespace App\Controller\GestionAbsence;

use App\Entity\Absence;
use App\Form\AbsenceType;
use App\Repository\AbsenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Snappy\Pdf;

#[Route('/absence')]
final class AbsenceController extends AbstractController
{
    #[Route(name: 'app_absence_index', methods: ['GET'])]
    public function index(AbsenceRepository $absenceRepository): Response
    {
        return $this->render('GestionAbsence/absence/index.html.twig', [
            'absences' => $absenceRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_absence_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $absence = new Absence();
        $form = $this->createForm(AbsenceType::class, $absence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->get('image_path')->getData();
            $type = $form->get('type')->getData(); // Récupérer le type d'absence
        
            if ($type === 'justifiee' && $file) { // Vérifie si le type est "Justifiée"
                // Générer un nom de fichier unique
                $filename = uniqid() . '.' . $file->guessExtension();

                try {
                    $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads';
                    $file->move($uploadsDirectory, $filename);
                    // Sauvegarder le chemin de l'image
                    $absence->setImagePath('uploads/' . $filename);
                    $this->addFlash('success', 'Image téléchargée avec succès.');
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
            } elseif ($type !== 'justifiee') {
                // Si le type n'est pas justifié, s'assurer qu'aucune image n'est assignée
                $absence->setImagePath(null);
            }
        
            // Persister l'entité Absence
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

    #[Route('/{ID_abs}', name: 'app_absence_show', methods: ['GET'])]
    public function show(Absence $absence): Response
    {
        return $this->render('GestionAbsence/absence/show.html.twig', [
            'absence' => $absence,
        ]);
    }

    #[Route('/{ID_abs}/edit', name: 'app_absence_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Absence $absence, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AbsenceType::class, $absence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si une nouvelle image est soumise, gérer l'upload et l'assignation
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
                $absence->setImagePath(null); // Si non justifié, on supprime l'image
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
    #[Route('/absence/pdf', name: 'app_absence_pdf', methods: ['GET'])]
public function generatePdf(AbsenceRepository $absenceRepository, Pdf $knpSnappy): Response
{
    // Récupérer toutes les absences depuis la base de données
    $absences = $absenceRepository->findAll();

    // Vérifier si chaque absence contient un ID valide
    foreach ($absences as $absence) {
        if (!$absence->getIDAbs()) {
            // Gérer les absences sans ID ou avec un ID invalide
            $this->addFlash('error', 'Une absence a un ID invalide.');
            return $this->redirectToRoute('app_absence_index');
        }
    }

    // Générer le contenu HTML pour le PDF depuis le fichier Twig
    $html = $this->renderView('GestionAbsence/absence/pdf.html.twig', [
        'absences' => $absences,
    ]);

    // Générer le PDF à partir du contenu HTML
    $pdfContent = $knpSnappy->getOutputFromHtml($html);

    // Retourner le PDF dans la réponse HTTP
    return new Response($pdfContent, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="absences.pdf"',
    ]);
}


   
}
