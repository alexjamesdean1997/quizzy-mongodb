<?php

namespace App\Controller;

use App\Form\EditUserType;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AccountController extends AbstractController
{
    /**
     * @Route("/user/edit", name="user_edit")
     */
    public function editProfile(Request $request, DocumentManager $dm)
    {
        $user = $this->getUser();
        $form = $this->createForm(EditUserType::class, $user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $dm->persist($user);
            $dm->flush();

            $this->addFlash('message', 'Profil mis à jour');
            return $this->redirectToRoute('dashboard');
        }

        return $this->render('security/edituser.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}