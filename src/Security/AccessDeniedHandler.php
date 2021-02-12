<?php
namespace Uicms\Admin\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(UrlGeneratorInterface $urlGenerator, SessionInterface $session)
    {
        $this->urlGenerator = $urlGenerator;
        $this->session = $session;
    }
    
    public function handle(Request $request, AccessDeniedException $accessDeniedException)
    {
        return new RedirectResponse($this->urlGenerator->generate('admin_access_denied'));
    }
}