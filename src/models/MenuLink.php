<?php

namespace Sitelease\LinkMenu\Models;

use gorriecoe\Link\Models\Link;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffoldingProvider;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Security\Member;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * MenuLink
 *
 * @property int $LinkMenuSetID
 * @property int $ParentID
 * @property int $Sort
 * @method MenuLink Parent()
 * @method HasManyList|MenuLink[] Children()
 * @package silverstripe-menu
 */
class MenuLink extends Link implements
    ScaffoldingProvider
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'SL_MenuLink';

    /**
     * Singular name for CMS
     * @var string
     */
    private static $singular_name = 'Link';

    /**
     * Plural name for CMS
     * @var string
     */
    private static $plural_name = 'Links';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Sort' => 'Int'
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'LinkMenuSet' => LinkMenuSet::class,
        'Parent'  => MenuLink::class
    ];

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'Children' => MenuLink::class
    ];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = [
        'Title'          => 'Title',
        'TypeLabel'      => 'Type',
        'LinkURL'        => 'Link',
        'Children.Count' => 'Children'
    ];

    /**
     * Default sort ordering
     * @var array
     */
    private static $default_sort = ['Sort' => 'ASC'];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        if (!$this->isAllowedChildren()) {
            return $fields;
        }
        $fields->addFieldsToTab(
            'Root.' . _t(__CLASS__ . '.CHILDREN', 'Children'),
            [
                GridField::create(
                    'Children',
                    _t(__CLASS__ . '.CHILDREN', 'Children'),
                    $this->Children(),
                    GridFieldConfig_RecordEditor::create()
                        ->addComponent(new GridFieldOrderableRows())
                )
            ]
        );

        return $fields;
    }

    /**
     * Inherit linkMenuSet from parent, if not directly assigned
     *
     * @return LinkMenuSet
     */
    public function LinkMenuSet()
    {
        if ($this->ParentID) {
            return $this->Parent()->LinkMenuSet();
        }
        /** @var LinkMenuSet $linkMenuSet */
        $linkMenuSet = $this->getComponent('LinkMenuSet');
        return $linkMenuSet;
    }

    /**
     * Checks if the menu allows child links.
     * @return Boolean
     */
    public function isAllowedChildren()
    {
        return $this->isInDB() && $this->LinkMenuSet()->AllowChildren;
    }

    /**
     * Relationship accessor for Graphql
     *
     * @return MenuLink|null
     */
    public function getParent()
    {
        if ($this->ParentID) {
            return $this->Parent();
        }
        return null;
    }

    /**
     * Returns the classes for this link.
     * @return string
     */
    public function getClass()
    {
        $this->setClass($this->LinkingMode());
        return parent::getClass();
    }

    /**
     * DataObject view permissions
     * @param Member $member
     * @return boolean
     */
    public function canView($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return true;
    }

    /**
     * DataObject edit permissions
     * @param Member $member
     * @return boolean
     */
    public function canEdit($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return $this->LinkMenuSet()->canEdit($member);
    }

    /**
     * DataObject delete permissions
     * @param Member $member
     * @return boolean
     */
    public function canDelete($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return $this->LinkMenuSet()->canEdit($member);
    }

    /**
     * DataObject create permissions
     * @param Member $member
     * @param array $context Additional context-specific data which might
     *                        affect whether (or where) this object could be created.
     * @return boolean
     */
    public function canCreate($member = null, $context = [])
    {
        $extended = $this->extendedCan(__FUNCTION__, $member, $context);
        if ($extended !== null) {
            return $extended;
        }
        if (isset($context['Parent'])) {
            return $context['Parent']->canEdit();
        }
        return $this->LinkMenuSet()->canEdit();
    }

    public function provideGraphQLScaffolding(SchemaScaffolder $scaffolder)
    {
        $scaffolder->type(MenuLink::class)
            ->addAllFields()
            ->addFields(['LinkURL'])
            ->nestedQuery('Children')
            ->setUsePagination(false)
            ->end()
            ->operation(SchemaScaffolder::READ)
            ->setName('readMenuLinks')
            ->setUsePagination(false)
            ->end()
            ->operation(SchemaScaffolder::READ_ONE)
            ->setName('readOneMenuLink')
            ->end()
            ->operation(SchemaScaffolder::CREATE)
            ->setName('createMenuLink')
            ->end()
            ->operation(SchemaScaffolder::UPDATE)
            ->setName('updateMenuLink')
            ->end()
            ->operation(SchemaScaffolder::DELETE)
            ->setName('deleteMenuLink')
            ->end()
            ->end();
        return $scaffolder;
    }

    /**
     * Return the first menulink matching the given LinkMenuSet and SiteTreeID.
     *
     * @param Sitelease\LinkMenu\Models\LinkMenuSet|String
     * @param Int
     *
     * @return Sitelease\LinkMenu\Models\MenuLink|Null
     */
    public static function get_by_sitetreeID($linkMenuSet, int $siteTreeID)
    {
        if (!$linkMenuSet instanceof LinkMenuSet) {
            $linkMenuSet = LinkMenuSet::get_by_name($linkMenuSet);
        }
        if (!$linkMenuSet) {
            return;
        }
        return DataObject::get_one(self::class, [
            'Type'       => 'SiteTree', // Ensures the admin hasn't intentionally changed this link
            'LinkMenuSetID'  => $linkMenuSet->ID,
            'SiteTreeID' => $siteTreeID
        ]);
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // When writing initial record, set position to last in menu
        if (!$this->isInDB() && is_null($this->Sort)) {
            $this->Sort = $this->getSiblings()->max('Sort') + 1;
        }
    }

    /**
     * Get sibling links
     *
     * @return DataList|MenuLink[]
     */
    public function getSiblings(): DataList
    {
        $siblings = static::get();
        if ($this->ParentID) {
            $siblings = $siblings->filter('ParentID', $this->ParentID);
        }
        if ($this->LinkMenuSetID) {
            $siblings = $siblings->filter('LinkMenuSetID', $this->LinkMenuSetID);
        }
        return $siblings;
    }
}
