<?php

namespace App\Validator\Route;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class MealsQueryParams
{
    #[Assert\Callback(callback: [MealsQueryParams::class, 'positiveInteger'], payload: 'per_page')]
    #[Assert\NotBlank(
        allowNull: true,
        message: 'The value of "per_page" query parameter cannot be blank.'
    )]
    public $per_page;

    #[Assert\Callback(callback: [MealsQueryParams::class, 'positiveInteger'], payload: 'page' )]
    #[Assert\NotBlank(
        allowNull: true,
        message: 'The value of "page" query parameter cannot be blank.'
    )]
    public $page;

    #[Assert\Regex(
        pattern: '/^(!NULL|NULL|\d+)$/',
        message: 'The value of "category" query parameter must be an positive integer, "NULL" or "!NULL".'
    )]
    #[Assert\NotBlank(
        allowNull: true,
        message: 'The value of "category" query parameter cannot be blank.'
    )]
    public $category;

    #[Assert\Regex(
        pattern: '/^(\d+)(,\d+)*$/',
        message: 'The value of "tags" query parameter must be a comma-separated list of integers or NULL.'
    )]
    #[Assert\NotBlank(
        allowNull: true,
        message: 'The value of "tags" query parameter cannot be blank.'
    )]
    public $tags;

    #[Assert\Callback(callback: [MealsQueryParams::class, 'withChoices'])]
    #[Assert\NotBlank(
        allowNull: true,
        message: 'The value of "with" query parameter cannot be blank.'
    )]
    public $with;

    #[Assert\Callback(callback: [MealsQueryParams::class, 'validateLang'])]
    #[Assert\Type(type: ['string', 'null'], message: 'The value of "lang" query parameter must be an string or NULL.')]
    public $lang;

    #[Assert\Callback(callback: [MealsQueryParams::class, 'positiveInteger'], payload: 'diff_time')]
    #[Assert\NotBlank(
        allowNull: true,
        message: 'The value of "diff_time" query parameter cannot be blank.'
    )]
    public $diff_time;

    /**
     * Validates the value of a "lang" query parameter.
     *
     * @param string $value The value of the "lang" query parameter to validate.
     * @param ExecutionContextInterface $context The validation context.
     * 
     * @return void
     * 
     */
    public static function validateLang($value, ExecutionContextInterface $context)
    {
        $length = strlen($value);
        if ($length !== 2 && $length !== 5) {
            $context->buildViolation('The value of "lang" query parameter should be 2 or 5 characters long.')
                ->addViolation();
        }
        if ($length === 2 && !preg_match('/^[a-z]{2}$/', $value)) {
            $context->buildViolation('The value of "lang" query parameter should be in format xx (e.g. en).')
                ->addViolation();
        }

        if ($length === 5 && !preg_match('/^[a-z]{2}_[A-Z]{2}$/', $value)) {
            $context->buildViolation('The value of "lang" query parameter should be in format xx_XX (e.g. en_US).')
                ->addViolation();
        }
    }

    /**
     * Validates that a given value is a positive integer.
     *
     * @param mixed $value The value to validate.
     * @param ExecutionContextInterface $context The validation context.
     * @param string $property The name of the query parameter being validated.
     * 
     * @return void
     * 
     */
    public static function positiveInteger($value, ExecutionContextInterface $context, string $property)
    {
        if ($value === null) {
            return;
        }
        if (is_int($value)) {
            return;
        }
        $message = sprintf('The value of "%s" query parameter must be a positive integer.', $property);
        if (!ctype_digit($value)) {
            $context->buildViolation($message)
                ->addViolation();
        }
        if ((int) $value <= 0) {
            $context->buildViolation($message)
                ->addViolation();
        }
    }

    /**
     * Validates that a given value is a comma-separated list of choices.
     *
     * @param mixed $value The value to validate.
     * @param ExecutionContextInterface $context The validation context.
     * 
     * @return void
     * 
     */
    public static function withChoices($value, ExecutionContextInterface $context)
    {
        if ($value === null) {
            return;
        }
        $choices = ['category', 'tags', 'ingredients'];
        $values = explode(',', $value);
        foreach ($values as $value) {
            if (!in_array($value, $choices)) {
                $context->buildViolation('The value of "with" query parameter must be a comma-separated list of "category", "tags" and/or "ingredients".')
                    ->addViolation();
            }
        }
    }
}
