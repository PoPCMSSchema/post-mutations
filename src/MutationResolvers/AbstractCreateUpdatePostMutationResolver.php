<?php

declare(strict_types=1);

namespace PoPSchema\PostMutations\MutationResolvers;

define('POP_POSTSCREATION_CONSTANT_VALIDATECATEGORIESTYPE_ATLEASTONE', 1);
define('POP_POSTSCREATION_CONSTANT_VALIDATECATEGORIESTYPE_EXACTLYONE', 2);

use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\Hooks\Facades\HooksAPIFacade;
use PoP\ComponentModel\Facades\ModuleProcessors\ModuleProcessorManagerFacade;
use PoP\LooseContracts\Facades\NameResolverFacade;
use PoPSchema\CustomPosts\Facades\CustomPostTypeAPIFacade;
use PoPSchema\CustomPosts\Types\Status;

abstract class AbstractCreateUpdatePostMutationResolver
{
    protected function addReferences()
    {
        return true;
    }

    protected function volunteer()
    {
        return false;
    }

    protected function showCategories()
    {
        return false;
    }

    protected function getCategoryTaxonomy()
    {
        return 'category';
    }

    protected function getCategories()
    {
        if ($this->showCategories()) {
            if ($categories_module = $this->getCategoriesModule()) {
                $moduleprocessor_manager = ModuleProcessorManagerFacade::getInstance();

                // We might decide to allow the user to input many sections, or only one section, so this value might be an array or just the value
                // So treat it always as an array
                $categories = $moduleprocessor_manager->getProcessor($categories_module)->getValue($categories_module);
                if ($categories && !is_array($categories)) {
                    $categories = array($categories);
                }

                return $categories;
            }
        }

        return array();
    }

    protected function addParentCategories()
    {
        return HooksAPIFacade::getInstance()->applyFilters(
            'GD_CreateUpdate_Post:add-parent-categories',
            false,
            $this
        );
    }

    protected function getCustomPostType($form_data)
    {
        return null;
    }

    protected function isFeaturedimageMandatory()
    {
        return false;
    }

    protected function getCategoriesModule()
    {
        if ($this->showCategories()) {
            if ($this->canInputMultipleCategories()) {
                return [\PoP_Module_Processor_CreateUpdatePostButtonGroupFormInputs::class, \PoP_Module_Processor_CreateUpdatePostButtonGroupFormInputs::MODULE_FORMINPUT_BUTTONGROUP_POSTSECTIONS];
            }

            return [\PoP_Module_Processor_CreateUpdatePostButtonGroupFormInputs::class, \PoP_Module_Processor_CreateUpdatePostButtonGroupFormInputs::MODULE_FORMINPUT_BUTTONGROUP_POSTSECTION];
        }

        return null;
    }

    protected function getEditorInput()
    {
        return [\PoP_Module_Processor_EditorFormInputs::class, \PoP_Module_Processor_EditorFormInputs::MODULE_FORMINPUT_EDITOR];
    }

    protected function getFeaturedimageModule()
    {
        return [\PoP_Module_Processor_FeaturedImageFormComponents::class, \PoP_Module_Processor_FeaturedImageFormComponents::MODULE_FORMCOMPONENT_FEATUREDIMAGE];
    }

    protected function moderate()
    {
        return \GD_CreateUpdate_Utils::moderate();
    }

    protected function canInputMultipleCategories()
    {
        return false;
        // return HooksAPIFacade::getInstance()->applyFilters(
        //     'GD_CreateUpdate_Post:multiple-categories',
        //     true
        // );
    }

    protected function validateCategories()
    {
        if ($this->showCategories()) {
            if ($this->canInputMultipleCategories()) {
                return POP_POSTSCREATION_CONSTANT_VALIDATECATEGORIESTYPE_ATLEASTONE;
            }

            return POP_POSTSCREATION_CONSTANT_VALIDATECATEGORIESTYPE_EXACTLYONE;
        }

        return null;
    }

    protected function getCategoriesErrorMessages()
    {
        return HooksAPIFacade::getInstance()->applyFilters(
            'GD_CreateUpdate_Post:categories-validation:error',
            array(
                'empty-categories' => TranslationAPIFacade::getInstance()->__('The categories have not been set', 'pop-application'),
                'empty-category' => TranslationAPIFacade::getInstance()->__('The category has not been set', 'pop-application'),
                'only-one' => TranslationAPIFacade::getInstance()->__('Only one category can be selected', 'pop-application'),
            )
        );
    }

    protected function supportsTitle()
    {
        // Not all post types support a title
        return true;
    }

    // Update Post Validation
    protected function validatecontent(&$errors, $form_data)
    {
        if ($this->supportsTitle()) {
            if (empty($form_data['title'])) {
                $errors[] = TranslationAPIFacade::getInstance()->__('The title cannot be empty', 'pop-application');
            }
        }

        // Validate the following conditions only if status = pending/publish
        if ($form_data['status'] == Status::DRAFT) {
            return;
        }

        if (empty($form_data['content'])) {
            $errors[] = TranslationAPIFacade::getInstance()->__('The content cannot be empty', 'pop-application');
        }

        if ($this->isFeaturedimageMandatory() && empty($form_data['featuredimage'])) {
            $errors[] = TranslationAPIFacade::getInstance()->__('The featured image has not been set', 'pop-application');
        }

        if ($validateCategories = $this->validateCategories()) {
            $category_error_msgs = $this->getCategoriesErrorMessages();
            if (empty($form_data['categories'])) {
                if ($validateCategories == POP_POSTSCREATION_CONSTANT_VALIDATECATEGORIESTYPE_ATLEASTONE) {
                    $errors[] = $category_error_msgs['empty-categories'];
                } elseif ($validateCategories == POP_POSTSCREATION_CONSTANT_VALIDATECATEGORIESTYPE_EXACTLYONE) {
                    $errors[] = $category_error_msgs['empty-category'];
                }
            } elseif (count($form_data['categories']) > 1 && $validateCategories == POP_POSTSCREATION_CONSTANT_VALIDATECATEGORIESTYPE_EXACTLYONE) {
                $errors[] = $category_error_msgs['only-one'];
            }
        }

        // Allow plugins to add validation for their fields
        HooksAPIFacade::getInstance()->doAction(
            'GD_CreateUpdate_Post:validatecontent',
            array(&$errors),
            $form_data
        );
    }

    protected function validatecreatecontent(&$errors, $form_data)
    {
    }
    protected function validateupdatecontent(&$errors, $form_data)
    {
        if ($this->addReferences()) {
            if (in_array($form_data['customPostID'], $form_data['references'])) {
                $errors[] = TranslationAPIFacade::getInstance()->__('The post cannot be a response to itself', 'pop-postscreation');
            }
        }
    }

    // Update Post Validation
    protected function validatecreate(&$errors)
    {
        // Validate user permission
        $cmsuserrolesapi = \PoPSchema\UserRoles\FunctionAPIFactory::getInstance();
        if (!$cmsuserrolesapi->currentUserCan(NameResolverFacade::getInstance()->getName('popcms:capability:editPosts'))) {
            $errors[] = TranslationAPIFacade::getInstance()->__('Your user doesn\'t have permission for editing.', 'pop-application');
        }
    }

    // Update Post Validation
    protected function validateupdate(&$errors)
    {
        // The ID comes directly as a parameter in the request, it's not a form field
        $post_id = $_REQUEST[POP_INPUTNAME_POSTID];

        // Validate there is postid
        if (!$post_id) {
            $errors[] = TranslationAPIFacade::getInstance()->__('Cheating, huh?', 'pop-application');
            return;
        }

        $customPostTypeAPI = CustomPostTypeAPIFacade::getInstance();
        $post = $customPostTypeAPI->getCustomPost($post_id);
        if (!$post) {
            $errors[] = TranslationAPIFacade::getInstance()->__('Cheating, huh?', 'pop-application');
            return;
        }

        if (!in_array($customPostTypeAPI->getStatus($post_id), array(Status::DRAFT, Status::PENDING, Status::PUBLISHED))) {
            $errors[] = TranslationAPIFacade::getInstance()->__('Hmmmmm, this post seems to have been deleted...', 'pop-application');
            return;
        }

        // Validation below not needed, since this is done in the Checkpoint already
        // // Validate user permission
        // if (!gdCurrentUserCanEdit($post_id)) {
        //     $errors[] = TranslationAPIFacade::getInstance()->__('Your user doesn\'t have permission for editing.', 'pop-application');
        // }

        // // The nonce comes directly as a parameter in the request, it's not a form field
        // $nonce = $_REQUEST[POP_INPUTNAME_NONCE];
        // if (!gdVerifyNonce($nonce, GD_NONCE_EDITURL, $post_id)) {
        //     $errors[] = TranslationAPIFacade::getInstance()->__('Incorrect URL', 'pop-application');
        //     return;
        // }
    }

    /**
     * Function to override
     */
    protected function additionals($post_id, $form_data)
    {
        // Topics
        if (\PoP_ApplicationProcessors_Utils::addCategories()) {
            \PoPSchema\CustomPostMeta\Utils::updateCustomPostMeta($post_id, GD_METAKEY_POST_CATEGORIES, $form_data['topics']);
        }

        // Only if the Volunteering is enabled
        if (defined('POP_VOLUNTEERING_INITIALIZED')) {
            if (defined('POP_VOLUNTEERING_ROUTE_VOLUNTEER') && POP_VOLUNTEERING_ROUTE_VOLUNTEER) {
                // Volunteers Needed?
                if ($this->volunteer()) {
                    \PoPSchema\CustomPostMeta\Utils::updateCustomPostMeta($post_id, GD_METAKEY_POST_VOLUNTEERSNEEDED, $form_data['volunteersneeded'], true, true);
                }
            }
        }

        if (\PoP_ApplicationProcessors_Utils::addAppliesto()) {
            \PoPSchema\CustomPostMeta\Utils::updateCustomPostMeta($post_id, GD_METAKEY_POST_APPLIESTO, $form_data['appliesto']);
        }
    }
    /**
     * Function to override
     */
    protected function updateadditionals($post_id, $form_data, $log)
    {
    }
    /**
     * Function to override
     */
    protected function createadditionals($post_id, $form_data)
    {
    }

    protected function getFormData()
    {
        $cmseditpostshelpers = \PoP\EditPosts\HelperAPIFactory::getInstance();
        $moduleprocessor_manager = ModuleProcessorManagerFacade::getInstance();

        $editor = $this->getEditorInput();
        $form_data = array(
            'customPostID' => $_REQUEST[POP_INPUTNAME_POSTID],
            'content' => trim($cmseditpostshelpers->kses(stripslashes($moduleprocessor_manager->getProcessor($editor)->getValue($editor)))),
            'categories' => $this->getCategories(),
        );

        if ($this->supportsTitle()) {
            $form_data['title'] = trim(strip_tags($moduleprocessor_manager->getProcessor([\PoP_Module_Processor_CreateUpdatePostTextFormInputs::class, \PoP_Module_Processor_CreateUpdatePostTextFormInputs::MODULE_FORMINPUT_CUP_TITLE])->getValue([\PoP_Module_Processor_CreateUpdatePostTextFormInputs::class, \PoP_Module_Processor_CreateUpdatePostTextFormInputs::MODULE_FORMINPUT_CUP_TITLE])));
        }

        if ($featuredimage = $this->getFeaturedimageModule()) {
            $form_data['featuredimage'] = $moduleprocessor_manager->getProcessor($featuredimage)->getValue($featuredimage);
        }

        // Status: 2 possibilities:
        // - Moderate: then using the Draft/Pending/Publish Select, user cannot choose 'Publish' when creating a post
        // - No moderation: using the 'Keep as Draft' checkbox, completely omitting value 'Pending', post is either 'draft' or 'publish'
        if ($this->moderate()) {
            $form_data['status'] = $moduleprocessor_manager->getProcessor([\PoP_Module_Processor_CreateUpdatePostSelectFormInputs::class, \PoP_Module_Processor_CreateUpdatePostSelectFormInputs::MODULE_FORMINPUT_CUP_STATUS])->getValue([\PoP_Module_Processor_CreateUpdatePostSelectFormInputs::class, \PoP_Module_Processor_CreateUpdatePostSelectFormInputs::MODULE_FORMINPUT_CUP_STATUS]);
        } else {
            $keepasdraft = $moduleprocessor_manager->getProcessor([\PoP_Module_Processor_CreateUpdatePostCheckboxFormInputs::class, \PoP_Module_Processor_CreateUpdatePostCheckboxFormInputs::MODULE_FORMINPUT_CUP_KEEPASDRAFT])->getValue([\PoP_Module_Processor_CreateUpdatePostCheckboxFormInputs::class, \PoP_Module_Processor_CreateUpdatePostCheckboxFormInputs::MODULE_FORMINPUT_CUP_KEEPASDRAFT]);
            $form_data['status'] = $keepasdraft ? Status::DRAFT : Status::PUBLISHED;
        }

        if ($this->addReferences()) {
            $references = $moduleprocessor_manager->getProcessor([\PoP_Module_Processor_PostSelectableTypeaheadFormComponents::class, \PoP_Module_Processor_PostSelectableTypeaheadFormComponents::MODULE_FORMCOMPONENT_SELECTABLETYPEAHEAD_REFERENCES])->getValue([\PoP_Module_Processor_PostSelectableTypeaheadFormComponents::class, \PoP_Module_Processor_PostSelectableTypeaheadFormComponents::MODULE_FORMCOMPONENT_SELECTABLETYPEAHEAD_REFERENCES]);
            $form_data['references'] = $references ?? array();
        }

        if (\PoP_ApplicationProcessors_Utils::addCategories()) {
            $topics = $moduleprocessor_manager->getProcessor([\PoP_Module_Processor_CreateUpdatePostMultiSelectFormInputs::class, \PoP_Module_Processor_CreateUpdatePostMultiSelectFormInputs::MODULE_FORMINPUT_CATEGORIES])->getValue([\PoP_Module_Processor_CreateUpdatePostMultiSelectFormInputs::class, \PoP_Module_Processor_CreateUpdatePostMultiSelectFormInputs::MODULE_FORMINPUT_CATEGORIES]);
            $form_data['topics'] = $topics ?? array();
        }

        // Only if the Volunteering is enabled
        if (defined('POP_VOLUNTEERING_INITIALIZED')) {
            if (defined('POP_VOLUNTEERING_ROUTE_VOLUNTEER') && POP_VOLUNTEERING_ROUTE_VOLUNTEER) {
                if ($this->volunteer()) {
                    $form_data['volunteersneeded'] = $moduleprocessor_manager->getProcessor([\GD_Custom_Module_Processor_SelectFormInputs::class, \GD_Custom_Module_Processor_SelectFormInputs::MODULE_FORMINPUT_VOLUNTEERSNEEDED_SELECT])->getValue([\GD_Custom_Module_Processor_SelectFormInputs::class, GD_Custom_Module_Processor_SelectFormInputs::MODULE_FORMINPUT_VOLUNTEERSNEEDED_SELECT]);
                }
            }
        }

        if (\PoP_ApplicationProcessors_Utils::addAppliesto()) {
            $appliesto = $moduleprocessor_manager->getProcessor([\PoP_Module_Processor_CreateUpdatePostMultiSelectFormInputs::class, \PoP_Module_Processor_CreateUpdatePostMultiSelectFormInputs::MODULE_FORMINPUT_APPLIESTO])->getValue([\PoP_Module_Processor_CreateUpdatePostMultiSelectFormInputs::class, PoP_Module_Processor_CreateUpdatePostMultiSelectFormInputs::MODULE_FORMINPUT_APPLIESTO]);
            $form_data['appliesto'] = $appliesto ?? array();
        }

        // Allow plugins to add their own fields
        return HooksAPIFacade::getInstance()->applyFilters(
            'GD_CreateUpdate_Post:form-data',
            $form_data
        );
    }

    protected function maybeAddParentCategories($categories)
    {
        $categoryapi = \PoPSchema\Categories\FunctionAPIFactory::getInstance();
        // If the categories are nested under other categories, ask if to add those too
        if ($this->addParentCategories()) {
            // Use a while, to also check if the parent category has a parent itself
            $i = 0;
            while ($i < count($categories)) {
                $cat = $categories[$i];
                $i++;

                if ($parent_cat = $categoryapi->getCategoryParent($cat)) {
                    $categories[] = $parent_cat;
                }
            }
        }

        return $categories;
    }

    protected function maybeAddPostCategories(&$post_data, $form_data)
    {

        // Only if it is a post_category
        if ($this->getCategoryTaxonomy() == 'category') {
            if ($cats = $form_data['categories']) {
                $cats = $this->maybeAddParentCategories($cats);
                $post_data['post-categories'] = $cats;
            }
        }
    }

    protected function maybeAddPostType(&$post_data, $form_data)
    {
        if ($post_type = $this->getCustomPostType($form_data)) {
            $post_data['custom-post-type'] = $post_type;
        }
    }

    protected function getUpdatepostData($form_data)
    {
        $post_data = array(
            'id' => $form_data['customPostID'],
            'post-content' => $form_data['content'],
        );

        if ($this->supportsTitle()) {
            $post_data['post-title'] = $form_data['title'];
        }


        // Add Post Categories and Post Type
        $this->maybeAddPostCategories($post_data, $form_data);
        $this->maybeAddPostType($post_data, $form_data);

        // Status: Validate the value is permitted, or get the default value otherwise
        if ($status = \GD_CreateUpdate_Utils::getUpdatepostStatus($form_data['status'], $this->moderate())) {
            $post_data['custom-post-status'] = $status;
        }

        return $post_data;
    }

    protected function getCreatepostData($form_data)
    {

        // Status: Validate the value is permitted, or get the default value otherwise
        $status = \GD_CreateUpdate_Utils::getCreatepostStatus($form_data['status'], $this->moderate());
        $post_data = array(
            'post-content' => $form_data['content'],
            'custom-post-status' => $status,
        );

        if ($this->supportsTitle()) {
            $post_data['post-title'] = $form_data['title'];
        }

        // Add Post Categories and Post Type
        $this->maybeAddPostCategories($post_data, $form_data);
        $this->maybeAddPostType($post_data, $form_data);

        return $post_data;
    }

    protected function executeUpdatepost($post_data)
    {
        $cmseditpostsapi = \PoP\EditPosts\FunctionAPIFactory::getInstance();
        return $cmseditpostsapi->updatePost($post_data);
    }

    protected function createupdatepost(&$errors, $form_data, $post_id)
    {

        // Set category taxonomy for taxonomies other than "category"
        $taxonomyapi = \PoPSchema\Taxonomies\FunctionAPIFactory::getInstance();
        $taxonomy = $this->getCategoryTaxonomy();
        if ($taxonomy != 'category') {
            if ($cats = $form_data['categories']) {
                $cats = $this->maybeAddParentCategories($cats);
                $taxonomyapi->setPostTerms($post_id, $cats, $taxonomy);
            }
        }

        $this->setfeaturedimage($errors, $post_id, $form_data);

        if ($this->addReferences()) {
            \PoPSchema\CustomPostMeta\Utils::updateCustomPostMeta($post_id, GD_METAKEY_POST_REFERENCES, $form_data['references']);
        }
    }

    protected function getUpdatepostDataLog($post_id, $form_data)
    {
        $customPostTypeAPI = CustomPostTypeAPIFacade::getInstance();
        $log = array(
            'previous-status' => $customPostTypeAPI->getStatus($post_id),
        );

        if ($this->addReferences()) {
            $previous_references = \PoPSchema\CustomPostMeta\Utils::getCustomPostMeta($post_id, GD_METAKEY_POST_REFERENCES);
            $log['new-references'] = array_diff($form_data['references'], $previous_references);
        }

        return $log;
    }

    protected function updatepost(&$errors, $form_data)
    {
        $post_data = $this->getUpdatepostData($form_data);
        $post_id = $post_data['id'];

        // Create the operation log, to see what changed. Needed for
        // - Send email only when post published
        // - Add user notification of post being referenced, only when the reference is new (otherwise it will add the notification each time the user updates the post)
        $log = $this->getUpdatepostDataLog($post_id, $form_data);

        $result = $this->executeUpdatepost($post_data);

        if ($result === 0) {
            $errors[] = TranslationAPIFacade::getInstance()->__('Oops, there was a problem... this is embarrassing, huh?', 'pop-application');
            return;
        }

        $this->createupdatepost($errors, $form_data, $post_id);

        // Allow for additional operations (eg: set Action categories)
        $this->additionals($post_id, $form_data);
        $this->updateadditionals($post_id, $form_data, $log);

        // Inject Share profiles here
        HooksAPIFacade::getInstance()->doAction('gd_createupdate_post', $post_id, $form_data);
        HooksAPIFacade::getInstance()->doAction('gd_createupdate_post:update', $post_id, $log, $form_data);
    }

    protected function executeCreatepost($post_data)
    {
        $cmseditpostsapi = \PoP\EditPosts\FunctionAPIFactory::getInstance();
        return $cmseditpostsapi->insertPost($post_data);
    }

    protected function createpost(&$errors, $form_data)
    {
        $post_data = $this->getCreatepostData($form_data);
        $post_id = $this->executeCreatepost($post_data);

        if ($post_id == 0) {
            $errors[] = TranslationAPIFacade::getInstance()->__('Oops, there was a problem... this is embarrassing, huh?', 'pop-application');
            return;
        }

        $this->createupdatepost($errors, $form_data, $post_id);

        // Allow for additional operations (eg: set Action categories)
        $this->additionals($post_id, $form_data);
        $this->createadditionals($post_id, $form_data);

        // Inject Share profiles here
        HooksAPIFacade::getInstance()->doAction('gd_createupdate_post', $post_id, $form_data);
        HooksAPIFacade::getInstance()->doAction('gd_createupdate_post:create', $post_id, $form_data);

        return $post_id;
    }

    protected function setfeaturedimage(&$errors, $post_id, $form_data)
    {
        if ($this->getFeaturedimageModule()) {
            $featuredimage = $form_data['featuredimage'];

            // Featured Image
            if ($featuredimage) {
                \set_post_thumbnail($post_id, $featuredimage);
            } else {
                \delete_post_thumbnail($post_id);
            }
        }
    }

    protected function update(&$errors)
    {
        // If already exists any of these errors above, return errors
        $this->validateupdate($errors);
        if ($errors) {
            return;
        }

        $form_data = $this->getFormData();

        $this->validateupdatecontent($errors, $form_data);
        $this->validatecontent($errors, $form_data);
        if ($errors) {
            return;
        }

        // Do the Post update
        $this->updatepost($errors, $form_data);
        // if ($errors) {
        //     return;
        // }

        // No errors, return empty array (signifying no errors);
        // return array();
    }

    protected function create(&$errors)
    {
        // If already exists any of these errors above, return errors
        $this->validatecreate($errors);
        if ($errors) {
            return;
        }

        $form_data = $this->getFormData();

        $this->validatecreatecontent($errors, $form_data);
        $this->validatecontent($errors, $form_data);
        if ($errors) {
            return;
        }

        // Do the Post update
        $post_id = $this->createpost($errors, $form_data);
        return $post_id;
        // if ($errors) {
        //     return;
        // }

        // // No errors, return empty array (signifying no errors);
        // return array();
    }
}
