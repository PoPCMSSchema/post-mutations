<?php

declare(strict_types=1);

namespace PoPSchema\PostMutations\MutationResolvers;

use PoP\Hooks\Facades\HooksAPIFacade;
use PoPSchema\CustomPosts\Types\Status;
use PoP\Translation\Facades\TranslationAPIFacade;
use PoPSchema\CustomPosts\Facades\CustomPostTypeAPIFacade;
use PoP\ComponentModel\ModuleProcessors\DataloadingConstants;
use PoP\ComponentModel\Facades\Instances\InstanceManagerFacade;
use PoP\ComponentModel\QueryInputOutputHandlers\ResponseConstants;
use PoP\ComponentModel\MutationResolvers\MutationResolverInterface;
use PoP\ComponentModel\Facades\MutationResolution\MutationResolutionManagerFacade;
use PoP\ComponentModel\MutationResolvers\ComponentMutationResolverBridgeInterface;

abstract class AbstractCreateUpdatePostMutationResolverBridge implements ComponentMutationResolverBridgeInterface
{
    public function getSuccessString($post_id, $status)
    {
        $customPostTypeAPI = CustomPostTypeAPIFacade::getInstance();
        if ($status == Status::PUBLISHED) {
            $success_string = sprintf(
                TranslationAPIFacade::getInstance()->__('<a href="%s" %s>Click here to view it</a>.', 'pop-application'),
                $customPostTypeAPI->getPermalink($post_id),
                getReloadurlLinkattrs()
            );
        } elseif ($status == Status::DRAFT) {
            $success_string = TranslationAPIFacade::getInstance()->__('The status is still “Draft”, so it won\'t be online.', 'pop-application');
        } elseif ($status == Status::PENDING) {
            $success_string = TranslationAPIFacade::getInstance()->__('Now waiting for approval from the admins.', 'pop-application');
        }

        return HooksAPIFacade::getInstance()->applyFilters('gd-createupdate-post:execute:successstring', $success_string, $post_id, $status);
    }

    /**
     * @param array $data_properties
     * @return array<string, mixed>|null
     */
    public function execute(array &$data_properties): ?array
    {
        // If the post has been submitted, execute the Gravity Forms shortcode
        if ('POST' == $_SERVER['REQUEST_METHOD']) {
            $mutationResolverClass = $this->getMutationResolverClass();
            $instanceManager = InstanceManagerFacade::getInstance();
            /** @var MutationResolverInterface */
            $mutationResolver = $instanceManager->getInstance($mutationResolverClass);
            $errors = array();
            $post_id = $mutationResolver->execute($errors);

            if ($errors) {
                // Bring no results
                $data_properties[DataloadingConstants::SKIPDATALOAD] = true;
                return array(
                    ResponseConstants::ERRORSTRINGS => $errors
                );
            }

            $this->modifyDataProperties($data_properties, $post_id);

            // Success String: check if the post status is 'publish' or 'pending', and so print the corresponding URL or Preview URL
            $customPostTypeAPI = CustomPostTypeAPIFacade::getInstance();
            $status = $customPostTypeAPI->getStatus($post_id);
            $success_string = $this->getSuccessString($post_id, $status);

            // Save the result for some module to incorporate it into the query args
            $gd_dataload_actionexecution_manager = MutationResolutionManagerFacade::getInstance();
            $gd_dataload_actionexecution_manager->setResult(get_called_class(), $post_id);

            // No errors => success
            return array(
                ResponseConstants::SUCCESS => true,
                ResponseConstants::SUCCESSSTRINGS => array($success_string)
            );
        }

        return null;
    }

    public function modifyDataProperties(&$data_properties, $post_id)
    {
        // Modify the block-data-settings, saying to select the id of the newly created post
        $data_properties[DataloadingConstants::QUERYARGS]['include'] = array($post_id);
        $data_properties[DataloadingConstants::QUERYARGS]['custom-post-status'] = [
            Status::PUBLISHED,
            Status::PENDING,
            Status::DRAFT,
        ];
    }

    abstract public function getMutationResolverClass(): string;
}

