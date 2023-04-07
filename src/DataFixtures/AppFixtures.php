<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use App\Entity\Meal;
use App\Entity\Ingredient;
use App\Entity\Tag;
use App\Entity\Category;
use APp\Entity\Language;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AppFixtures extends Fixture
{

    /**
     * @var Faker $faker
     */
    private $faker;

    /**
     * @var ObjectRepository $repository
     */
    private $repository;

    /**
     * @var ObjectManager $manager
     */
    private $manager;

    /**
     * @var array $languages
     */
    private $languages;

    /**
     * @var array $languagesArray
     */
    private $languagesArray;

    /**
     * @var int $categoriesAmount
     */
    private $categoriesAmount;

    /**
     * @var int $tagsAmount
     */
    private $tagsAmount;

    /**
     * @var int $ingredientsAmount
     */
    private $ingredientsAmount;

    /**
     * @var int $mealsAmount
     */
    private $mealsAmount;


    /**
     * Constructor.
     * 
     * @param EntityManagerInterface $em
     * @param ParameterBagInterface $params
     */
    public function __construct(EntityManagerInterface $em, ParameterBagInterface $params)
    {
        $this->faker = Factory::create('en_US');
        $this->repository = $em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
        $this->languagesArray = $params->get('languages_array');
        $this->categoriesAmount = $params->get('categories_amount');
        $this->tagsAmount = $params->get('tags_amount');
        $this->ingredientsAmount = $params->get('ingredients_amount');
        $this->mealsAmount = $params->get('meals_amount');
    }

    /**
     * @param ObjectManager $manager
     * 
     * Loads all the data for testing purposes into the database
     * 
     * The data is generated using Faker and the Translations are generated using Gedmo Translatable extension You can modify the number of entities generated and translation languages by changing the parameters.
     * 
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;
        $this->loadLanguages($this->languagesArray);
        $this->loadCategories($this->categoriesAmount);
        $this->loadTags($this->tagsAmount);
        $this->loadIngredients($this->ingredientsAmount);
        $this->loadMeals($this->mealsAmount);
    }

    /**
     * @param array $languages
     * 
     * Loads the languages into the database
     * 
     * @return void
     */
    private function loadLanguages(Array $languagesArray): void
    {
        foreach ($languagesArray as $locale => $name) {
            $language = new Language();
            $language->setLocale($locale);
            $language->setName($name);
            $this->manager->persist($language);
        }
        $this->manager->flush();
        $this->languages = $this->manager->getRepository(Language::class)->findAll();
    }


    /**
     * @param int $amount
     * 
     * Loads the categories into the database
     * 
     * @return void
     */
    private function loadCategories(Int $amount): void
    {
        for ($i = 0; $i < $amount; $i++) {
            $category = new Category();
            $category->setTitle($this->generateContent(255));
            $this->manager->persist($category);
            foreach ($this->languages as $language) {
                $this->translate($category, 'title', $language->getLocale());
            }
        }
        $this->manager->flush();

    }

    /**
     * @param int $amount
     * 
     * Loads the tags into the database
     * 
     * @return void
     */
    private function loadTags(Int $amount): void
    {
        for ($i = 0; $i < 10; $i++) {
            $tag = new Tag();
            $tag->setTitle($this->generateContent(255));
            $this->manager->persist($tag);
            foreach ($this->languages as $language) {
                $this->translate($tag, 'title', $language->getLocale());
            }
        }
        $this->manager->flush();

    }

    /**
     * @param int $amount
     * 
     * Loads the ingredients into the database
     * 
     * @return void
     */
    private function loadIngredients(Int $amount): void
    {
        for ($i = 0; $i < 25; $i++) {
            $ingredient = new Ingredient();
            $ingredient->setTitle($this->generateContent(255));
            $this->manager->persist($ingredient);
            foreach ($this->languages as $language) {
                $this->translate($ingredient, 'title', $language->getLocale());
            }
        }
        $this->manager->flush();

    }

    /**
     * @param int $amount
     * 
     * Loads the meals into the database
     * 
     * Has to be called after the categories, tags and ingredients are loaded as it loads the relations
     * 
     * @return void
     */
    private function loadMeals(Int $amount): void
    {
        for ($i = 0; $i < 50; $i++) {
            $meal = new Meal();
            $meal->setTitle($this->generateContent(255));
            $meal->setDescription($this->generateContent(255));
            $meal->setCategory($this->getRandomCategoryOrNull());
            $tags = $this->getRandomSelection($this->manager->getRepository(Tag::class)->findAll());
            //Due to task requiremnts it is assumed there is always at least one tag
            foreach ($tags as $tag) {
                $meal->addTag($tag);
            }
            $ingredients = $this->getRandomSelection($this->manager->getRepository(Ingredient::class)->findAll());

            //Due to task requiremnts it is assumed there is always at least one ingredient
            foreach ($ingredients as $ingredient) {
                $meal->addIngredient($ingredient);
            }
            $this->manager->persist($meal);

            foreach ($this->languages as $language) {
                $this->translate($meal, 'title', $language->getLocale());
                $this->translate($meal, 'description', $language->getLocale());
            }
        }
        $this->manager->flush();
    }

    /**
     * 
     * Randomly returns a random category or null
     * 
     * @return Category|null
     */
    private function getRandomCategoryOrNull(): ?Category
    {
        $categories = $this->manager->getRepository(Category::class)->findAll();
        $count = count($categories);
        if ($count == 0) {
            return null;
        }
        $index = random_int(0, $count * 2 - 1);
        return $index % 2 == 0 ? null : $categories[$index / 2];
    }

    /**
     * @param array $entities
     * 
     * Returns a random selection of entities from the array
     * 
     * @return array
     */
    private function getRandomSelection(array $entities): array
    {
        shuffle($entities);
        $randomCount = rand(1, count($entities));
        return array_slice($entities, 0, $randomCount);
    }

    /**
     * @param int $maxLength
     * 
     * Generates a random string of the given length using faker->catchPhrase, if it fails due to locale it uses faker->sentence instead to generate ranom latin text
     * 
     * @return string
     */
    private function generateContent($maxLength): string
    {
        try {
            $content = ucwords($this->faker->catchPhrase());
        } catch (\Exception $e) {
            $content = ucwords($this->faker->sentence());
        }
        return substr($content, 0, $maxLength);
    }

    /**
     * @param Object $entity
     * @param string $field
     * @param string $locale
     * 
     * Translates the given entity field into the given locale using this->generateContent fucntion
     * 
     * @return void
     */
    private function translate(Object $entity, string $field, string $locale): void
    {
        $this->faker = Factory::create($locale);
        $this->repository->translate($entity, $field, $locale, $this->generateContent(255));
    }
}
