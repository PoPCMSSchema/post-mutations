<?php

declare(strict_types=1);

namespace PoPSchema\PostMutations\MutationResolvers;

trait CreatePostMutationResolverTrait
{
    public function execute(array &$errors)
    {
        $post_id = $this->create($errors);
        return $post_id;
    }
}
