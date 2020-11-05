<?php

declare(strict_types=1);

namespace PoPSchema\PostMutations\MutationResolvers;

class UpdatePostMutationResolver extends AbstractCreateUpdatePostMutationResolver
{
    use CreateUpdatePostMutationResolverTrait, UpdatePostMutationResolverTrait;
}
