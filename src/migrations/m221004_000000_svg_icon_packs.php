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

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;

class m221004_000000_svg_icon_packs extends Migration
{
    
    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%onepluginpro_svg_icon_packs}}')) {
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

            $this->createIndex(null, '{{%onepluginpro_svg_icon_packs}}', 'category', false);
            $this->addForeignKey(null, '{{%onepluginpro_svg_icon_packs}}', ['category'], '{{%onepluginpro_category}}', ['id'], 'CASCADE', null);
        }

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%onepluginpro_svg_icon_packs}}');
        return true;
    }
}
