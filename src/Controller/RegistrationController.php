<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RegistrationController extends AbstractController
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function registrationAction(Request $request): JsonResponse
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->submit(json_decode($request->getContent(), true));
        try {
            if (!$user->getPassword()) {
                throw new RuntimeException();
            }
            $user->setPassword($this->passwordEncoder->encodePassword($user, $user->getPassword()));
            $user->setRoles(['ROLE_USER']);
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
            return new JsonResponse(null, Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            return new JsonResponse($e->getMessage(),Response::HTTP_BAD_REQUEST
            );
        }
    }
}