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

use oneplugin\onepluginpro\models\OnePluginProAsset;

interface RenderInterface
{
    /**
     * Return an HTML string for the corresponding content type
     *
     * @param OnePluginProAsset              $asset
     * @param array               $options
     *
     * @return string
     */
    public function render(OnePluginProAsset $asset, array $options): array;
}
