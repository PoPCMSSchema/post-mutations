<?php

declare(strict_types=1);

namespace PoPSchema\PostMutations\MutationResolvers;

trait UpdatePostMutationResolverTrait
{
    public function execute(array &$errors, array &$errorcodes)
    {
        $post_id = $_REQUEST[POP_INPUTNAME_POSTID];
        $this->update($errors);
        return $post_id;
    }
}
