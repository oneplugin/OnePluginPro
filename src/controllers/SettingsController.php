<?php

/**
 * OnePlugin Pro plugin for Craft CMS 3.x
 *
 * OnePlugin Pro lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginpro\controllers;

use Craft;

use yii\web\Response;
use craft\web\Controller;
use craft\web\assets\cp\CpAsset;

use oneplugin\onepluginpro\OnePluginPro;
use oneplugin\onepluginpro\helpers\StringHelper;
use oneplugin\onepluginpro\records\OnePluginProVersion;

class SettingsController extends Controller
{

    public $plugin;

    public function init():void
    {
        $this->plugin = OnePluginPro::$plugin;
        parent::init();
    }

    public function actionIndex(): Response
    {
        $this->requireAdmin();
        $settings = $this->plugin->getSettings();

        $baseAssetsUrl = Craft::$app->assetManager->getPublishedUrl(
            '@oneplugin/onepluginpro/assetbundles/onepluginpro/dist',
            true
        );
        
        Craft::$app->getView()->registerCssFile($baseAssetsUrl . '/css/onepluginpro.min.css');
        Craft::$app->getView()->registerJsFile($baseAssetsUrl . '/js/spectrum.min.js',['depends' => CpAsset::class]);

        return $this->renderTemplate('one-plugin-pro/settings/_general', array_merge(
                [
                    'plugin' => $this->plugin,
                    'settings' => $settings
                ],
                Craft::$app->getUrlManager()->getRouteParams())
        );
    }

    public function actionSync(): Response
    {
        $settings = $this->plugin->getSettings();
        $version = OnePluginProVersion::latest_version();
        $baseAssetsUrl = Craft::$app->assetManager->getPublishedUrl(
            '@oneplugin/onepluginpro/assetbundles/onepluginpro/dist',
            true
        );
        Craft::$app->getView()->registerJsFile($baseAssetsUrl . '/js/party.min.js',['depends' => CpAsset::class]);
        return $this->renderTemplate('one-plugin-pro/settings/_sync', array_merge(
                [
                    'plugin' => $this->plugin,
                    'settings' => $settings,
                    'version' => $version,
                    'formatted_version' => number_format((float)$version, 1, '.', '')
                ],
                Craft::$app->getUrlManager()->getRouteParams())
        );
    }

    public function actionSaveSettings()
    {
        $this->requirePostRequest();
        $postData = Craft::$app->request->post('settings', []);

        $plugin = OnePluginPro::getInstance();
        $plugin->setSettings($postData);
        $settings = $this->plugin->getSettings();
        
        if (Craft::$app->plugins->savePluginSettings($plugin, $postData)) {
            Craft::$app->session->setNotice(OnePluginPro::t('Settings Saved'));

            $opHash = $this->generateOpHash($postData);
            if( $opHash != $settings->opSettingsHash){
                $this->plugin->onePluginProService->addRegenerateAllImageOptimizeJob();
                Craft::$app->plugins->savePluginSettings($plugin, ['opSettingsHash'=>$opHash]);
            }
            return $this->redirectToPostedUrl();
        }

        $errors = $plugin->getSettings()->getErrors();
        Craft::$app->session->setError(
            implode("\n", StringHelper::flattenArrayValues($errors))
        );
    }

    public function actionCheckForUpdates()
    {
        $version = OnePluginProVersion::latest_version();
        $response = $this->plugin->onePluginProService->checkForUpdates($version);
        return $this->asJson($response);
    }

    public function actionDownloadFiles(){

        $version = OnePluginProVersion::latest_version();
        $response = $this->plugin->onePluginProService->checkForUpdates($version);
        return $this->asJson($this->plugin->onePluginProService->downloadLatestVersion($response));

    }

    private function generateOpHash($postData){

        if( empty($postData['opUpscale'])){
            $postData['opUpscale'] =  '0';
        }
        $source = $postData['opOutputFormat'] . $postData['opUpscale'] . $this->implode_all('x',$postData['opImageVariants']);
        return md5($source);
    }

    private function implode_all($glue, $arr){            
        for ($i=0; $i<count($arr); $i++) {
            if (@is_array($arr[$i])) 
                $arr[$i] = $this->implode_all ($glue, $arr[$i]);
        }            
        return implode($glue, $arr);
    }
}
