<?php

/**
 * OnePlugin Pro plugin for Craft CMS 3.x
 *
 * OnePlugin Pro lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginpro\models;

use Craft;
use craft\helpers\UrlHelper;
use oneplugin\onepluginpro\OnePluginPro;
use craft\helpers\Template as TemplateHelper;
use oneplugin\onepluginpro\gql\models\ImageGql;
use oneplugin\onepluginpro\render\BaseRenderer;
use oneplugin\onepluginpro\render\ImageRenderer;
use oneplugin\onepluginpro\gql\models\SVGIconGql;
use oneplugin\onepluginpro\render\RenderInterface;
use oneplugin\onepluginpro\render\SVGIconRenderer;
use oneplugin\onepluginpro\gql\models\AnimatedIconGql;
use oneplugin\onepluginpro\render\AnimatedIconRenderer;

class OnePluginProAsset
{
    private $defaultSize = ["animatedIcons" => ["width" => "256px","height" => "256px"],
        "svg" => ["width" => "256px","height" => "256px"],"imageAsset" => ["width" => "100%","height" => "100%"]];

    private $renderers = ["imageAsset" => ["classname" => 'oneplugin\onepluginpro\render\ImageRenderer', "class" => ImageRenderer::class],
                          "animatedIcons"  => ["classname" => 'oneplugin\onepluginpro\render\AnimatedIconRenderer', "class" => AnimatedIconRenderer::class],
                          "svg"  => ["classname" => 'oneplugin\onepluginpro\render\SVGIconRenderer', "class" => SVGIconRenderer::class]]; 

	public $output = '';
    public $json = '';
    public $iconData = null;

    public function __construct($value)
    {
        if($this->validateJson($value)){
        	$this->json = $value;
        	$this->iconData = (array)json_decode($value,true);
        } else {
            $value = null;
            $this->iconData = null;
        }
    }

    public function __toString()
    {
        return $this->output;
    }

    public function url()
    {
        if( $this->iconData && ($this->iconData['type'] == 'imageAsset')) {
            $asset = Craft::$app->getAssets()->getAssetById($this->iconData['id']);
            if( $asset ){
                return $asset->getUrl();
            }
        }
        return "";
    }

    public function type()
    {
        if( $this->iconData )
            return $this->iconData['type'];
        return "";
    }

    public function render(array $options = [])
    {
        $settings = OnePluginPro::$plugin->getSettings();
        $hash = 'op_' . $settings->opSettingsHash . '_' . $settings->opImageTag . '_' . $settings->aIconDataAsHtml . md5($this->json . json_encode($options));
        if( $settings->enableCache && Craft::$app->cache->exists($hash)) {
            $renderer = $this->createAssetRenderer();
            $renderer->includeAssets();
            return TemplateHelper::raw(\Craft::$app->cache->get($hash));
        }
        $cache = true;
        $renderer = $this->createAssetRenderer();
        if( $renderer != null){
            list($html,$cache) = $renderer->render($this,$options);
            if( $settings->enableCache && $cache ){
                Craft::$app->cache->set($hash, $html,86400);
            }
            return TemplateHelper::raw($html);;
        }
        return TemplateHelper::raw('<div>Renderer Exception </div>');
    }

    public function getThumbHtml(){
        
        if( $this->iconData )
        {
            if( ($this->iconData['type'] == 'imageAsset') && isset($this->iconData['id']) && $this->iconData['id'] != null){
                $asset = Craft::$app->getAssets()->getAssetById((int) $this->iconData['id']);        
                if( $asset )
                {
                    return TemplateHelper::raw( $asset->getPreviewThumbImg(34,34) );
                }
            }
        }
        return '';
    }

    public function getName() {
        return 'OnePluginPro';
    }

    public function getType() {
        return $this->iconData['type'];
    }

    public function getJsAssets() {
        $baseAssetsUrl = Craft::$app->assetManager->getPublishedUrl(
            '@oneplugin/onepluginpro/assetbundles/onepluginpro/dist',
            true
        );
        $jsFiles = [];

        if( $this->iconData['type'] == 'animatedIcons'){
            $jsFiles[] = $baseAssetsUrl . '/js/onepluginpro-lottie.min.js';
        }
        return $jsFiles;
    }
    
    public function getTag($args) {
        $opts = $args['options'] ?? [];
        $options = array();
        foreach ($opts as $opt) {
            foreach ($opt as $key => $value) {
                $options[$key] = $value;
            }
        }
        return $this->render($options);
    }

    public function getImage() {
        if( $this->iconData['type'] == 'imageAsset'){
            return new ImageGql($this->json);
        }
        return null;
    }

    public function getAnimatedIcon() {
        if( $this->iconData['type'] == 'animatedIcons'){
            return new AnimatedIconGql($this->json);
        }
        return null;
    }

    public function getSvgIcon() {
        if( $this->iconData['type'] == 'svg'){
            return new SVGIconGql($this->json);
        }
        return null;
    }

    public function getSrc()
    {
        switch( (string)$this->iconData['type'] ){
            case 'imageAsset':
                $imageAsset = Craft::$app->getAssets()->getAssetById($this->iconData['id']);
                if( $imageAsset ){
                    return $imageAsset->getUrl();
                }
                break;
            case 'animatedIcons':
                $url = UrlHelper::actionUrl('one-plugin-pro/one-plugin/load/',[ 'name' => $this->iconData['asset']['icon-name'],'type' => 'aicon','trigger'=>$this->iconData['asset']['icon-trigger'] ] );
                return $url;
            case 'svg':
                return '';
            }
        
    }

    private function createAssetRenderer(): RenderInterface
    {
        /** @var RenderInterface $renderer */
        $renderer = null;
        try {
            if( isset( $this->renderers[$this->iconData['type']] ) ){
                $renderer = Craft::createObject($this->renderers[$this->iconData['type']]["classname"]);
                if( $renderer instanceof $this->renderers[$this->iconData['type']]["class"]) {
                    return $renderer;
                }
            }
            $renderer = new BaseRenderer();
        } catch (\Throwable $e) {
            $renderer = new BaseRenderer();
            Craft::error($e->getMessage(), __METHOD__);
        }
        return $renderer;
    }

    private function normalizeOptions(array $options){

        if (empty($options['width'])){
            $options['width'] = $this->defaultSize[$this->iconData['type']]['width'];
        }
        if (empty($options['height'])){
            $options['height'] = $this->defaultSize[$this->iconData['type']]['height'];
        }

        return $options;
    }
    
    private function setAttribute($doc, $elem, $name, $value){
        
        $attribute = $doc->createAttribute($name);
        $attribute->value = htmlspecialchars($value);
        $elem->appendChild($attribute);
    }
    private function validateJson($value)
    {
        $json = json_decode($value);
        return $json && $value != $json;
    }
}
