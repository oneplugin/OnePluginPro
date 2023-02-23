<?php

/**
 * OnePlugin Pro plugin for Craft CMS 3.x
 *
 * OnePlugin Pro lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginpro\services;

use Craft;
use craft\helpers\App;
use GuzzleHttp\Client;
use craft\base\Component;
use oneplugin\onepluginpro\OnePluginPro;
use oneplugin\onepluginpro\jobs\OptimizeImageJob;
use oneplugin\onepluginpro\records\OnePluginProSVGIcon;
use oneplugin\onepluginpro\records\OnePluginProVersion;
use oneplugin\onepluginpro\records\OnePluginProCategory;
use oneplugin\onepluginpro\records\OnePluginProAnimatedIcon;
use oneplugin\onepluginpro\records\OnePluginProOptimizedImage;
use oneplugin\onepluginpro\records\OnePluginProOptimizedImage as OnePluginProOptimizedImageRecord;


class OnePluginProService extends Component
{
    const SERVER_URL = 'https://dev.oneplugin.co';

    // Public Methods
    // =========================================================================
    
    public function addRegenerateAllImageOptimizeJob(){

        $queue = Craft::$app->getQueue();
        $assets = OnePluginProOptimizedImage::find()->all();
        foreach($assets as $asset){
            Craft::$app->db->createCommand()
            ->upsert(OnePluginProOptimizedImage::tableName(), [
                'content' => '',
                'assetId' => $asset->assetId
            ], true, [], true)
            ->execute();

            $jobId = $queue->push(new OptimizeImageJob([
                'description' => Craft::t('one-plugin-pro', 'OnePlugin Pro - Job for optimizing image with id {id}', ['id' => $asset->assetId]),
                'assetId' => $asset->assetId,
                'force' => true
            ]));
        }
    }

    public function addImageOptimizeJob($assetId, $force,$runQueue = false){

        //TODO - check whether same job exists
        $assets = OnePluginProOptimizedImageRecord::find()->where(['assetId' => $assetId])->all();
        
        if($force){ //Make sure the content is cleared
            Craft::$app->db->createCommand()
                    ->upsert(OnePluginProOptimizedImage::tableName(), [
                        'content' => '',
                        'assetId' => $assetId
                    ], true, [], true)
                    ->execute();
        }

        $queue = Craft::$app->getQueue();
        $jobId = $queue->push(new OptimizeImageJob([
            'description' => Craft::t('one-plugin-pro', 'OnePlugin Pro - Job for optimizing image with id {id}', ['id' => $assetId]),
            'assetId' => $assetId,
            'force' => $force
        ]));

        if($runQueue){
            App::maxPowerCaptain();
            Craft::$app->getQueue()->run();
        }
    }

    public function checkForUpdates( $current_version)
    {
        $client = new Client();

        $response = $client->request('GET', self::SERVER_URL . '/api/update/' . $current_version);
        $response = json_decode($response->getBody(), true);
        return $response;
    }

    public function downloadLatestVersion( $json)
    {
        $client = new Client();

        $response = $client->request('GET', self::SERVER_URL . $json['json_path']);
        $response = json_decode($response->getBody(), true);
        $latest_version = '1.0';
        
        foreach ($response as $version => $value) {
            $latest_version = $version;
            $categories = $value['categories'];
            $svgIcons = $value['svg'];
            $animatedIcons = $value['animatedicon'];

            foreach ($categories as $category) {
                $type = 'svg';
                if($category['type'] == 'ANIMATEDICON'){
                    $type = 'aicon';
                }
                $parent_id = 0;
                if( !empty($category['parent_id'])){
                    $parent_id = $category['parent_id'];
                }
                $cat = OnePluginProCategory::find()->where(['id' => $category['id']] )->all();
                if( count($cat) > 0 ){
                    $command = Craft::$app->getDb()->createCommand()->update(OnePluginProCategory::tableName(), [
                        'id' => $category['id'],
                        'name' => $category['name'],
                        'type' => $type,
                        'count' => 0,
                        'parent_id' => $parent_id,
                    ],'id=' . $category['id']);
                    $command->execute();
                }
                else{
                    $command = Craft::$app->getDb()->createCommand()->insert(OnePluginProCategory::tableName(), [
                        'id' => $category['id'],
                        'name' => $category['name'],
                        'type' => $type,
                        'count' => 0,
                        'parent_id' => $parent_id,
                    ]);
                    $command->execute();
                }
            }

            foreach ($svgIcons as $svgIcon) {
                $svgs = OnePluginProSVGIcon::find()->where(['name' => $svgIcon['fname']] )->all();
                $tags = '';
                if( isset($svgIcon['tags']) )
                    $tags = $svgIcon['tags'];
                if( count($svgs) > 0 ){
                    $command = Craft::$app->getDb()->createCommand()->update(OnePluginProSVGIcon::tableName(), [
                        'category' => $svgIcon['cid'],
                        'name' => $svgIcon['fname'],
                        'title' => $svgIcon['name'],
                        'description' => ' ',
                        'data' => $svgIcon['data'],
                        'tags' => $tags
                    ],'name = \'' . $svgIcon['fname'] . '\'');
                    $command->execute();
                }
                else {
                    $command = Craft::$app->getDb()->createCommand()->insert(OnePluginProSVGIcon::tableName(), [
                        'category' => $svgIcon['cid'],
                        'name' => $svgIcon['fname'],
                        'title' => $svgIcon['name'],
                        'description' => ' ',
                        'data' => $svgIcon['data'],
                        'tags' => $tags
                    ]);
                    $command->execute();
                }
            }

            foreach ($animatedIcons as $animatedIcon) {
                $data_loop = !empty($animatedIcon['data-loop'])?$animatedIcon['data-loop']:'';
                $data_morph = !empty($animatedIcon['data-morph'])?$animatedIcon['data-morph']:'';

                $aicons = OnePluginProAnimatedIcon::find()->where(['name' => $animatedIcon['fname']] )->all();
                $tags = '';
                if( isset($animatedIcon['tags']) )
                    $tags = $animatedIcon['tags'];
                if( count($aicons) > 0 ){
                    $command = Craft::$app->getDb()->createCommand()->update(OnePluginProAnimatedIcon::tableName(), [
                        'category' => $animatedIcon['cid'],
                        'name' => $animatedIcon['fname'],
                        'title' => $animatedIcon['name'],
                        'description' => ' ',
                        'data_loop' => $data_loop,
                        'data_morph' => $data_morph,
                        'tags' => $tags
                    ],'name = \'' . $animatedIcon['fname'] . '\'');
                    $command->execute();
                }
                else{
                    $command = Craft::$app->getDb()->createCommand()->insert(OnePluginProAnimatedIcon::tableName(), [
                        'category' => $animatedIcon['cid'],
                        'name' => $animatedIcon['fname'],
                        'title' => $animatedIcon['name'],
                        'description' => ' ',
                        'data_loop' => $data_loop,
                        'data_morph' => $data_morph,
                        'tags' => $tags
                    ]);
                    $command->execute();
                }
            }

            Craft::$app->getDb()->createCommand("update onepluginpro_category set count = (select count(id) from onepluginpro_svg_icon where onepluginpro_svg_icon.category = onepluginpro_category.id) where onepluginpro_category.type = 'svg'")->execute();
            Craft::$app->getDb()->createCommand("update onepluginpro_category set count = (select count(id) from onepluginpro_animated_icon where onepluginpro_animated_icon.category = onepluginpro_category.id) where onepluginpro_category.type = 'aicon'")->execute();
            Craft::$app->getDb()->createCommand("update onepluginpro_animated_icon set onepluginpro_animated_icon.aloop = true where TRIM(data_loop) <> ''")->execute();
            Craft::$app->getDb()->createCommand("update onepluginpro_animated_icon set onepluginpro_animated_icon.amorph = true where TRIM(data_morph) <> ''")->execute();
            Craft::$app->plugins->savePluginSettings(OnePluginPro::$plugin, ['newContentPackAvailable'=>false]);
        }

        $command = Craft::$app->getDb()->createCommand()->update(OnePluginProVersion::tableName(), [
            'content_version_number' => $latest_version
        ]);
        $command->execute();
        return @['success' => true];
    }
    
}
