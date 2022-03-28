<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Firebase\JWT\JWT;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{

    /**
     * @Route("/api/login", name="login", methods={"POST"})
     * @param Request $request
     * @param UserRepository $userRepository
     * @param UserPasswordHasherInterface $encoder
     * @return Response
     */
    public function login(Request $request, UserRepository $userRepository, UserPasswordHasherInterface $encoder)
    {
        $user = $userRepository->findOneBy([
            'email'=>$request->get('email'),
        ]);
        if (!$user || !$encoder->hashPassword($user, $request->get('password'))) {
            return $this->json([
                'message' => 'email or password is wrong.',
            ]);
        }
        $payload = [
            "user" => $user->getEmail(),
            "exp"  => (new \DateTime())->modify("+5 minutes")->getTimestamp(),
        ];


        $jwt = JWT::encode($payload, $this->getParameter('jwt_secret'), 'HS256');
        return new JsonResponse([
            'message' => 'success!',
            'token' => sprintf('Bearer %s', $jwt),
        ]);
    }
}
