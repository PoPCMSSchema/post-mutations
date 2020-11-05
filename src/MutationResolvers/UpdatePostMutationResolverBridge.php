<?php

declare(strict_types=1);

namespace PoPSchema\PostMutations\MutationResolvers;

class UpdatePostMutationResolverBridge extends AbstractCreateUpdatePostMutationResolverBridge
{
    public function getMutationResolverClass(): string
    {
        return UpdatePostMutationResolver::class;
    }
}
