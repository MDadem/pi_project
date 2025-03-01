<?php
namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use App\Enum\Category;
use Symfony\Component\Form\Exception\TransformationFailedException;

class CategoryToStringTransformer implements DataTransformerInterface
{
    public function transform($categories): array
    {
        if (!$categories) {
            return [];
        }

        if (!is_array($categories)) {
            throw new TransformationFailedException('Expected an array.');
        }

        return array_map(fn(Category $category) => $category->value, $categories);
    }

    public function reverseTransform($categoryValues): array
    {
        if (!$categoryValues) {
            return [];
        }

        if (!is_array($categoryValues)) {
            throw new TransformationFailedException('Expected an array.');
        }

        return array_map(fn(string $value) => Category::tryFrom($value) ?? throw new TransformationFailedException("Invalid category: $value"), $categoryValues);
    }
}
