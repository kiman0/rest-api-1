<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TodoType;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class TaskController extends AbstractController
{
    private $taskRepository;
    private $serializer;

    public function __construct(TaskRepository $taskRepository, SerializerInterface $serializer)
    {
        $this->taskRepository = $taskRepository;
        $this->serializer     = $serializer;
    }

    /**
     * @Route("/api/task", methods={"GET"})
     */
    public function getAllAction(): JsonResponse
    {
        return new JsonResponse(json_decode($this->serializer->serialize($this->taskRepository->findBy(['user' => $this->getUser()]), 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['user']]), true), 200);
    }

    /**
     * @Route("/api/task/{id}", requirements={"id"="\d+"}, methods={"GET"})
     */
    public function getAction(Task $task): JsonResponse
    {
        if ($task->getUser() !== $this->getUser()) {
            return new JsonResponse(null,Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse(json_decode($this->serializer->serialize($task, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['user']]), true), 200);
    }

    /**
     * @Route("/api/task", methods={"POST"})
     */
    public function createAction(Request $request): JsonResponse
    {
        $task = new Task();

        $text = json_decode($request->getContent(), true)['text'] ?? null;

        if ($text === null) {
            return new JsonResponse(null,228);
        }

        $task->setText($text);

        try {
            $task->setUser($this->getUser());

            $em = $this->getDoctrine()->getManager();
            $em->persist($task);
            $em->flush();

            return new JsonResponse(json_decode($this->serializer->serialize($task, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['user']]), true), 201);
        } catch (\Throwable $e) {
            return new JsonResponse(null, Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/api/task/{id}", requirements={"id"="\d+"}, methods={"PUT"})
     */
    public function editAction(Task $task, Request $request): JsonResponse
    {
        if ($task->getUser() !== $this->getUser()) {
            return new JsonResponse(null, Response::HTTP_FORBIDDEN);
        }

        $text = json_decode($request->getContent(), true)['text'] ?? null;

        if ($text === null) {
            return new JsonResponse(null,Response::HTTP_BAD_REQUEST);
        }

        try {
            $task->setText($text);
            $em = $this->getDoctrine()->getManager();
            $em->persist($task);
            $em->flush();
            return new JsonResponse(json_decode($this->serializer->serialize($task, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['user']]), true), 200);
        } catch (\Throwable $e) {
            return new JsonResponse(null,Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @Route("/api/task/{id}", requirements={"id"="\d+"}, methods={"DELETE"})
     */
    public function deleteAction(Task $task): JsonResponse
    {
        if ($task->getUser() !== $this->getUser()) {
            return new JsonResponse(null, Response::HTTP_FORBIDDEN);
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($task);
        $em->flush();
        return new JsonResponse(json_decode($this->serializer->serialize($task, 'json', [AbstractNormalizer::IGNORED_ATTRIBUTES => ['user']]), true), 204);
    }
}
