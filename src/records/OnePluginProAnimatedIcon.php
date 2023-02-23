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

class OnePluginProAnimatedIcon extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%onepluginpro_animated_icon}}';
    }
}
