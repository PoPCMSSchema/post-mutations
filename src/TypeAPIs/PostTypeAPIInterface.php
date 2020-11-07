<?php

declare(strict_types=1);

namespace PoPSchema\PostMutations\TypeAPIs;

use PoPSchema\CustomPostMutations\TypeAPIs\CustomPostTypeAPIInterface;

/**
 * Methods to interact with the Type, to be implemented by the underlying CMS
 */
interface PostTypeAPIInterface extends CustomPostTypeAPIInterface
{
    /**
     * @param array<string, mixed> $data
     * @return mixed the ID of the created post
     */
    public function createPost(array $data);
    /**
     * @param array<string, mixed> $data
     * @return mixed the ID of the updated post
     */
    public function updatePost(array $data);
}
