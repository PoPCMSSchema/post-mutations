<?php

declare(strict_types=1);

namespace PoPSchema\PostMutations\MutationResolvers;

class CreatePostMutationResolverBridge extends AbstractCreateUpdatePostMutationResolverBridge
{
    public function getMutationResolverClass(): string
    {
        return CreatePostMutationResolver::class;
    }
}
