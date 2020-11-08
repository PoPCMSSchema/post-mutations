<?php

declare(strict_types=1);

namespace PoPSchema\PostMutations\MutationResolvers;

trait UpdatePostMutationResolverTrait
{
    public function execute(array $form_data)
    {
        $post_id = $_REQUEST[POP_INPUTNAME_POSTID];
        $this->update($form_data);
        return $post_id;
    }
}
