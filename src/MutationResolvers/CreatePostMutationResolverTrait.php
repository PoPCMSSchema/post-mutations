<?php

declare(strict_types=1);

namespace PoPSchema\PostMutations\MutationResolvers;

trait CreatePostMutationResolverTrait
{
    public function execute(array $form_data)
    {
        return $this->create($form_data);
    }
}
