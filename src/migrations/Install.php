<?php

/**
 * OnePlugin Pro plugin for Craft CMS 3.x
 *
 * OnePlugin Pro lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginpro\migrations;

use Craft;

use craft\db\Migration;
use craft\helpers\Json;
use oneplugin\onepluginpro\OnePluginPro;
use oneplugin\onepluginpro\records\OnePluginProSVGIcon;
use oneplugin\onepluginpro\records\OnePluginProVersion;
use oneplugin\onepluginpro\records\OnePluginProCategory;

use oneplugin\onepluginpro\records\OnePluginProAnimatedIcon;

class Install extends Migration
{
    public $driver;

    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }

        return true;
    }

    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    protected function createTables()
    {
        $this->dropTableIfExists('{{%onepluginpro_config}}');
        $this->createTable('{{%onepluginpro_config}}', [
            'id' => $this->primaryKey(),
            'content_version_number' => $this->string(256)->notNull(),
            'uid' => $this->uid(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
        ]);
        $this->dropTableIfExists('{{%onepluginpro_category}}');
        $this->createTable('{{%onepluginpro_category}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(256)->notNull(),
            'type' => $this->string(256)->notNull(),
            'parent_id' => $this->integer(),
            'count' => $this->integer(),
            'uid' => $this->uid(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull()
        ]);
        $this->dropTableIfExists('{{%onepluginpro_svg_icon}}');
        $this->createTable('{{%onepluginpro_svg_icon}}', [
            'id' => $this->primaryKey(),
            'category' => $this->integer()->notNull(),
            'name' => $this->string(256)->notNull(),
            'title' => $this->string(256)->notNull(),
            'description' => $this->text(),
            'data' => $this->mediumText(),
            'tags' => $this->mediumText(),
            'uid' => $this->uid(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull()
        ]);
        $this->dropTableIfExists('{{%onepluginpro_animated_icon}}');
        $this->createTable('{{%onepluginpro_animated_icon}}', [
            'id' => $this->primaryKey(),
            'category' => $this->integer()->notNull(),
            'name' => $this->string(256)->notNull(),
            'title' => $this->string(256)->notNull(),
            'description' => $this->text(),
            'data_loop' => $this->mediumText(),
            'data_morph' => $this->mediumText(),
            'tags' => $this->mediumText(),
            'aloop' => $this->boolean()->defaultValue(false),
            'amorph' => $this->boolean()->defaultValue(false),
            'uid' => $this->uid(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull()
        ]);
        $this->dropTableIfExists('{{%onepluginpro_optimized_image}}');
        $this->createTable('{{%onepluginpro_optimized_image}}', [
            'id' => $this->primaryKey(),
            'assetId' => $this->integer()->notNull(),
            'content' => $this->mediumText(),
            'uid' => $this->uid(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull()
        ]);

        $this->dropTableIfExists('{{%onepluginpro_svg_icon_packs}}');
        $this->createTable('{{%onepluginpro_svg_icon_packs}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(),
            'handle' => $this->string(),
            'category' => $this->integer()->notNull(),
            'count' => $this->string()->notNull(),
            'dateArchived' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        return true;
    }

    protected function createIndexes()
    {
        $this->createIndex(null, '{{%onepluginpro_config}}', 'id', true);
        $this->createIndex(null, '{{%onepluginpro_svg_icon}}', 'id', true);
        $this->createIndex(null, '{{%onepluginpro_animated_icon}}', 'id', true);
        $this->createIndex(null, '{{%onepluginpro_category}}', 'id', true);
        $this->createIndex(null, '{{%onepluginpro_optimized_image}}', 'id', true);
        $this->createIndex(null, '{{%onepluginpro_optimized_image}}', 'assetId', true);
        $this->createIndex(null, '{{%onepluginpro_svg_icon_packs}}', 'category', false);
        
    }

    protected function addForeignKeys()
    {
        $this->addForeignKey(null, '{{%onepluginpro_animated_icon}}', ['category'], '{{%onepluginpro_category}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%onepluginpro_svg_icon}}', ['category'], '{{%onepluginpro_category}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%onepluginpro_svg_icon_packs}}', ['category'], '{{%onepluginpro_category}}', ['id'], 'CASCADE', null);
    }

    protected function insertDefaultData()
    {
        $command = $this->db->createCommand()->insert(OnePluginProVersion::tableName(), [
            'content_version_number' => '1.0'
        ]);
        $command->execute();

        $dir = OnePluginPro::getInstance()->getBasePath();
        $path = $dir . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'data.json';
        $data = Json::decode(file_get_contents($path));
        $latest_version = '';
        foreach ($data as $version => $value) {
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
                $command = $this->db->createCommand()->insert(OnePluginProCategory::tableName(), [
                    'id' => $category['id'],
                    'name' => $category['name'],
                    'type' => $type,
                    'count' => 0,
                    'parent_id' => $parent_id,
                ]);
                $command->execute();
            }

            foreach ($svgIcons as $svgIcon) {
                $command = $this->db->createCommand()->insert(OnePluginProSVGIcon::tableName(), [
                    'category' => $svgIcon['cid'],
                    'name' => $svgIcon['fname'],
                    'title' => $svgIcon['name'],
                    'description' => ' ',
                    'data' => $svgIcon['data'],
                    'tags' => $svgIcon['tags']
                ]);
                $command->execute();
            }

            foreach ($animatedIcons as $animatedIcon) {
                $data_loop = !empty($animatedIcon['data-loop'])?$animatedIcon['data-loop']:'';
                $data_morph = !empty($animatedIcon['data-morph'])?$animatedIcon['data-morph']:'';

                $command = $this->db->createCommand()->insert(OnePluginProAnimatedIcon::tableName(), [
                    'category' => $animatedIcon['cid'],
                    'name' => $animatedIcon['fname'],
                    'title' => $animatedIcon['name'],
                    'description' => ' ',
                    'data_loop' => $data_loop,
                    'data_morph' => $data_morph,
                    'tags' => $animatedIcon['tags']
                ]);
                $command->execute();
            }
        }

        $command = $this->db->createCommand()->update(OnePluginProVersion::tableName(), [
            'content_version_number' => $latest_version
        ]);
        $command->execute();

        $this->db->createCommand("update onepluginpro_category set count = (select count(id) from onepluginpro_svg_icon where onepluginpro_svg_icon.category = onepluginpro_category.id) where onepluginpro_category.type = 'svg'")->execute();
        $this->db->createCommand("update onepluginpro_category set count = (select count(id) from onepluginpro_animated_icon where onepluginpro_animated_icon.category = onepluginpro_category.id) where onepluginpro_category.type = 'aicon'")->execute();
        $this->db->createCommand("update onepluginpro_animated_icon set onepluginpro_animated_icon.aloop = true where TRIM(data_loop) <> ''")->execute();
        $this->db->createCommand("update onepluginpro_animated_icon set onepluginpro_animated_icon.amorph = true where TRIM(data_morph) <> ''")->execute();
    }

    protected function removeTables()
    {
        $this->dropTableIfExists('{{%onepluginpro_config}}');
        $this->dropTableIfExists('{{%onepluginpro_svg_icon_packs}}');
        $this->dropTableIfExists('{{%onepluginpro_animated_icon}}');
        $this->dropTableIfExists('{{%onepluginpro_svg_icon}}');
        $this->dropTableIfExists('{{%onepluginpro_optimized_image}}');
        $this->dropTableIfExists('{{%onepluginpro_category}}');
        $this->db->createCommand("delete from " . Craft::$app->getQueue()->tableName . " where description like 'OnePlugin Pro%' ")->execute();
    }
}
