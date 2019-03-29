<?php

namespace App\Controller;

use App\Entity\Author;
use App\Form\AuthorType;
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


class AuthorController extends FOSRestController
{
    /**
     * @FOSRest\Get("api/authors")
     *
     * @param ObjectManager $manager
     *
     * @return Response
     */
    public function getAuthorsAction(ObjectManager $manager)
    {
        $authorRepository = $manager->getRepository(Author::class);
        $authors = $authorRepository->findAll();

        $encoders = [new JsonEncoder()]; // If no need for XmlEncoder
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $jsonObject = $serializer->serialize($authors, 'json', [
            'circular_reference_handler' => function ($authors) {
                return $authors;
            }
        ]);

        return new Response($jsonObject, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * @FOSRest\Get("api/author/{id}")
     *
     * @param ObjectManager $manager
     * @param $id
     *
     * @return Response
     */
    public function getAuthorAction(ObjectManager $manager, $id)
    {
        $authorRepository = $manager->getRepository(Author::class);
        $author = $authorRepository->find($id);

        if (!$author instanceof Author) {
            return $this->json([
                'success' => false,
                'error' => 'Author not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $encoders = [new JsonEncoder()]; // If no need for XmlEncoder
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $jsonObject = $serializer->serialize($author, 'json', [
            'circular_reference_handler' => function ($author) {
                return $author->getId();
            }
        ]);

        return new Response($jsonObject, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * @FOSRest\Post("api/authors")
     *
     * @ParamConverter("author", converter="fos_rest.request_body")
     *
     * @param ObjectManager $manager
     * @param Author $author
     * @param ValidatorInterface $validator
     *
     * @return Response
     */
    public function postAuthorAction(ObjectManager $manager, Author $author, ValidatorInterface $validator)
    {
        $errors = $validator->validate($author);

        if (!count($errors)) {
            $author->setFirstname("New firstname");
            $author->setLastname("New lastname");
            $manager->persist($author);
            $manager->flush();

            return $this->json($author, Response::HTTP_CREATED);
        } else {
            return $this->json([
                'success' => false,
                'error' => $errors[0]->getMessage().' ('.$errors->getPropertyPath().')'
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @FOSRest\Delete("api/author/{id}")
     *
     * @param ObjectManager $manager
     * @param $id
     *
     * @return Response
     */
    public function deleteAuthorAction(ObjectManager $manager, $id)
    {
        $authorRepository = $manager->getRepository(Author::class);
        $author = $authorRepository->find($id);

        if ($author instanceof Author) {
            $manager->remove($author);
            $manager->flush();
            return $this->json([
                'success' => true,
            ], Response::HTTP_OK);
        } else {
            return $this->json([
                'success' => false,
                'error' => 'Author not found'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * @FOSRest\Put("/api/author/{id}")
     *
     * @param Request $request
     * @param int $id
     * @param ObjectManager $manager
     * @param ValidatorInterface $validator
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateAuthorAction(Request $request, int $id, ObjectManager $manager, ValidatorInterface $validator)
    {
        $authorRepository = $manager->getRepository(Author::class);
        $existingAuthor   = $authorRepository->find($id);

        if (!$existingAuthor instanceof Author) {
            return $this->json([
                'success' => false,
                'error'   => 'Author not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(AuthorType::class, $existingAuthor);
        $form->submit($request->request->all());

        $errors = $validator->validate($existingAuthor);

        if (!count($errors)) {
            $manager->persist($existingAuthor);
            $manager->flush();

            return $this->json($existingAuthor, Response::HTTP_CREATED);
        } else {
            return $this->json([
                'success' => false,
                'error'   => $errors[0]->getMessage() . ' (' . $errors[0]->getPropertyPath() . ')'
            ], Response::HTTP_BAD_REQUEST);
        }
    }

}
