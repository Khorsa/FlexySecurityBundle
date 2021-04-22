<?php

namespace flexycms\FlexySecurityBundle\Controller;

use flexycms\FlexyAdminFrameBundle\Controller\AdminBaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AdminBaseController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $forRender = parent::renderDefault();

        $forRender['title'] = 'Вход в систему';
        $forRender['last_username'] = $lastUsername;
        $forRender['error'] = $error;

        return $this->render('@FlexySecurity/login.html.twig', $forRender);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
