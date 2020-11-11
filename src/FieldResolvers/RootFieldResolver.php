<?php

declare(strict_types=1);

namespace PoPSchema\PostMutations\FieldResolvers;

use PoPSchema\CustomPosts\Types\Status;
use PoP\ComponentModel\Schema\SchemaHelpers;
use PoP\Engine\TypeResolvers\RootTypeResolver;
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
use PoPSchema\PostMutations\MutationResolvers\CreatePostMutationResolver;
use PoPSchema\PostMutations\MutationResolvers\UpdatePostMutationResolver;
use PoPSchema\CustomPostMutations\MutationResolvers\MutationInputProperties;

class RootFieldResolver extends AbstractQueryableFieldResolver
{
    public static function getClassesToAttachTo(): array
    {
        return array(RootTypeResolver::class);
    }

    public static function getFieldNamesToResolve(): array
    {
        return [
            'createPost',
            'updatePost',
        ];
    }

    public function getSchemaFieldDescription(TypeResolverInterface $typeResolver, string $fieldName): ?string
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        $descriptions = [
            'createPost' => $translationAPI->__('Create a post', 'post-mutations'),
            'updatePost' => $translationAPI->__('Update a post', 'post-mutations'),
        ];
        return $descriptions[$fieldName] ?? parent::getSchemaFieldDescription($typeResolver, $fieldName);
    }

    public function getSchemaFieldType(TypeResolverInterface $typeResolver, string $fieldName): ?string
    {
        $types = [
            'createPost' => SchemaDefinition::TYPE_ID,
            'updatePost' => SchemaDefinition::TYPE_ID,
        ];
        return $types[$fieldName] ?? parent::getSchemaFieldType($typeResolver, $fieldName);
    }

    public function getSchemaFieldArgs(TypeResolverInterface $typeResolver, string $fieldName): array
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        switch ($fieldName) {
            case 'createPost':
            case 'updatePost':
                $instanceManager = InstanceManagerFacade::getInstance();
                /**
                 * @var CustomPostStatusEnum
                 */
                $customPostStatusEnum = $instanceManager->getInstance(CustomPostStatusEnum::class);
                return array_merge(
                    $fieldName == 'updatePost' ? [
                        [
                            SchemaDefinition::ARGNAME_NAME => MutationInputProperties::ID,
                            SchemaDefinition::ARGNAME_TYPE => SchemaDefinition::TYPE_ID,
                            SchemaDefinition::ARGNAME_DESCRIPTION => $translationAPI->__('The ID of the post to update', 'post-mutations'),
                            SchemaDefinition::ARGNAME_MANDATORY => true,
                        ],
                    ] : [],
                    [
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
                        array_merge(
                            [
                                SchemaDefinition::ARGNAME_NAME => MutationInputProperties::STATUS,
                                SchemaDefinition::ARGNAME_TYPE => SchemaDefinition::TYPE_ENUM,
                                SchemaDefinition::ARGNAME_DESCRIPTION => $translationAPI->__('The status of the post', 'post-mutations'),
                                SchemaDefinition::ARGNAME_ENUM_NAME => $customPostStatusEnum->getName(),
                                SchemaDefinition::ARGNAME_ENUM_VALUES => SchemaHelpers::convertToSchemaFieldArgEnumValueDefinitions(
                                    $customPostStatusEnum->getValues()
                                ),
                            ],
                            $fieldName == 'createPost' ? [
                                SchemaDefinition::ARGNAME_DEFAULT_VALUE => Status::PUBLISHED,
                            ] : [],
                        ),
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
                    ]
                );
        }
        return parent::getSchemaFieldArgs($typeResolver, $fieldName);
    }

    public function resolveFieldMutationResolverClass(TypeResolverInterface $typeResolver, string $fieldName, array $fieldArgs = []): ?string
    {
        switch ($fieldName) {
            case 'createPost':
                return CreatePostMutationResolver::class;
            case 'updatePost':
                return UpdatePostMutationResolver::class;
        }

        return parent::resolveFieldMutationResolverClass($typeResolver, $fieldName, $fieldArgs);
    }

    public function resolveFieldTypeResolverClass(TypeResolverInterface $typeResolver, string $fieldName, array $fieldArgs = []): ?string
    {
        switch ($fieldName) {
            case 'createPost':
            case 'updatePost':
                return PostTypeResolver::class;
        }

        return parent::resolveFieldTypeResolverClass($typeResolver, $fieldName, $fieldArgs);
    }
}
