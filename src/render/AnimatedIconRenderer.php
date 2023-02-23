<?php

/**
 * OnePlugin Pro plugin for Craft CMS 3.x
 *
 * OnePlugin Pro lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginpro\render;

use Craft;
use DOMDocument;
use craft\web\View;
use craft\helpers\UrlHelper;
use oneplugin\onepluginpro\OnePluginPro;
use oneplugin\onepluginpro\models\OnePluginProAsset;
use oneplugin\onepluginpro\records\OnePluginProAnimatedIcon;

class AnimatedIconRenderer extends BaseRenderer
{
    public function render(OnePluginProAsset $asset, array $options): array{
        
        $settings = OnePluginPro::$plugin->getSettings();
        $plugin = OnePluginPro::$plugin;
        $doc = new DOMDocument();
        $doc->formatOutput = true;
        $doc->preserveWhiteSpace = false;
        $html = '';
        $icon = '';
        $attributes = $this->normalizeOptionsForSize($asset,$options);
        try{
            $url = UrlHelper::actionUrl('one-plugin-pro/one-plugin/load/',[ 'name' => $asset->iconData['asset']['icon-name'],'type' => 'aicon','trigger'=>$asset->iconData['asset']['icon-trigger'] ] );
            $aIcon = $doc->createElement('one-plugin');
            empty($attributes['class']) ?:$this->setAttribute($doc,$aIcon,'class',$attributes['class']);
            if( $attributes['size'] ){
                $this->setAttribute($doc,$aIcon,'style','width:'. $attributes["width"] . ';height:' . $attributes["height"] . ';');
            }

            $this->setAttribute($doc,$aIcon,'stroke',$asset->iconData['asset']['icon-stroke-width']);
            $this->setAttribute($doc,$aIcon,'colors','primary:' . $asset->iconData['asset']['icon-primary'] . ',secondary:' . $asset->iconData['asset']['icon-secondary']);
            $this->setAttribute($doc,$aIcon,'trigger',$asset->iconData['asset']['icon-trigger']);

            $name = $asset->iconData['asset']['icon-name'];
            $trigger = $asset->iconData['asset']['icon-trigger'];
            $icon_name = $asset->iconData['asset']['icon-name'];
            $icon_name .= '_' . $trigger;
            if( $settings->aIconDataAsHtml ){ //Hidden in Settings now and value set to true
                $icons = OnePluginProAnimatedIcon::find()
                    ->where(['name' => $name])
                    ->all();
                if( count($icons) > 0 ){
                    if( !empty($trigger) && ($trigger == 'morph' || $trigger == 'morph-two-way') ){
                        $icon = $icons[0]['data_morph'];
                    }
                    else{
                        $icon = $icons[0]['data_loop'];
                    }
                }
                $this->setAttribute($doc,$aIcon,'icon',$icon_name);
                if( !empty($icon) ){
                    $aIcon->appendChild($doc->createCDATASection('<data style="display:none">' . $icon . '</data>'));
                }
                else{
                    $this->setAttribute($doc,$aIcon,'src',$url); //fallback, in case :)
                }
            }
            else{
                $this->setAttribute($doc,$aIcon,'src',$url);
            }
            $this->includeAssets();
            return [$this->htmlFromDOMAfterAddingProperties($doc,$aIcon,$attributes), true];
        }
        catch (\Exception $exception) {
            Craft::info($exception->getMessage(), 'onepluginpro');
        }
        $renderer = new BaseRenderer();
        return $renderer->render($asset,$options);
    }

    public function includeAssets()
    {
        $folder = 'dist';
         if( OnePluginPro::$devMode ){
            $folder = 'src';
        }
        $baseAssetsUrl = Craft::$app->assetManager->getPublishedUrl(
            '@oneplugin/onepluginpro/assetbundles/onepluginpro/' . $folder,
            true
        );

        $jsFiles = [];
        $jsFiles[] = $baseAssetsUrl . '/js/jquery.min.js';

        if( OnePluginPro::$devMode ){
            $jsFiles = array_merge($jsFiles,[ $baseAssetsUrl . '/js/icons/lottie_svg.js',$baseAssetsUrl . '/js/icons/onepluginpro-lottie.js']);
        }
        else{
            $jsFiles = array_merge($jsFiles,[ $baseAssetsUrl . '/js/onepluginpro-lottie.min.js']);
        }
        foreach ($jsFiles as $jsFile) {
            Craft::$app->getView()->registerJsFile($jsFile,['position' => View::POS_END,'defer' => true],hash('ripemd160',$jsFile) );
        }
    }
}

