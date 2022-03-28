<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Form\UserForm;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


/**
 * Startup Controller
 *
 * Anything related with to the startups
 *
 * @Route("api/user", name="user")
 */
class UserController extends AbstractController
{

    /**
     * @IsGranted("ROLE_ADMIN")
     *
     * @Route("/create", name="_register", methods={"POST"})
     */
    public function create(Request $request, UserPasswordHasherInterface $encoder, UserRepository $userRepository,
                           ValidatorInterface $validator): JsonResponse
    {
        $user = $userRepository->findOneBy([
            'email' => $request->get('email'),
        ]);
        if ($user) {
            return new JsonResponse([
                'message' => 'User with this email already exists.',
            ]);
        }
        $form = $this->createForm(UserForm::class);

        $form->submit($request->request->all());

        if ($form->isValid()) {
            $user = new User();
            $user->setPassword($encoder->hashPassword($user, $request->get('password')));
            $user->setEmail($request->get('email'));
            $user->setLastname($request->get('lastName'));
            $user->setFirstname($request->get('firstName'));
            $user->setPhone('+' . $request->get('phone'));
            $userRepository->save($user);
            return new JsonResponse([
                'success' => true,
                'user' => $user->getEmail()
            ], 201);
        } else {
            $errors = [];
            foreach ($form->all() as $child) {
                if (!$child->isValid()) {
                    foreach ($child->getErrors() as $error)
                        $errors[] = ['field' => $child->getName(), 'message' => $error->getMessage()];
                }
            }
            return new JsonResponse([
                'success' => false,
                'errors' => $errors
            ], 422);
        }
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     *
     * @Route("/user/delete/{id}", name="_delete", methods={"POST"})
     */
    public function delete(Request $request, UserRepository $userRepository, $id,
                           EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $userRepository->find($id);
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'User is not found'
            ], 204);
        }
        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse(['success' => true], 200);
    }

    /**
     *
     * @Route("/user/update/{id}", name="_update", methods={"POST"})
     *
     * @return Response
     */
    public function update(Request $request, UserRepository $userRepository, $id, EntityManagerInterface $entityManager,
                           UserPasswordHasherInterface $encoder, Security $security): JsonResponse
    {
        $user = $this->getUser();
        $userUpdate = $userRepository->find($id);
        if (!$userUpdate) {
            return new JsonResponse([
                'success' => false,
                'message' => 'User is not found'
            ], 204);
        }
        if (!$security->isGranted('ROLE_ADMIN') || $user !== $userUpdate) {
            return new JsonResponse([
                'success' => false,
                'message' => 'You have no rights'
            ], 204);
        }


        $form = $this->createForm(UserForm::class);
        $form->submit($request->request->all());
        if ($form->isValid()) {
            $userUpdate->setPassword($encoder->hashPassword($userUpdate, $request->get('password')));
            $userUpdate->setEmail($request->get('email'));
            $userUpdate->setLastname($request->get('lastName'));
            $userUpdate->setFirstname($request->get('firstName'));
            $userUpdate->setPhone('+' . $request->get('phone'));
            $entityManager->remove($userUpdate);
            $entityManager->flush();
            return new JsonResponse([
                'success' => true,
                'user' => $userUpdate->getEmail() . 'updated.'
            ], 200);
        } else {
            $errors = [];
            foreach ($form->all() as $child) {
                if (!$child->isValid()) {
                    foreach ($child->getErrors() as $error)
                        $errors[] = ['field' => $child->getName(), 'message' => $error->getMessage()];
                }
            }
            return new JsonResponse([
                'success' => false,
                'errors' => $errors
            ], 400);
        }
    }
}
