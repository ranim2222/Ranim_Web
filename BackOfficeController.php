<?php
// src/Controller/BackOfficeController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class BackOfficeController extends AbstractController
{
    #[Route('/backoffice', name: 'app_backoffice_dashboard')]
    public function dashboard()
    {
        // Vérifie si l'utilisateur est connecté
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('BackOffice/dashboard.html.twig');
    }
}