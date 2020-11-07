<?php

declare(strict_types=1);

namespace PoPSchema\PostMutations\MutationResolvers;

use PoPSchema\Posts\Facades\PostTypeAPIFacade;
use PoPSchema\PostMutations\Facades\PostTypeAPIFacade as MutationPostTypeAPIFacade;
use PoPSchema\CustomPostMutations\MutationResolvers\AbstractCreateUpdateCustomPostMutationResolver;

abstract class AbstractCreateUpdatePostMutationResolver extends AbstractCreateUpdateCustomPostMutationResolver
{
    protected function getCustomPostType()
    {
        $postTypeAPI = PostTypeAPIFacade::getInstance();
        return $postTypeAPI->getPostCustomPostType();
    }

    /**
     * @param array<string, mixed> $data
     * @return mixed the ID of the updated post
     */
    protected function executeUpdateCustomPost(array $data)
    {
        $postTypeAPI = MutationPostTypeAPIFacade::getInstance();
        return $postTypeAPI->updatePost($data);
    }

    /**
     * @param array<string, mixed> $data
     * @return mixed the ID of the created post
     */
    protected function executeCreateCustomPost(array $data)
    {
        $postTypeAPI = MutationPostTypeAPIFacade::getInstance();
        return $postTypeAPI->createPost($data);
    }
}
