<?php

declare(strict_types=1);

namespace PoPSchema\PostMutations\TypeAPIs;

/**
 * Methods to interact with the Type, to be implemented by the underlying CMS
 */
interface PostTypeAPIInterface
{
    /**
     * @param array<string, mixed> $data
     * @return mixed the ID of the created post
     */
    public function createPost(array $data);
}
