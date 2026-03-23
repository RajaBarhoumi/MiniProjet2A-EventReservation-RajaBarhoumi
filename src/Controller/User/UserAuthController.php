<?php
namespace App\Controller\User;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UserAuthController extends AbstractController
{
    #[Route('/login', name: 'user_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        return $this->render('user/auth/login.html.twig', [
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'last_username' => $authenticationUtils->getLastUsername(),
        ]);
    }

    #[Route('/logout', name: 'user_logout')]
    public function logout(): void {}

    #[Route('/register', name: 'user_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $hasher,
        UserRepository $userRepo
    ): Response {
        if ($request->isMethod('POST')) {
            $user = new User();
            $user->setEmail($request->request->get('email'));
            $user->setUsername($request->request->get('username'));
            $user->setPassword(
                $hasher->hashPassword($user, $request->request->get('password'))
            );

            $userRepo->save($user);

            $this->addFlash('success', 'Account created! Please login.');
            return $this->redirectToRoute('user_login');
        }

        return $this->render('user/auth/register.html.twig');
    }
}
