<?php

/**
 * OnePlugin Pro plugin for Craft CMS 3.x
 *
 * OnePlugin Pro lets the Craft community embed rich contents on their website
 *
 * @link      https://github.com/oneplugin
 * @copyright Copyright (c) 2022 The OnePlugin Team
 */

namespace oneplugin\onepluginpro\elements;

use Craft;
use Exception;
use craft\base\Element;
use craft\elements\User;
use craft\helpers\UrlHelper;
use craft\helpers\StringHelper;
use craft\elements\actions\Delete;
use craft\elements\actions\Restore;
use oneplugin\onepluginpro\OnePluginPro;
use craft\elements\db\ElementQueryInterface;
use oneplugin\onepluginpro\records\OnePluginProSVGIcon;
use oneplugin\onepluginpro\elements\db\SVGIconPackQuery;
use oneplugin\onepluginpro\records\OnePluginProCategory;
use oneplugin\onepluginpro\records\OnePluginProSVGIconPack;
/**
 * @author    ThePluginFactory
 * @package   TpfGallery
 * @since     1.0.0
 */
class SVGIconPack extends Element
{
    // Public Properties
    // =========================================================================
    public $settings;

    public $name;

    public $handle;

    public $description;

    public $category;

    public $count;

    public $icons;
        // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('one-plugin-pro', 'SVG Icon Pack');
    }

    /**
     * @return string
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('one-plugin-pro', 'SVG Icon Pack');
    }

    /**
     * @inheritdoc
     */
    public static function lowerDisplayName(): string
    {
        return StringHelper::toLowerCase(static::displayName());
    }

    /**
     * @inheritdoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return StringHelper::toLowerCase(static::pluralDisplayName());
    }

    /**
     * @inheritdoc
     */
    public static function refHandle():null|string
    {
        return 'svgicon';
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function find(): ElementQueryInterface
    {
        return new SVGIconPackQuery(static::class);
    } 

    public function canDelete(User $user): bool
    {
        return true;
    }

    public function canView(User $user): bool
    {
        return true;
    }

    public function canSave(User $user): bool
    {
        return true;
    }
    public function init(): void
    {
        parent::init();

        if (empty($this->settings)) {
            $this->settings = OnePluginPro::$plugin->getSettings();
        }
        $this->icons = [];
        $this->count = 0;
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['title'], 'required'];
        $rules[] = [['title'], 'string', 'max' => 255];
        $rules[] = [['handle'], 'string', 'max' => 60];

        return $rules;
    }

    public static function defineSources(string $context = null): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => 'All SVG Icon Packs',
                'defaultSort' => ['title', 'desc'],
                'criteria' => []
            ]
        ];

        return $sources;
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'name' => ['label' => Craft::t('app', 'Title')],
            'dateUpdated' => ['label' =>Craft::t('app', 'Date Updated')],
        ];
    }

    /**
     * @inheritDoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = [];
        $attributes[] = 'name';
        $attributes[] = 'dateUpdated';

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['name', 'handle'];
    }

    /**
     * @inheritDoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            'name' => Craft::t('app', 'Title'),
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated'
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated'
            ],
            [
                'label' => Craft::t('app', 'ID'),
                'orderBy' => 'elements.id',
                'attribute' => 'id',
            ],
        ];
    }

    protected function tableAttributeHtml(string $attribute): string
    {
        return parent::tableAttributeHtml($attribute);
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source = null): array
    {
        $elementsService = Craft::$app->getElements();

        $actions = parent::defineActions($source);

        $actions[] = $elementsService->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('one-plugin-pro', 'Are you sure you want to delete the selected SVG Icon Pack?'),
            'successMessage' => Craft::t('one-plugin-pro', 'SVG Icon Pack deleted.'),
        ]);

        $actions[] = Craft::$app->elements->createAction([
            'type' => Restore::class,
            'successMessage' => Craft::t('one-plugin-pro', 'SVG Icon Pack restored.'),
            'partialSuccessMessage' => Craft::t('one-plugin-pro', 'Some SVG Icon Packs are restored.'),
            'failMessage' => Craft::t('one-plugin-pro', 'SVG Icon Pack not restored.'),
        ]);

        return $actions;
    }

    public function afterSave(bool $isNew): void
    {
        $record = null;
        if (!$isNew) {
            $record = OnePluginProSVGIconPack::findOne($this->id);
            if (!$record) {
                throw new Exception('Invalid Icon Pack ID: '.$this->id);
            }
        }
        else{
            $record = new OnePluginProSVGIconPack();
            $record->id = $this->id;
        }
        
        $category = new OnePluginProCategory();
        $category->name = $this->title;
        $category->type = "svg";
        $category->parent_id = 0;
        $category->count = $this->count;
        $category->save();

        foreach($this->icons as $key => $value){
            $icon = new OnePluginProSVGIcon();
            $icon->category = $category->id;
            $icon->name = rand(10000,10000000) . '_' . $key;
            $icon->title = $key;
            $icon->data = $value;
            $icon->tags = explode('.',$key)[0];
            $icon->save();
        }
        $record->handle = $this->handle;
        $record->name = $this->title;
        $record->category = $category->id;
        $record->count = $this->count;
        $record->save(false);
        parent::afterSave($isNew);
    }

    public function beforeDelete(): bool
    {
        //Delete by CASCADE
        if (!parent::beforeDelete()) {
            return false;
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function afterDelete(): void
    {
        $record = OnePluginProSVGIconPack::findOne($this->id);
        Craft::$app->getDb()->createCommand('delete from onepluginpro_svg_icon where category = ' . $record->category)->execute();
        Craft::$app->getDb()->createCommand('delete from onepluginpro_category where id = ' . $record->category)->execute();
        $record->delete();
    }

    public function beforeRestore(): bool
    {
        if (!parent::beforeRestore()) {
            return false;
        }
        //TODO - Check if handle exists and update accordingly

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getCpEditUrl(): null|string
    {
        return UrlHelper::cpUrl('one-plugin-pro/svg-icons/edit/' . $this->id);
    }
    
}
