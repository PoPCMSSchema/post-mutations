<?php

declare(strict_types=1);

namespace PoPSchema\PostMutations\MutationResolvers;

trait CreatePostMutationResolverTrait
{
    /**
     * @return mixed
     */
    public function execute(array $form_data)
    {
        return $this->create($form_data);
    }
}
