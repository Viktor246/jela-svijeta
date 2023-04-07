<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\LanguageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\MealRepository;
use App\Validator\Route\MealsQueryParams;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MealsController extends AbstractController
{
    /**
     * @var MealRepository $mealRepository
     */
    private $mealRepository;

    /**
     * @var LanguageRepository $languageRepository
     */
    private $languageRepository;

    /**
     * @var ValidatorInterface $validator
     */
    private $validator;

    /**
     * Constructor.
     * 
     * @param MealRepository $mealRepository
     * @param LanguageRepository $languageRepository
     * @param ValidatorInterface $validator
     */
    public function __construct(MealRepository $mealRepository, LanguageRepository $languageRepository, ValidatorInterface $validator)
    {
        $this->mealRepository = $mealRepository;
        $this->languageRepository = $languageRepository;
        $this->validator = $validator;
    }

    #[Route('/meals', name: 'app_meals', methods: ['GET', 'HEAD'])]
    public function index(Request $request): JsonResponse
    {
        // Get query parameters from the request for validation
        $query = new MealsQueryParams();
        $query->per_page = $request->query->get('per_page', 5);        // default value is 5
        $query->page = $request->query->get('page', 1);                // default value is 1
        $query->category = $request->query->get('category', null);     // default value is null
        $query->tags = $request->query->get('tags', null);             // default value is null
        $query->with = $request->query->get('with', null);             // default value is null
        $query->lang = $request->query->get('lang', 'en_US');          // default value is en_US
        $query->diff_time = $request->query->get('diff_time', null);   // default value is null

        // Validate the query parameters
        $violations = $this->validator->validate($query);

        if (count($violations) > 0) {
            return $this->returnViolations($violations);
        }

        // Get query parameters from the request
        $perPage = $request->query->get('per_page', 5);         // default value is 5
        $page = $request->query->get('page', 1);                // default value is 1
        $category = $request->query->get('category', null);     // default value is null
        $tags = $request->query->get('tags', null);             // default value is null
        $with = $request->query->get('with', null);             // default value is null
        $lang = $request->query->get('lang', 'en_US');          // default value is en_US
        $diffTime = $request->query->get('diff_time', null);    // default value is null

        // Validate the language
        $language = $this->languageRepository->findOneBy(['locale' => $lang]);
        if (!$language) {
            if (strlen($lang) === 2) {
                $language = $this->languageRepository->createQueryBuilder('l')
                    ->where('l.locale LIKE :locale')
                    ->setParameter('locale', $lang . '_%')
                    ->getQuery()
                    ->getOneOrNullResult();
            }
        }
        if (!$language) {
            $violations->add(
                new ConstraintViolation(
                    'Unsupported language.',
                    'Unsupported language.',
                    [],
                    $lang,
                    'lang',
                    $lang
                )
            );
        }

        // Handle validation errors
        if (count($violations) > 0) {
            return $this->returnViolations($violations);
        }

        // Validate the category
        if ($category !== null && !$this->mealRepository->findByCategory($category)) {
            $violations->add(
                new ConstraintViolation(
                    'Category not found.',
                    'Category not found.',
                    [],
                    $category,
                    'category',
                    $category
                )
            );
        }

        // Validate the tags
        if ($tags !== null) {
            $tagsArray = explode(',', $tags);
            foreach ($tagsArray as $tag) {
                if (!$this->mealRepository->findByTag($tag)) {
                    $violations->add(
                        new ConstraintViolation(
                            'Tag not found.',
                            'Tag not found.',
                            [],
                            $tag,
                            'tags',
                            $tags
                        )
                    );
                }
            }
        }
        // Handle validation errors
        if (count($violations) > 0) {
            return $this->returnViolations($violations);
        }

        // Build the query
        $queryBuilder = $this->mealRepository->createQueryBuilder('e');
        // Add category filter
        if ($category !== null){
            if ($category === 'NULL') {
                $queryBuilder->andWhere('e.category IS NULL');
            } else if ($category === '!NULL') {
                $queryBuilder->andWhere('e.category IS NOT NULL');
            } else {
                $queryBuilder->andWhere('e.category = :category')
                    ->setParameter('category', $category);
            }
        }
        // Add tags filter
        if ($tags !== null) {
            $tagsArray = explode(',', $tags);
            foreach ($tagsArray as $index => $tag) {
                $queryBuilder->innerJoin('e.tags', 't' . $index)
                    ->andWhere('t' . $index . '.id = :tag' . $index)
                    ->setParameter('tag' . $index, $tag);
            }
        }
        // add diffTime filter
        $dateTime = DateTimeImmutable::createFromFormat('U', $diffTime);
        if ($diffTime > 0) {
            $queryBuilder->andWhere('e.createdAt > :dateTime OR e.updatedAt > :dateTime OR e.deletedAt > :dateTime')
                ->setParameter('dateTime', $dateTime);
        }

        // Count the total items using a query without offset and limit
        $countQueryBuilder = clone $queryBuilder;
        $countQueryBuilder->select('COUNT(e.id)');
        $countQuery = $countQueryBuilder->getQuery();
        $totalItems = $countQuery->getSingleScalarResult();

        // Calculate the total pages using the total items and items per page
        $totalPages = ceil($totalItems / $perPage);

        // Validate the page number
        if ($page > $totalPages) {
            $message = "Page {$page} doesn't exist. There is {$totalPages} pages.";
            $violations->add(
                new ConstraintViolation(
                    $message,
                    $message,
                    [],
                    $page,
                    'page',
                    $page
                )
            );
        }

        // Handle validation errors
        if (count($violations) > 0) {
            return $this->returnViolations($violations);
        }

        // Calculate previous and next pages
        $prevPage = $page > 1 ? $page - 1 : null;
        $nextPage = $page < $totalPages ? $page + 1 : null;
        
        // Add offset and limit to the query
        $offset = ($page - 1) * $perPage;
        $queryBuilder->setFirstResult($offset)
            ->setMaxResults($perPage);
        $query = $queryBuilder->getQuery();

        // Add translation hints to the query
        $query->setHint(
            \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
            'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
        );
        $query->setHint(
            \Gedmo\Translatable\TranslatableListener::HINT_TRANSLATABLE_LOCALE,
            $language->getLocale()
        );

        // Get the meals
        $meals = $query->getResult();

        // Extract meal IDs from $meals
        $mealIds = array_map(function ($meal) {
            return $meal->getId();
        }, $meals);

        // Build a query to get the meals with their relationships
        $mealsWithRelationshipsQuery = $this->mealRepository->createQueryBuilder('e')
        ->select('e', 'c', 't', 'i')
        ->leftJoin('e.category', 'c')
        ->leftJoin('e.tags', 't')
        ->leftJoin('e.ingredients', 'i')
        ->where('e.id IN (:ids)')
        ->setParameter('ids', $mealIds)
        ->getQuery();

        // Add translation hints to the query
        $mealsWithRelationshipsQuery->setHint(
            \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
            'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
        );
        $mealsWithRelationshipsQuery->setHint(
            \Gedmo\Translatable\TranslatableListener::HINT_TRANSLATABLE_LOCALE,
            $language->getLocale()
        );

        // Get the meals with their relationships
        $mealsWithRelationships = $mealsWithRelationshipsQuery->getResult();

        // Convert the meals to arrays
        $mealArray = [];
        foreach ($mealsWithRelationships as $meal) {
            $mealArray[] = $meal->toArray(($diffTime > 0)? $dateTime : null, $with);
        }
        // Build the response
        $return = [
            'meta' => [
                'currentPage' => $page,
                'totalItems' => $totalItems,
                'itemsPerPage' => $perPage,
                'totalPages' => $totalPages,
            ],
            'data' => $mealArray,
            'links' => [
                'prev' => $prevPage ? $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo() . '?' . http_build_query(array_merge($request->query->all(), ['page' => $prevPage])) : null,
                'next' => $nextPage ? $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo() . '?' . http_build_query(array_merge($request->query->all(), ['page' => $nextPage])) : null,        
                'self' => $request->getUri(),
            ]
        ];
        return new JsonResponse($return);
    }

    private function returnViolations(ConstraintViolationList $violations) : JsonResponse
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }
        return new JsonResponse(['errors' => $errors], 400);
    }
}