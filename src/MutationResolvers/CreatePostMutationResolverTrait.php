<?php

declare(strict_types=1);

namespace PoPSchema\PostMutations\MutationResolvers;

trait CreatePostMutationResolverTrait
{
    public function execute(array &$errors, array &$errorcodes, array $form_data)
    {
        $post_id = $this->create($errors, $form_data);
        return $post_id;
    }
}
