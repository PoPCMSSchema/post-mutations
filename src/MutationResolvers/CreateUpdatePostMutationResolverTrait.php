<?php

declare(strict_types=1);

namespace PoPSchema\PostMutations\MutationResolvers;

trait CreateUpdatePostMutationResolverTrait
{
    protected function showCategories()
    {
        return !empty(\PoP_Application_Utils::getContentpostsectionCats());
    }
}
