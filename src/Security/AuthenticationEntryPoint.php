<?php
namespace Uicms\Admin\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class AuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    private $urlGenerator;
    private $session;

    public function __construct(UrlGeneratorInterface $urlGenerator, SessionInterface $session)
    {
        $this->urlGenerator = $urlGenerator;
        $this->session = $session;
    }

    public function start(Request $request, AuthenticationException $authException = null): RedirectResponse
    {
        $this->session->getFlashBag()->add('note', 'You have to login in order to access this page.');

        return new RedirectResponse($this->urlGenerator->generate('admin_login'));
    }
}