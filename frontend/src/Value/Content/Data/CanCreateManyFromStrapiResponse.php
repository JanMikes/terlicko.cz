<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

/** @template T of array */
trait CanCreateManyFromStrapiResponse
{
    /**
     * @param mixed<T> $data
     */
    abstract public static function createFromStrapiResponse(array $data): self;


    /**
     * @param array<T> $data
     * @return array<self>
     */
    public static function createManyFromStrapiResponse(array $data): array
    {
        $objects = [];

        foreach ($data as $singleObjectData) {
            $objects[] = self::createFromStrapiResponse($singleObjectData);
        }

        return $objects;
    }
}
