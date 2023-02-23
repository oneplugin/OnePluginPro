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
use craft\db\Paginator;
use craft\web\Controller;

use oneplugin\onepluginpro\OnePluginPro;
use oneplugin\onepluginpro\records\OnePluginProSVGIcon;
use oneplugin\onepluginpro\records\OnePluginProCategory;
use oneplugin\onepluginpro\records\OnePluginProAnimatedIcon;
use oneplugin\onepluginpro\records\OnePluginProOptimizedImage;
use oneplugin\onepluginpro\models\OnePluginProOptimizedImage as OnePluginProOptimizedImageModel;

class OnePluginController extends Controller
{

    public $plugin;
    protected array|bool|int $allowAnonymous = true;
    const QUERY_PAGE_SIZE = 30;

    public function init():void
    {
        $this->plugin = OnePluginPro::$plugin;
        parent::init();
    }
    public function actionIndex()
    {

        $url = "one-plugin-pro/settings";
        return $this->redirect($url);

    }

    public function actionShow(): Response
    {
        $this->requirePostRequest();
        $settings = $this->plugin->getSettings();
        return $this->renderTemplate('one-plugin-pro/icon_selector/index', array_merge(
                [
                    'plugin' => $this->plugin,
                    'settings' => $settings,
                ],
                Craft::$app->getUrlManager()->getRouteParams())
        );
    }

    public function actionOptimizeDialog(): Response
    {
        $this->requirePostRequest();
        $assetId = Craft::$app->getRequest()->getBodyParam('assetId');
        $userSession = Craft::$app->getUser();
        $asset = Craft::$app->getAssets()->getAssetById($assetId);
        $settings = $this->plugin->getSettings();
        if( $asset ){
            $assets = OnePluginProOptimizedImage::find()->where(['assetId' => $assetId] )->all();
            $previewable = Craft::$app->getAssets()->getAssetPreviewHandler($asset) !== null;
            if( count($assets) > 0 ){
                if( !empty($assets[0]['content'] ) ){
                    $optimizedImage = new OnePluginProOptimizedImageModel($assets[0]['content']);
                    return $this->renderTemplate('one-plugin-pro/image_optimize/index', array_merge(
                        [
                            'plugin' => $this->plugin,
                            'settings' => $settings,
                            'derivations' => $optimizedImage,
                            'asset' => $asset,
                            'previewable' => $previewable
                        ],
                        Craft::$app->getUrlManager()->getRouteParams())
                    );
                }
            }
            else{
                $this->plugin->onePluginProService->addImageOptimizeJob($assetId, true, false);
            }
        }
        return $this->renderTemplate('one-plugin-pro/image_optimize/processing', array_merge(
            [
                'plugin' => $this->plugin,
                'settings' => $settings,
                'asset' => $asset
            ],
            Craft::$app->getUrlManager()->getRouteParams())
        );
    }

    public function actionCategories()
    {
        $categories = OnePluginProCategory::find()
            ->orderBy(['name' => SORT_ASC])
            ->where(['type' => 'aicon'])
            ->all();
        $json = array();
        $json[] = array("id" => "0","text" => 'Animated Icons','parent'=>'#');
        $json[] = array("id" => "latest","text" => "Latest Release","parent" => 0);
        foreach($categories as $category){
            $parent = '0';
            if(!empty($category->parent_id)){
                $parent = $category->parent_id;
            }
            $json[] = array("id" => strval($category->id),"text" => $category->name . '(' . $category->count . ')',"parent" => $parent);
        }
        //return $this->asJson(['success' => true, 'categories' => $categories]);
        return json_encode($json);
    }

    public function actionSvgCategories()
    {
        $categories = OnePluginProCategory::find()
            ->orderBy(['name' => SORT_ASC])
            ->where(['type' => 'svg'])
            ->all();
        $json = array();
        $json[] = array("id" => "0","text" => 'SVG Icons','parent'=>'#');
        $json[] = array("id" => "latest","text" => "Latest Release","parent" => 0);
        foreach($categories as $category){
            $parent = '0';
            if(!empty($category->parent_id)){
                $parent = $category->parent_id;
            }
            $json[] = array("id" => strval($category->id),"text" => $category->name . '(' . $category->count . ')',"parent" => $parent);
        }
        //return $this->asJson(['success' => true, 'categories' => $categories]);
        return json_encode($json);
    }

    public function actionLoad($name,$type,$trigger)
    {
        $settings = OnePluginPro::$plugin->getSettings();
        $hash = 'op_' . md5($name . $type . $trigger);
        if( $settings->enableCache && Craft::$app->cache->exists($hash)) {
            return \Craft::$app->cache->get($hash);
        }

        if( $type == 'aicon'){
            $icons = OnePluginProAnimatedIcon::find()
                ->where(['name' => $name])
                ->all();
            if( count($icons) > 0 ){
                if( !empty($trigger) && ($trigger == 'morph' || $trigger == 'morph-two-way') ){
                    if( $settings->enableCache ){
                        Craft::$app->cache->set($hash, $icons[0]['data_morph'],86400);
                    }
                    return $icons[0]['data_morph'];
                }
                else{
                    if( $settings->enableCache ){
                        Craft::$app->cache->set($hash, $icons[0]['data_loop'],86400);
                    }
                    return $icons[0]['data_loop'];
                }
            }
            else
                return $this->asJson([]); //TODO Send dummy Animated Icon if not found

        }
        return $this->asJson([]); //TODO Send dummy Animated Icon if not found
    }

    public function actionIconsByCategory($id = null, $type = 'aicon',$filter = 'all',$pageNum = 0)
    {
        if( $type == 'aicon'){
            $icons = [];
            $query = null;
            if( $id == 'latest'){
                if( $filter == 'all'){
                    $query = OnePluginProAnimatedIcon::find()
                    ->limit(100)
                    ->orderBy(['dateUpdated' => SORT_DESC]);
                }
                else if( $filter == 'loop'){
                    $query = OnePluginProAnimatedIcon::find()
                        ->where(['aloop' => true])
                        ->limit(100)
                        ->orderBy(['dateUpdated' => SORT_DESC]);
                }
                else if( $filter == 'morph'){
                    $query = OnePluginProAnimatedIcon::find()
                        ->where(['amorph' => true])
                        ->limit(100)
                        ->orderBy(['dateUpdated' => SORT_DESC]);
                }
                else if( $filter == 'none'){
                    return $this->asJson(['success' => true, 'data' => []]);
                }
            }
            else{
                if( $filter == 'all'){
                    $query = OnePluginProAnimatedIcon::find()
                        ->where(['category' => $id])
                        ->orderBy(['title' => SORT_ASC]);
                }
                else if( $filter == 'loop'){
                    $query = OnePluginProAnimatedIcon::find()
                        ->where(['category' => $id])->andWhere(['aloop' => true])
                        ->orderBy(['title' => SORT_ASC]);
                }
                else if( $filter == 'morph'){
                    $query = OnePluginProAnimatedIcon::find()
                        ->where(['category' => $id])->andWhere(['amorph' => true])
                        ->orderBy(['title' => SORT_ASC]);
                }
                else if( $filter == 'none'){
                    return $this->asJson(['success' => true, 'data' => []]);
                }
            }
            $pages = new Paginator($query,[
                'pageSize' => self::QUERY_PAGE_SIZE,
                'currentPage' => $pageNum,
            ]);
            $pageResults = $pages->getPageResults();
            $result = [];
            $result['data'] = $pageResults;
            $result['total'] = $pages->totalResults;
            $result['pages'] = $pages->totalPages;
            $result['currentPage'] = $pages->currentPage;
            return $this->asJson($result);
        }
        else if( $type == 'svg'){
            $query = null;
            if( $id == 'latest'){
                $query = OnePluginProSVGIcon::find()
                ->limit(100)
                ->orderBy(['dateUpdated' => SORT_DESC]);
            }
            else{
                $query = OnePluginProSVGIcon::find()
                ->where(['category' => $id])
                ->orderBy(['id' => SORT_ASC]);
            }
            

            $pages = new Paginator($query,[
                'pageSize' => self::QUERY_PAGE_SIZE,
                'currentPage' => $pageNum,
            ]);
            $pageResults = $pages->getPageResults();
            $result = [];
            $result['data'] = $pageResults;
            $result['total'] = $pages->totalResults;
            $result['pages'] = $pages->totalPages;
            $result['currentPage'] = $pages->currentPage;
            return $this->asJson($result);
        }
        return $this->asJson(['success' => true, 'data' => []]);
    }

    public function actionSearchIconsSvg($text = null,$pageNum)
    {
        $query = OnePluginProSVGIcon::find()
            ->where(['like','tags','%' . $text . '%', false])
            ->orderBy(['title' => SORT_ASC]);
        $pages = new Paginator($query,[
            'pageSize' => self::QUERY_PAGE_SIZE,
            'currentPage' => $pageNum,
        ]);
        $pageResults = $pages->getPageResults();
        $result = [];
        $result['data'] = $pageResults;
        $result['total'] = $pages->totalResults;
        $result['pages'] = $pages->totalPages;
        $result['currentPage'] = $pages->currentPage;
        return  $this->asJson($result);
    }

    public function actionSearchIconsAicon($text = null,$filter,$pageNum)
    {
        $query = null;
        if( $filter == 'all'){
            $query = OnePluginProAnimatedIcon::find()
                ->where(['like','tags','%' . $text . '%', false])
                ->orderBy(['title' => SORT_ASC]);
        }
        else if( $filter == 'loop'){
            $query = OnePluginProAnimatedIcon::find()
            ->where(['like','tags','%' . $text . '%', false])->andWhere(['aloop' => true])
                ->orderBy(['title' => SORT_ASC]);
        }
        else if( $filter == 'morph'){
            $query = OnePluginProAnimatedIcon::find()
            ->where(['like','tags','%' . $text . '%', false])->andWhere(['amorph' => true])
                ->orderBy(['title' => SORT_ASC]);
        }
        else if( $filter == 'none'){
            return $this->asJson(['success' => true, 'data' => []]);
        }
        $pages = new Paginator($query,[
            'pageSize' => self::QUERY_PAGE_SIZE,
            'currentPage' => $pageNum,
        ]);

        $pageResults = $pages->getPageResults();
        $result = [];
        $result['data'] = $pageResults;
        $result['total'] = $pages->totalResults;
        $result['pages'] = $pages->totalPages;
        $result['currentPage'] = $pages->currentPage;
        return  $this->asJson($result);
    }

    public function actionCreateOptimizedImage(){

        $this->requirePostRequest();
        $assetId = Craft::$app->getRequest()->getBodyParam('assetId');
        $force = Craft::$app->getRequest()->getBodyParam('force');

        $this->plugin->onePluginProService->addImageOptimizeJob($assetId, $force, false);
        return $this->asJson(['success' => true]);
    }

    public function actionCheckAsset($assetId){

        $assets = OnePluginProOptimizedImage::find()->where(['assetId'=>$assetId])->all();
        if( count($assets) > 0 ){
            if( !empty($assets[0]['content']) ){
                return $this->asJson(['result' => true]);
            }
        }
        
        return $this->asJson(['result' => false]);
    }
    
}
