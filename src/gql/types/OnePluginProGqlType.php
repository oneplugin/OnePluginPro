<?php

namespace oneplugin\onepluginpro\gql\types;

use craft\gql\TypeLoader;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\Type;
use craft\gql\base\GeneratorInterface;
use GraphQL\Type\Definition\InputObjectType;
use oneplugin\onepluginpro\gql\models\ImageGql;
use oneplugin\onepluginpro\gql\models\SVGIconGql;
use oneplugin\onepluginpro\gql\models\AnimatedIconGql;
use oneplugin\onepluginpro\gql\resolvers\OnePluginProResolver;
/**
 * Class LinkGqlType
 */
class OnePluginProGqlType implements GeneratorInterface
{
  /**
   * @return string
   */
    public static function getName($context = null): string
    {
      return 'OnepluginPro_Field';
    }

  /**
   * @return Type
   */
  public static function generateTypes($context = null): array{

    $tagArgument = GqlEntityRegistry::getEntity("OnePluginPro_TagArgument") ?: GqlEntityRegistry::createEntity("OnePluginPro_TagArgument", new InputObjectType([
        'name' => 'Tag Argument',
        'fields' => [
          'class' => [
            'name' => 'class',
            'type' => Type::string(),
          ],
          'size' => [
            'name' => 'size',
            'type' => Type::boolean(),
          ],
          'width' => [
            'name' => 'width',
            'type' => Type::string(),
          ],
          'height' => [
            'name' => 'height',
            'type' => Type::string(),
          ],
          'alt' => [
            'name' => 'alt',
            'type' => Type::string(),
          ]
        ]]));

    $typeName = self::getName($context);

    $onePluginPro = [
      'name' => [
        'name' => 'name',
        'type' => Type::string(),
      ],
      'type' => [
        'name' => 'type',
        'type' => Type::string(),
      ],
      'jsAssets' => [
        'name' => 'jsAssets',
        'type' => Type::listOf(Type::string()),
      ],
      'tag' => [
        'name' => 'tag',
        'type' => Type::string(),
        'args' => [
          'options' => [
              'name' => 'options',
              'type' => Type::listOf($tagArgument),
              'description' => 'If true, returns webp images.'
          ],
        ],
        'description' => 'A `<oneplugin>` tag based on this asset.',
      ],
      'src' => [
        'name' => 'src',
        'type' => Type::string(),
        'description' => 'Returns a `src` attribute value',
      ],
      'image' => [
        'name' => 'image',
        'type' => ImageGql::getType(),
      ],
      'animatedIcon' => [
        'name' => 'animatedIcon',
        'type' => AnimatedIconGql::getType(),
      ],
      'svgIcon' => [
        'name' => 'svgIcon',
        'type' => SVGIconGql::getType(),
      ]
    ];

    $type = GqlEntityRegistry::getEntity($typeName)
        ?: GqlEntityRegistry::createEntity(self::class, new OnePluginProResolver([
          'name'   => static::getName(),
          'fields' => function () use ($onePluginPro) {
            return $onePluginPro;
          },
          'description' => 'This is the interface implemented by OnePluginPro.',
        ]));

    TypeLoader::registerType($typeName, function () use ($type) {
        return $type;
    });

    return [$type];
  }
}
