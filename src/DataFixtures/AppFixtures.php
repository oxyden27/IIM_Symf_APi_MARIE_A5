<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Author;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->encoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        $categories = [];
        $articles = [];

        // Category fixtures
        for ($i = 0; $i < 10; $i++) {
            $category = new Category();
            $category->setName('Category' . $i );
            $manager->persist($category);
            $categories[] = $category;

        }

        // Articles fixture and Authors fixture
        for ($i = 0; $i < 10; $i++) {
            $author = new Author();
            $author->setFirstname('Firstname' . $i );
            $author->setLastname('Lastname' . $i );
            $manager->persist($author);
            $article = new Article();
            $article->setName('Article' . $i );
            $article->setDescription('Les Padawan de l\'espaces remportent toujours la victoire.');
            $article->setCategory($categories[rand(0, 9)]);
            $article->setAuthor($author);
            $manager->persist($article);
            $articles[] = $article;

        }

        // Comments fixture
        for ($i = 0; $i < 10; $i++) {
            $comment = new Comment();
            $comment->setContent('ObiWan est le plus sage des Jedi !');
            $comment->setArticle($articles[rand(0, 9)]);
            $manager->persist($comment);
            $comments[] = $comment;

        }

        $user = new User();
        $user->setEmail('lucmarie.lm@gmail.com');
        $user->setRoles([
            'ROLE_ADMIN',
            'ROLE_API',
        ]);
        $user->setPassword($this->encoder->encodePassword($user, 'Anakin'));
        $manager->persist($user);

        $manager->flush();
    }
}
