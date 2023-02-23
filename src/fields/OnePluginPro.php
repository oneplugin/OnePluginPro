<?php

/**
 * OnePlugin Pro plugin for Craft CMS 3.x
 *
 * OnePlugin Pro lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginpro\fields;

use Craft;

use yii\db\Schema;
use craft\base\Field;
use craft\helpers\Json;
use craft\base\ElementInterface;
use craft\web\assets\cp\CpAsset;
use oneplugin\onepluginpro\models\OnePluginProAsset;
use oneplugin\onepluginpro\gql\types\OnePluginProGqlType;
use oneplugin\onepluginpro\OnePluginPro as OnePluginProPlugin;

class OnePluginPro extends Field
{

    public $mandatory = false;

    /** @var array */
    public $allowedContents = '*';
    public $allowedSources = '*';

    public static function displayName(): string
    {
        return Craft::t('one-plugin-pro', 'OnePlugin Pro');
    }

    // Public Methods
    // =========================================================================

    public function rules(): array
    {
        $rules = parent::rules();
        return $rules;
    }

    
    public function getContentColumnType(): string
    {
        return Schema::TYPE_TEXT;
    }

    public function getElementValidationRules(): array
    {
        if( $this->mandatory){
            return [
                ['required']
            ];
        }
        else{
            return [];
        }
    }
    
    public function normalizeValue($value, ElementInterface $element = null): mixed
    {
        if( $value ==  null)
        {
            return null;
        }
        if ($value instanceof OnePluginProAsset)
        {
            return $value;
        }
        
        if (is_array($value) && empty($value))
        {
            return null;
        }
        
        // quick array transform so that we can ensure and `required fields` fire an error
        $valueData = (array)json_decode($value);
        // if we have actual data return model
        if (count($valueData) > 0)
        {
            return new OnePluginProAsset($value);
        }
        else{
            return null;
        }
        return $value;
    }

    public function serializeValue($value, ElementInterface $element = null): mixed
    {

        if ($value instanceof OnePluginProAsset)
        {
            $value = $value->json;
        }
        return parent::serializeValue($value, $element);
    }

    public function getSettingsHtml():string
    {  
        return Craft::$app->getView()->renderTemplate(
            'one-plugin-pro/_components/fields/_settings',
            [
                'field' => $this,
                'availableContents' => $this->availableContent(),
                'availableSources' => $this->availableSources()
            ]
        );
    }

    
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $settings = OnePluginProPlugin::$plugin->getSettings();
        
        $folder = 'dist';
        if( OnePluginProPlugin::$devMode ){
            $folder = 'src';
        }
        $baseAssetsUrl = Craft::$app->assetManager->getPublishedUrl(
            '@oneplugin/onepluginpro/assetbundles/onepluginpro/' . $folder,
            true
        );
        $cssFiles = [];
        $jsFiles = [];

        if( OnePluginProPlugin::$devMode ){
            $cssFiles = [$baseAssetsUrl . '/css/onepluginpro.css',$baseAssetsUrl . '/themes/default/style.css'];
            $jsFiles = [ $baseAssetsUrl . '/js/icons/lottie_svg.js',$baseAssetsUrl . '/js/icons/onepluginpro-lottie.js',$baseAssetsUrl . '/js/onepluginpro.js',$baseAssetsUrl . '/js/spectrum.min.js',$baseAssetsUrl . '/js/jstree.js',$baseAssetsUrl . '/js/selectric.min.js'];
        }
        else{
            $cssFiles = [$baseAssetsUrl . '/css/onepluginpro.min.css',$baseAssetsUrl . '/themes/default/style.min.css'];
            $jsFiles = [$baseAssetsUrl . '/js/onepluginpro-cp.min.js'];
        }
        
        $dynamicMaps =  false;
        
        foreach ($cssFiles as $cssFile) {
            Craft::$app->getView()->registerCssFile($cssFile);
        }
        if( !empty($settings->mapsAPIKey) ){
            $dynamicMaps = true;
            Craft::$app->getView()->registerJsFile('https://maps.googleapis.com/maps/api/js?key=' . $settings->mapsAPIKey . '&libraries=places&v=3.exp',['depends' => CpAsset::class]);
        }
        foreach ($jsFiles as $jsFile) {
            Craft::$app->getView()->registerJsFile($jsFile,['depends' => CpAsset::class]);
        }
        

        // Get our id and namespace
        $id = Craft::$app->getView()->formatInputId($this->handle);
        $namespacedId = Craft::$app->getView()->namespaceInputId($id);

        // Variables to pass down to our field JavaScript to let it namespace properly
        $allowedContents = is_array($this->allowedContents) ? $this->allowedContents : [$this->allowedContents ];

        $allowedSources = is_array($this->allowedSources) ? $this->allowedSources : [$this->allowedSources ];
        if( sizeof( $allowedSources ) == 1 && ( empty($allowedSources[0]) || $allowedSources[0] == '*' ) ){
            $allowedSources = '*';
        }
        $jsonVars = [
            'namespace' => $namespacedId,
            //'volumes' => implode(',',$this->getAllVolumes()),
            //'folders' => implode(',',$this->getAllFolders()),
            'primary-color' => $settings->primaryColor,
            'secondary-color' => $settings->secondaryColor,
            'stroke-width' => $settings->strokeWidth,
            'svg-stroke-color' => $settings->svgStrokeColor,
            'svg-stroke-width' => $settings->svgStrokeWidth,
            'allowedSources' => $allowedSources,
            'dynamicMaps' => $dynamicMaps
            ];
        $jsonVars = Json::encode($jsonVars);
        Craft::$app->getView()->registerJs("new OnePluginProSelectInput(" . $jsonVars . ");");

        // Render the input template
        $asset = null;
        if( $value != null && ( $value->iconData['type'] == 'imageAsset') ){
            if( isset($value->iconData['id']) && !empty($value->iconData['id'])){
                $asset = Craft::$app->getAssets()->getAssetById($value->iconData['id']);
            }
        }
        return Craft::$app->getView()->renderTemplate(
            'one-plugin-pro/_components/fields/_input',
            [
                'name' => $this->handle,
                'fieldValue' => $value,
                'field' => $this,
                'id' => $id,
                'settings' => $settings,
                'allowedContents' => $allowedContents,
                'allowedSources' => $allowedSources,
                'asset' => $asset
            ]
        );
    }

    public function getContentGqlType(): \GraphQL\Type\Definition\Type|array
    {
        $typeArray = OnePluginProGqlType::generateTypes($this);

        return [
            'name' => $this->handle,
            'description' => "OnepluginPro field",
            'type' => array_shift($typeArray),
        ];
    }

    private function availableContent(): array{

        return [['label' => 'All','value' =>'*'], 
                ['label' => 'Images','value' =>'imageAsset'],
                ['label' => 'Animated Icons','value' =>'animatedIcons'],
                ['label' => 'SVG Icons','value' =>'svg'],
                ];
    }

    private function availableSources(): array{

        $sources = Craft::$app->getElementSources()->getSources('craft\elements\Asset', 'modal');
        $options = [];
        $optionNames = [];
        foreach ($sources as $source) {
            if (!isset($source['heading'])) {
                $options[] = [
                    'label' => $source['label'],
                    'value' => $source['key'],
                ];
                $optionNames[] = $source['label'];
            }
        }
        array_multisort($optionNames, SORT_NATURAL | SORT_FLAG_CASE, $options);
        return $options;
    }
}
