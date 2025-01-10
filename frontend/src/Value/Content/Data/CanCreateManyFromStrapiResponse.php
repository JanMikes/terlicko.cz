<?php

declare(strict_types=1);

namespace Terlicko\Web\Value\Content\Data;

trait CanCreateManyFromStrapiResponse
{
    abstract public static function createFromStrapiResponse(array $data): self;


    /**
     * @return array<self>
     */
    public static function createManyFromStrapiResponse(array $data): array
    {
        $objects = [];

        foreach ($data as $singleObjectData) {
            /** @var int|null $id */
            $id = $singleObjectData['id'] ?? null;

            $objects[] = self::createFromStrapiResponse($singleObjectData, $id);
        }

        return $objects;
    }
}
