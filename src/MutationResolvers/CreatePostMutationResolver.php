<?php

declare(strict_types=1);

namespace PoPSchema\PostMutations\MutationResolvers;

class CreatePostMutationResolver extends AbstractCreateUpdatePostMutationResolver
{
    use CreateUpdatePostMutationResolverTrait;

    public function execute(array &$errors)
    {
        $post_id = $this->create($errors);
        return $post_id;
    }
}
