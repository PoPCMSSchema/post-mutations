<?php

declare(strict_types=1);

namespace PoPSchema\PostMutations\FieldResolvers;

use PoP\ComponentModel\Schema\SchemaHelpers;
use PoP\ComponentModel\Schema\SchemaDefinition;
use PoP\ComponentModel\Schema\TypeCastingHelpers;
use PoP\Translation\Facades\TranslationAPIFacade;
use PoPSchema\Posts\TypeResolvers\PostTypeResolver;
use PoPSchema\Media\TypeResolvers\MediaTypeResolver;
use PoPSchema\CustomPosts\Enums\CustomPostStatusEnum;
use PoP\ComponentModel\TypeResolvers\TypeResolverInterface;
use PoP\ComponentModel\Facades\Instances\InstanceManagerFacade;
use PoPSchema\CustomPosts\TypeResolvers\CustomPostTypeResolver;
use PoP\ComponentModel\FieldResolvers\AbstractQueryableFieldResolver;
use PoPSchema\PostMutations\MutationResolvers\UpdatePostMutationResolver;
use PoPSchema\CustomPostMutations\MutationResolvers\MutationInputProperties;

class PostFieldResolver extends AbstractQueryableFieldResolver
{
    public static function getClassesToAttachTo(): array
    {
        return array(PostTypeResolver::class);
    }

    public static function getFieldNamesToResolve(): array
    {
        return [
            'update',
        ];
    }

    public function getSchemaFieldDescription(TypeResolverInterface $typeResolver, string $fieldName): ?string
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        $descriptions = [
            'update' => $translationAPI->__('Update the post', 'post-mutations'),
        ];
        return $descriptions[$fieldName] ?? parent::getSchemaFieldDescription($typeResolver, $fieldName);
    }

    public function getSchemaFieldType(TypeResolverInterface $typeResolver, string $fieldName): ?string
    {
        $types = [
            'update' => SchemaDefinition::TYPE_ID,
        ];
        return $types[$fieldName] ?? parent::getSchemaFieldType($typeResolver, $fieldName);
    }

    public function getSchemaFieldArgs(TypeResolverInterface $typeResolver, string $fieldName): array
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        switch ($fieldName) {
            case 'update':
                $instanceManager = InstanceManagerFacade::getInstance();
                /**
                 * @var CustomPostStatusEnum
                 */
                $customPostStatusEnum = $instanceManager->getInstance(CustomPostStatusEnum::class);
                return [
                    [
                        SchemaDefinition::ARGNAME_NAME => MutationInputProperties::TITLE,
                        SchemaDefinition::ARGNAME_TYPE => SchemaDefinition::TYPE_STRING,
                        SchemaDefinition::ARGNAME_DESCRIPTION => $translationAPI->__('The title of the post', 'post-mutations'),
                    ],
                    [
                        SchemaDefinition::ARGNAME_NAME => MutationInputProperties::CONTENT,
                        SchemaDefinition::ARGNAME_TYPE => SchemaDefinition::TYPE_STRING,
                        SchemaDefinition::ARGNAME_DESCRIPTION => $translationAPI->__('The content of the post', 'post-mutations'),
                    ],
                    [
                        SchemaDefinition::ARGNAME_NAME => MutationInputProperties::STATUS,
                        SchemaDefinition::ARGNAME_TYPE => SchemaDefinition::TYPE_ENUM,
                        SchemaDefinition::ARGNAME_DESCRIPTION => $translationAPI->__('The status of the post', 'post-mutations'),
                        SchemaDefinition::ARGNAME_ENUM_NAME => $customPostStatusEnum->getName(),
                        SchemaDefinition::ARGNAME_ENUM_VALUES => SchemaHelpers::convertToSchemaFieldArgEnumValueDefinitions(
                            $customPostStatusEnum->getValues()
                        ),
                    ],
                    [
                        SchemaDefinition::ARGNAME_NAME => MutationInputProperties::CATEGORIES,
                        SchemaDefinition::ARGNAME_TYPE => TypeCastingHelpers::makeArray(SchemaDefinition::TYPE_ID),
                        SchemaDefinition::ARGNAME_DESCRIPTION => sprintf(
                            $translationAPI->__('The IDs of the categories (of type %s)', 'post-mutations'),
                            'PostCategory'// PostCategory::class
                        ),
                    ],
                    [
                        SchemaDefinition::ARGNAME_NAME => MutationInputProperties::FEATUREDIMAGE,
                        SchemaDefinition::ARGNAME_TYPE => SchemaDefinition::TYPE_ID,
                        SchemaDefinition::ARGNAME_DESCRIPTION => sprintf(
                            $translationAPI->__('The ID of the featured image (of type %s)', 'post-mutations'),
                            MediaTypeResolver::NAME
                        ),
                    ],
                ];
        }
        return parent::getSchemaFieldArgs($typeResolver, $fieldName);
    }

    protected function getFieldArgsToExecuteMutation(
        array $fieldArgs,
        TypeResolverInterface $typeResolver,
        object $resultItem,
        string $fieldName,
        array $options = []
    ): array {
        $fieldArgs = parent::getFieldArgsToExecuteMutation(
            $fieldArgs,
            $typeResolver,
            $resultItem,
            $fieldName,
            $options
        );
        $post = $resultItem;
        switch ($fieldName) {
            case 'update':
                $fieldArgs[MutationInputProperties::ID] = $typeResolver->getID($post);
                break;
        }

        return $fieldArgs;
    }

    public function resolveFieldMutationResolverClass(TypeResolverInterface $typeResolver, string $fieldName): ?string
    {
        switch ($fieldName) {
            case 'update':
                return UpdatePostMutationResolver::class;
        }

        return parent::resolveFieldMutationResolverClass($typeResolver, $fieldName);
    }

    public function resolveFieldTypeResolverClass(TypeResolverInterface $typeResolver, string $fieldName): ?string
    {
        switch ($fieldName) {
            case 'update':
                return PostTypeResolver::class;
        }

        return parent::resolveFieldTypeResolverClass($typeResolver, $fieldName);
    }
}
