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
use oneplugin\onepluginpro\OnePluginPro;
use oneplugin\onepluginpro\models\OnePluginProAsset;
use oneplugin\onepluginpro\models\OnePluginProOptimizedImage as OnePluginProOptimizedImageModel;
use oneplugin\onepluginpro\records\OnePluginProOptimizedImage as OnePluginProOptimizedImageRecord;

class ImageRenderer extends BaseRenderer
{


    public function render(OnePluginProAsset $asset, array $options): array{

        $settings = OnePluginPro::$plugin->getSettings();
        $attributes = $this->normalizeOptionsForSize($asset,$options);
        $html = '';
        $cache = false;
        try{
            if( $settings->opImageTag == 'img'){
                list($html, $cache) = $this->getImgObject($asset, $attributes);
            }
            else{
                list($html, $cache) = $this->getPictureObject($asset, $attributes);
            }
            return[$html, $cache];
        }
        catch (\Exception $exception) {
            Craft::info($exception->getMessage(), 'onepluginpro');
        }
        
        $renderer = new BaseRenderer();
        return $renderer->render($asset,$options);
    }
    
    private function getImgObject(OnePluginProAsset $asset, $attributes): array
    {
        $cache = false;
        $srcset = '';
        $optimizedImages = null;
        try{
            $assets = OnePluginProOptimizedImageRecord::find()->where(['assetId' => $asset->iconData['id']])->all();
            if( count($assets) > 0 && !empty($assets[0]['content'])){
                $optimizedImages = new OnePluginProOptimizedImageModel($assets[0]['content']);
                $srcset = $this->getSrcset($optimizedImages);
                $cache = true; //cache if srcset is available
            }
            else{
                if( isset($asset->iconData['id'])){
                    OnePluginPro::$plugin->onePluginProService->addImageOptimizeJob($asset->iconData['id'], true, false);
                }
            }
            $doc = new DOMDocument();
            $doc->formatOutput = true;
            $doc->preserveWhiteSpace = false;
            $image = $doc->createElement('img');
            $imageAsset = Craft::$app->getAssets()->getAssetById($asset->iconData['id']);
            if( $imageAsset ){
                $this->setAttribute($doc,$image,'src',$imageAsset->getUrl());
            }
            if( $attributes['size'] ){
                $this->setAttribute($doc,$image,'style','width:'. $attributes["width"] . ';height:' . $attributes["height"] . ';');
            }
            if( !empty($srcset)){
                $this->setAttribute($doc,$image,'srcset',$srcset);
            }
            empty($attributes['class']) ?:$this->setAttribute($doc,$image,'class',$attributes['class']);
            empty($asset->iconData['alt']) ? (empty($attributes['alt']) ? (empty($asset->iconData['name']) ?: $this->setAttribute($doc,$image,'alt',$asset->iconData['name'])) : $this->setAttribute($doc,$image,'alt',$attributes['alt'])) : $this->setAttribute($doc,$image,'alt',$asset->iconData['alt']);
            unset($attributes['alt']);
            return [$this->htmlFromDOMAfterAddingProperties($doc,$image,$attributes),$cache]; ;
        }
        catch (\Exception $exception) {
            Craft::info($exception->getMessage(), 'onepluginpro');
        }
        $renderer = new BaseRenderer();
        return $renderer->render($asset,$attributes);
    }

    private function getPictureObject(OnePluginProAsset $asset, $attributes): array
    {
        $cache = false;
        $srcset = '';
        $optimizedImages = null;
        try{
            $assets = OnePluginProOptimizedImageRecord::find()->where(['assetId' => $asset->iconData['id']])->all();
            if( count($assets) > 0 && !empty($assets[0]['content'])){
                $optimizedImages = new OnePluginProOptimizedImageModel($assets[0]['content']);
                $srcset = $this->getSrcset($optimizedImages);
                $cache = true; //cache if srcset is available
            }
            else{
                if( isset($asset->iconData['id'])){
                    OnePluginPro::$plugin->onePluginProService->addImageOptimizeJob($asset->iconData['id'], true, false);
                }
            }
            $doc = new DOMDocument();
            $doc->formatOutput = true;
            $doc->preserveWhiteSpace = false;
            $picture = $doc->createElement('picture');
            if( $attributes['size'] ){
                $this->setAttribute($doc,$picture,'style','width:'. $attributes["width"] . ';height:' . $attributes["height"] . ';');
            }

            if( !empty($srcset)){
                $source = $doc->createElement('source');
                $this->setAttribute($doc,$source,'srcset',$srcset);
                $this->setAttribute($doc,$source,'type','image/'.$optimizedImages->extension);
                $picture->appendChild($source);
            }

            if( $optimizedImages && $optimizedImages->extension == 'webp'){ //Set the fallback urls
                $srcset = $this->getFallbackSrcset($optimizedImages);
                if( !empty($srcset)){
                    $source = $doc->createElement('source');
                    $this->setAttribute($doc,$source,'srcset',$srcset);
                    $this->setAttribute($doc,$source,'type','image/jpeg');
                    $picture->appendChild($source);
                }
            }
            $image = $doc->createElement('img');
            $imageAsset = Craft::$app->getAssets()->getAssetById($asset->iconData['id']);
            if( $imageAsset ){
                $this->setAttribute($doc,$image,'src',$imageAsset->getUrl());
            }
            if( $attributes['size'] ){
                $this->setAttribute($doc,$image,'style','width:'. $attributes["width"] . ';height:' . $attributes["height"] . ';');
            }
            empty($attributes['class']) ?:$this->setAttribute($doc,$image,'class',$attributes['class']);
            $picture->appendChild($image);
            empty($attributes['class']) ?:$this->setAttribute($doc,$picture,'class',$attributes['class']);
            empty($asset->iconData['alt']) ? (empty($attributes['alt']) ? (empty($asset->iconData['name']) ?: $this->setAttribute($doc,$picture,'alt',$asset->iconData['name'])) : $this->setAttribute($doc,$picture,'alt',$attributes['alt'])) : $this->setAttribute($doc,$picture,'alt',$asset->iconData['alt']);
            unset($attributes['alt']);
            return [$this->htmlFromDOMAfterAddingProperties($doc,$picture,$attributes),$cache]; ;
        }
        catch (\Exception $exception) {
            Craft::info($exception->getMessage(), 'onepluginpro');
        }
        $renderer = new BaseRenderer();
        return $renderer->render($asset,$attributes);
    }

    private function getSrcset(OnePluginProOptimizedImageModel $optimizedImage): string
    {
        $srcset = '';
        foreach ($optimizedImage->imageUrls as $key => $value) {
            if( !empty($value['url']) ){
                $srcset .= $value['url'] . ' ' . $key . 'w, ';
            }
        }
        $srcset = rtrim($srcset, ', ');
        return $srcset;
    }

    private function getFallbackSrcset(OnePluginProOptimizedImageModel $optimizedImage): string
    {
        $srcset = '';
        foreach ($optimizedImage->fallbackImageUrls as $key => $value) {
            if( !empty($value['url']) ){
                $srcset .= $value['url'] . ' ' . $key . 'w, ';
            }
        }
        $srcset = rtrim($srcset, ', ');
        return $srcset;
    }
}
