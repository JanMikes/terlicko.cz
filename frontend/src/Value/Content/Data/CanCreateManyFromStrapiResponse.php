<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

trait CanCreateManyFromStrapiResponse
{
    abstract public static function createFromStrapiResponse(array $data, int|null $id = null): self;


    /**
     * @return array<self>
     */
    public static function createManyFromStrapiResponse(array $data): array
    {
        $objects = [];

        foreach ($data as $singleObjectData) {
            $objects[] = self::createFromStrapiResponse($singleObjectData, $singleObjectData['id'] ?? null);
        }

        return $objects;
    }
}
