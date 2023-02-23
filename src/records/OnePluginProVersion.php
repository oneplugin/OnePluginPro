<?php

/**
 * OnePlugin Pro plugin for Craft CMS 3.x
 *
 * OnePlugin Pro lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginpro\records;

use craft\db\ActiveRecord;

class OnePluginProVersion extends ActiveRecord
{

    public static function tableName()
    {
        return '{{%onepluginpro_config}}';
    }

    public static function latest_version()
    {
        $version = OnePluginProVersion::find()
                ->where(['id' => 1])->limit(1)
                ->all();
        if (count($version) > 0 ) {
            return $version[0]['content_version_number'];
        }
        else{
            return '1.0';
        }
    }
}
