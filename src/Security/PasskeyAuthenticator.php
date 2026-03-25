<?php
namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class PasskeyAuthenticator extends AbstractAuthenticator
{
    public function __construct(private RouterInterface $router) {}

    public function supports(Request $request): ?bool
    {
        return $request->getPathInfo() === '/passkey-login'
            && $request->query->get('email');
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->query->get('email');
        return new SelfValidatingPassport(new UserBadge($email));
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        return new RedirectResponse($this->router->generate('home'));
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response {
        return new RedirectResponse($this->router->generate('user_login'));
    }
}