<?php
namespace Uicms\Admin\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Request;

use Uicms\Admin\Form\Type\UIFormType;
use Uicms\App\Service\Model;
use Uicms\App\Service\Nav;
use Uicms\App\Service\Paginator;
use Uicms\App\Service\Params;
use Uicms\App\Service\Security;
use Uicms\App\Service\Viewnav;

class SecurityController extends AbstractController
{
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
		$ui_config = $this->getParameter('ui_config');
        $this->get('session')->set('ui_config', $ui_config);
        
        return $this->render('admin/tpl/security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }
    
    public function denied($page, Params $params_service, Model $model, Request $request, Nav $nav)
    {
        return $this->render('admin/tpl/security/denied.html.twig', [
                     'page'=>$page,
                     'nav'=>$nav,
                     'model'=>$model,
                ]);
    }
	
    public function logout(AuthenticationUtils $authenticationUtils): Response
    {
        $this->addFlash('success', 'Session closed');
        return $this->redirectToRoute('admin_login');
    }
}