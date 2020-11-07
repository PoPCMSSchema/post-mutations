<?php

declare(strict_types=1);

namespace PoPSchema\PostMutations\MutationResolvers;

use PoPSchema\Posts\Facades\PostTypeAPIFacade;
use PoPSchema\CustomPostMutations\MutationResolvers\AbstractCreateUpdateCustomPostMutationResolver;

abstract class AbstractCreateUpdatePostMutationResolver extends AbstractCreateUpdateCustomPostMutationResolver
{
    protected function getCustomPostType()
    {
        $postTypeAPI = PostTypeAPIFacade::getInstance();
        return $postTypeAPI->getPostCustomPostType();
    }
}
