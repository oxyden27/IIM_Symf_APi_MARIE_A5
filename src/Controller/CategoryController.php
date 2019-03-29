<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Category;
use App\Form\CategoryType;
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


class CategoryController extends FOSRestController
{
    /**
     * @FOSRest\Get("api/categories")
     *
     * @param ObjectManager $manager
     *
     * @return Response
     */
    public function getCategoriesAction(ObjectManager $manager)
    {
        $categoryRepository = $manager->getRepository(Category::class);
        $categories = $categoryRepository->findAll();

        $encoders = [new JsonEncoder()]; // If no need for XmlEncoder
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $jsonObject = $serializer->serialize($categories, 'json', [
            'circular_reference_handler' => function ($categories) {
                return $categories;
            }
        ]);

        return new Response($jsonObject, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * @FOSRest\Get("api/category/{id}")
     *
     * @param ObjectManager $manager
     * @param $id
     *
     * @return Response
     */
    public function getCategoryAction(ObjectManager $manager, $id)
    {
        $categoryRepository = $manager->getRepository(Category::class);
        $category = $categoryRepository->find($id);

        if (!$category instanceof Category) {
            return $this->json([
                'success' => false,
                'error' => 'Category not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $encoders = [new JsonEncoder()]; // If no need for XmlEncoder
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $jsonObject = $serializer->serialize($category, 'json', [
            'circular_reference_handler' => function ($category) {
                return $category->getId();
            }
        ]);

        return new Response($jsonObject, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * @FOSRest\Post("api/categories")
     *
     * @ParamConverter("category", converter="fos_rest.request_body")
     *
     * @param ObjectManager $manager
     * @param Category $category
     * @param ValidatorInterface $validator
     *
     * @return Response
     */
    public function postCategoryAction(ObjectManager $manager, Category $category, ValidatorInterface $validator)
    {
        $errors = $validator->validate($category);

        if (!count($errors)) {
            $category->setName("New category");
            $manager->persist($category);
            $manager->flush();

            return $this->json($category, Response::HTTP_CREATED);
        } else {
            return $this->json([
                'success' => false,
                'error' => $errors[0]->getMessage().' ('.$errors->getPropertyPath().')'
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @FOSRest\Delete("api/category/{id}")
     *
     * @param ObjectManager $manager
     * @param $id
     *
     * @return Response
     */
    public function deleteCategoryAction(ObjectManager $manager, $id)
    {
        $categoryRepository = $manager->getRepository(Category::class);
        $category = $categoryRepository->find($id);

        $articleRepository = $manager->getRepository(Article::class);
        $articles = $articleRepository->findBy(array("category" => $category->getId()));

        if ($category instanceof Category) {
            $manager->remove($category);
            foreach ($articles as $article) {
                $article->setCategory(null);
                $manager->persist($article);
            }
            $manager->flush();
            return $this->json([
                'success' => true,
            ], Response::HTTP_OK);
        } else {
            return $this->json([
                'success' => false,
                'error' => 'Category not found'
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * @FOSRest\Put("/api/category/{id}")
     *
     * @param Request $request
     * @param int $id
     * @param ObjectManager $manager
     * @param ValidatorInterface $validator
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateCategoryAction(Request $request, int $id, ObjectManager $manager, ValidatorInterface $validator)
    {
        $categoryRepository = $manager->getRepository(Category::class);
        $existingCategory   = $categoryRepository->find($id);

        if (!$existingCategory instanceof Category) {
            return $this->json([
                'success' => false,
                'error'   => 'Category not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm(CategoryType::class, $existingCategory);
        $form->submit($request->request->all());

        $errors = $validator->validate($existingCategory);

        if (!count($errors)) {
            $manager->persist($existingCategory);
            $manager->flush();

            return $this->json($existingCategory, Response::HTTP_CREATED);
        } else {
            return $this->json([
                'success' => false,
                'error'   => $errors[0]->getMessage() . ' (' . $errors[0]->getPropertyPath() . ')'
            ], Response::HTTP_BAD_REQUEST);
        }
    }

}
