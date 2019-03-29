<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use FOS\RestBundle\Controller\Annotations as FOSRest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class CommentController extends FOSRestController
{
    /**
     * @FOSRest\Get("api/comments")
     *
     * @param ObjectManager $manager
     *
     * @return Response
     */
    public function getCommentsAction(ObjectManager $manager)
    {
        $commentRepository = $manager->getRepository(Comment::class);
        $comments = $commentRepository->findAll();

        $encoders = [new JsonEncoder()]; // If no need for XmlEncoder
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $jsonObject = $serializer->serialize($comments, 'json', [
            'circular_reference_handler' => function ($comments) {
                return $comments;
            }
        ]);

        return new Response($jsonObject, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * @FOSRest\Get("api/comment/{id}")
     *
     * @param ObjectManager $manager
     * @param $id
     *
     * @return Response
     */
    public function getCommentAction(ObjectManager $manager, $id)
    {
        $commentRepository = $manager->getRepository(Comment::class);
        $comment = $commentRepository->find($id);

        if (!$comment instanceof Comment) {
            return $this->json([
                'success' => false,
                'error' => 'Comment not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $encoders = [new JsonEncoder()]; // If no need for XmlEncoder
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $jsonObject = $serializer->serialize($comment, 'json', [
            'circular_reference_handler' => function ($comment) {
                return $comment->getId();
            }
        ]);

        return new Response($jsonObject, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * @FOSRest\Post("api/comments")
     *
     * @ParamConverter("comment", converter="fos_rest.request_body")
     *
     * @param ObjectManager $manager
     * @param Comment $comment
     * @param ValidatorInterface $validator
     *
     * @return Response
     */
    public function postCommentAction(ObjectManager $manager, Comment $comment, ValidatorInterface $validator)
    {
        $errors = $validator->validate($comment);

        if (!count($errors)) {
            $comment->getContent("New content");
            $manager->persist($comment);
            $manager->flush();

            return $this->json($comment, Response::HTTP_CREATED);
        } else {
            return $this->json([
                'success' => false,
                'error' => $errors[0]->getMessage().' ('.$errors->getPropertyPath().')'
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @FOSRest\Delete("api/comment/{id}")
     *
     * @param ObjectManager $manager
     * @param $id
     *
     * @return Response
     */
    public function deleteCommentAction(ObjectManager $manager, $id)
    {
        $commentRepository = $manager->getRepository(Comment::class);
        $comment = $commentRepository->find($id);

        if ($comment instanceof Comment) {
            $manager->remove($comment);
            $manager->flush();
            return $this->json([
                'success' => true,
            ], Response::HTTP_OK);
        } else {
            return $this->json([
                'success' => false,
                'error' => 'Comment not found'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * @FOSRest\Put("/api/comment/{id}")
     *
     * @param Request $request
     * @param int $id
     * @param ObjectManager $manager
     * @param ValidatorInterface $validator
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateCommentAction(Request $request, int $id, ObjectManager $manager, ValidatorInterface $validator)
    {
        $commentRepository = $manager->getRepository(Comment::class);
        $existingComment   = $commentRepository->find($id);

        if (!$existingComment instanceof Comment) {
            return $this->json([
                'success' => false,
                'error'   => 'Comment not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(CommentType::class, $existingComment);
        $form->submit($request->request->all());

        $errors = $validator->validate($existingComment);

        if (!count($errors)) {
            $manager->persist($existingComment);
            $manager->flush();

            return $this->json($existingComment, Response::HTTP_CREATED);
        } else {
            return $this->json([
                'success' => false,
                'error'   => $errors[0]->getMessage() . ' (' . $errors[0]->getPropertyPath() . ')'
            ], Response::HTTP_BAD_REQUEST);
        }
    }

}
