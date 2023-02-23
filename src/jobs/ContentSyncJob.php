<?php

/**
 * OnePlugin Pro plugin for Craft CMS 3.x
 *
 * OnePlugin Pro lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginpro\jobs;

use Craft;
use craft\queue\BaseJob;
use oneplugin\onepluginpro\OnePluginPro;
use oneplugin\onepluginpro\records\OnePluginProVersion;

class ContentSyncJob extends BaseJob
{

    public function execute($queue)
    {
        $settings = OnePluginPro::$plugin->getSettings();
        if( $settings->newContentPackAvailable ){
            $this->addJob();
            return;
        }
        $version = OnePluginProVersion::latest_version();
        $response = OnePluginPro::$plugin->onePluginProService->checkForUpdates($version);
        if( $response['updates'] ){
            Craft::$app->plugins->savePluginSettings(OnePluginPro::$plugin, ['newContentPackAvailable'=>true]);
        }
        $this->addJob();
    }

    private function addJob(){
        //This function adds a job for checking availability of new content after 24 hours.

        $queue = Craft::$app->getQueue();
        $jobId = $queue->priority(1024)
                        ->delay(6 * 60 * 60)
                        ->ttr(300)
                        ->push(new ContentSyncJob([
            'description' => Craft::t('one-plugin-pro', 'OnePlugin Pro - Job for checking availability of new content packs')
        ]));
    }
}
