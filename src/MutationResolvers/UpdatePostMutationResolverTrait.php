<?php

declare(strict_types=1);

namespace PoPSchema\PostMutations\MutationResolvers;

trait UpdatePostMutationResolverTrait
{
    public function execute(array $form_data)
    {
        return $this->update($form_data);
    }
}
