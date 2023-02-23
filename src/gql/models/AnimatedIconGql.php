<?php

/**
 * OnePlugin Pro plugin for Craft CMS 3.x
 *
 * OnePlugin Pro lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginpro\gql\models;

use craft\base\Model;
use craft\gql\TypeLoader;
use craft\helpers\UrlHelper;
use craft\gql\base\GqlTypeTrait;
use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use oneplugin\onepluginpro\records\OnePluginProAnimatedIcon;
use oneplugin\onepluginpro\gql\resolvers\OnePluginProResolver;

class AnimatedIconGql extends Model
{
    use GqlTypeTrait;

    public $iconData = null;

    public static function getName($context = null): string
    {
        return 'OnePluginPro_AnimatedIcon';
    }

    static public function getType(): Type
    {
      $typeName = self::getName();
        $type = GqlEntityRegistry::getEntity($typeName)
          ?: GqlEntityRegistry::createEntity($typeName, new OnePluginProResolver([
          'name'   => static::getName(),
          'fields' => self::class . '::getFieldDefinitions',
          'description' => 'The interface implemented by OnePluginPro Animated Icon type.',
          ]));

        TypeLoader::registerType(static::getName(), function () use ($type) {
          return $type;
        });
      
      return $type;
    }

    /**
     * @return array
     */
    public static function getFieldDefinitions(): array {
      return [
        'iconName' => [
          'name' => 'iconName',
          'type' => Type::string(),
        ],
        'primaryColor' => [
          'name' => 'primaryColor',
          'type' => Type::string(),
        ],
        'secondaryColor' => [
          'name' => 'secondaryColor',
          'type' => Type::string(),
        ],
        'strokeWidth' => [
          'name' => 'strokeWidth',
          'type' => Type::float(),
        ],
        'trigger' => [
          'name' => 'trigger',
          'type' => Type::string(),
        ],
        'src' => [
          'name' => 'src',
          'type' => Type::string(),
          'description' => 'Returns a `src` attribute value',
        ],
        'icon' => [
          'name' => 'icon',
          'type' => Type::string(),
        ]
      ];
    }

    public function __construct($value)
    {
        if( $value != null){
            $this->iconData = (array)json_decode($value,true);
        }
        else{
            $this->iconData = [];
        }
    }

    public function getIconName() {
        return $this->iconData['asset']['icon-name'];
    }

    public function getPrimaryColor() {
        return $this->iconData['asset']['icon-primary'];
    }

    public function getSecondaryColor() {
        return $this->iconData['asset']['icon-secondary'];
    }

    public function getStrokeWidth() {
      return is_null($this->iconData['asset']['icon-stroke-width'])? 1.0: floatval($this->iconData['asset']['icon-stroke-width']);
    }

    
    public function getTrigger() {
      return $this->iconData['asset']['icon-trigger'];
    }

    public function getSrc() {      
        return UrlHelper::actionUrl('one-plugin-pro/one-plugin/load/',[ 'name' => $this->iconData['asset']['icon-name'],'type' => 'aicon','trigger'=>$this->iconData['asset']['icon-trigger'] ] );
    }

    public function getIcon() {
      $icons = OnePluginProAnimatedIcon::find()->where(['name' => $this->iconData['asset']['icon-name']])->all();
      if( count($icons) > 0 ){
          if( !empty($trigger) && ($trigger == 'morph' || $trigger == 'morph-two-way') ){
              return $icons[0]['data_morph'];
          }
          else{
            return $icons[0]['data_loop'];
          }
      }
      
      return null;
    }
    
}
