<?php

namespace Sitelease\MenuSets\Models;

use Sitelease\MenuSets\Models\MenuLink;

use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;

use SilverStripe\ORM\DB;
use SilverStripe\Core\Convert;
use SilverStripe\Control\Controller;
use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use SilverStripe\GraphQL\Scaffolding\Interfaces\ScaffoldingProvider;
use SilverStripe\GraphQL\Scaffolding\Scaffolders\SchemaScaffolder;

use SilverStripe\ORM\DataObject;
/**
 * LinkMenuSet
 *
 * @property string $Title
 * @property string $Slug
 * @property bool $AllowChildren
 * @method HasManyList|MenuLink[] Links()
 * @package silverstripe-menu
 */
class LinkMenuSet extends DataObject implements ScaffoldingProvider
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'SL_MenuSet';

    /**
     * Singular name for CMS
     * @var string
     */
    private static $singular_name = 'Menu Set';

    /**
     * Plural name for CMS
     * @var string
     */
    private static $plural_name = 'Menu Sets';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title'         => 'Varchar(255)',
        'Slug'          => 'Varchar(255)',
        'AllowChildren' => 'Boolean',
        'AutoGenerated' => 'Boolean',
    ];

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'Links' => MenuLink::class,
    ];

    /**
     * Defines summary fields commonly used in table columns
     * as a quick overview of the data for this dataobject
     * @var array
     */
    private static $summary_fields = [
        'Title'       => 'Title',
        'Links.Count' => 'Links'
    ];

    /**
     * Defines a default list of filters for the search context
     * @var array
     */
    private static $searchable_fields = [
        'Title'
    ];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = FieldList::create(TabSet::create('Root')->setTitle(_t(__CLASS__ . '.TABMAIN', 'Main')));

        if (empty($this->ID)) {
            $fields->addFieldToTab(
                'Root.MenuCreation',
                TextField::create(
                    "Title",
                    'Menu Set Title',
                )->setDescription("The name that will be displayed in the admin interface when editing this menu"),                            
            );
        } else {
            $fields->addFieldToTab(
                'Root.Main',
                GridField::create(
                    'Links',
                    _t(__CLASS__ . '.FIELDLINKS', 'Links'),
                    $this->Links,
                    GridFieldConfig_RecordEditor::create()
                        ->addComponent(new GridFieldOrderableRows('Sort'))
                )
            );
        }
        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    /**
     * Validates object data
     *
     * @author Benjamin Blake (sitelease.ca)
     *
     * @return RequiredFields
     */
    public function getCMSValidator()
    {
        return new RequiredFields([
            'Title',
        ]);
    }

    public function validate()
    {
        $result = parent::validate();

        // If this is not in the DB, and is created via the CMS interface
        if (!$this->isInDB() && !$this->AutoGenerated) {
            $slug = LinkMenuSet::toCamelCase($this->Title);
            $existingSlug = LinkMenuSet::get()->find("Slug", $slug);
            if (!empty($existingSlug)) {
                $result->addError('Menu Set Title in Use - Please use a different Title as that one has already been used');
            }
        }

        return $result;
    }

    public function onBeforeWrite()
    {        
        // If this is the first write
        if (!$this->isInDb()) {
            $slug = LinkMenuSet::toCamelCase($this->Title);

            if (empty($this->Slug)) {
                $this->Slug = $slug;
            }
        }
        parent::onBeforeWrite();
    }

    /**
     * Return a map of permission codes to add to the dropdown shown in the Security section of the CMS
     * @return array
     */
    // public function providePermissions()
    // {
    //     $permissions = [];
    //     foreach (LinkMenuSet::get() as $linkMenuSet) {
    //         $key = $linkMenuSet->PermissionKey();
    //         $permissions[$key] = [
    //             'name'     => _t(
    //                 __CLASS__ . '.EDITMENUSET',
    //                 "Manage links with in '{name}'",
    //                 [
    //                     'name' => $linkMenuSet->obj('Title')
    //                 ]
    //             ),
    //             'category' => _t(__CLASS__ . '.MENUSETS', 'Menu sets')
    //         ];
    //     }
    //     return $permissions;
    // }

    // /**
    //  * @return string
    //  */
    // public function PermissionKey()
    // {
    //     return $this->obj('Slug')->Uppercase() . 'EDIT';
    // }

    /**
     * Deleting Permissions
     * This module will not allow YAML configured sets to be deleted
     * @param mixed $member
     * @return boolean
     */
    public function canDelete($member = null)
    {
        if ($this->wasAutoGenerated()) {
            return false;
        }
        return Permission::check('CMS_ACCESS', 'any', $member);
    }

    /**
     * Editing Permissions
     * @param mixed $member
     * @return boolean
     */
    public function canEdit($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        // Restrict permissions based on saved key
        // if ($this->isInDB()) {
        //     return Permission::check($this->PermissionKey(), 'any', $member);
        // }

        // If canEdit() is called on an unsaved singleton, default to any users with CMS access
        // This allows MenuLink objects to be created via gridfield,
        // which will call the singleton LinkMenuSet::canEdit()
        return Permission::check('CMS_ACCESS', 'any', $member);
    }

    /**
     * Viewing Permissions
     * @param mixed $member
     * @return boolean
     */
    public function canView($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return Permission::check('CMS_ACCESS', 'any', $member);
    }

    /**
     * Set up default records based on the yaml config
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        if (!empty($this->config()->get('sets'))) {
            $default_menu_sets = $this->config()->get('sets') ?: array();
        
            foreach ($default_menu_sets as $slug => $options) {
                if (is_array($options)) {
                    $title = $options['title'];
                    $allowChildren = isset($options['allow_children']) ? $options['allow_children'] : false;
                } else {
                    $title = $options;
                    $allowChildren = false;
                }
                $slug = Convert::raw2htmlid($slug);
                $record = LinkMenuSet::get()->find('Slug', $slug);
                if (!$record) {
                    $record = LinkMenuSet::create();
                    DB::alteration_message("Menu set '$title' created", 'created');
                } else {
                    DB::alteration_message("Menu set '$title' updated", 'updated');
                }
                $record->Slug = $slug;
                $record->Title = $title;
                $record->AllowChildren = $allowChildren;
                $record->AutoGenerated = 1;
                $record->write();
            }
        }
    }

    /**
     * Generates a link to edit this page in the CMS.
     *
     * @return string
     */
    public function CMSEditLink()
    {
        return Controller::join_links(
            Controller::curr()->Link(),
            'EditForm',
            'field',
            $this->ClassName,
            'item',
            $this->ID
        );
    }

    /**
     * Return the first menuset matching the given slug.
     *
     * @return LinkMenuSet|false
     */
    public static function get_by_slug($slug)
    {
        if ($slug) {
            return self::get()->find('Slug', $slug);
        }
        return false;
    }

    /**
     * Relationship accessor for Graphql
     * @return ManyManyList MenuLink
     */
    public function getLinks()
    {
        return $this->Links()->filter([
            'ParentID' => 0
        ]);
    }

    public function provideGraphQLScaffolding(SchemaScaffolder $scaffolder)
    {
        $scaffolder->type(LinkMenuSet::class)
            ->addAllFields()
            ->nestedQuery('Links')
            ->setUsePagination(false)
            ->end()
            ->operation(SchemaScaffolder::READ)
            ->setName('readMenuSets')
            ->setUsePagination(false)
            ->end()
            ->operation(SchemaScaffolder::CREATE)
            ->setName('createMenuSet')
            ->end()
            ->operation(SchemaScaffolder::UPDATE)
            ->setName('updateMenuSet')
            ->end()
            ->operation(SchemaScaffolder::DELETE)
            ->setName('deleteMenuSet')
            ->end()
            ->end()
            ->query('readOneMenuSet', LinkMenuSet::class)
            ->setUsePagination(false)
            ->addArgs([
                'Slug' => 'String!'
            ])
            ->setResolver(function ($object, array $args, $context, ResolveInfo $info) {
                if (!singleton(LinkMenuSet::class)->canView($context['currentUser'])) {
                    throw new \InvalidArgumentException(sprintf(
                        '%s view access not permitted',
                        LinkMenuSet::class
                    ));
                }
                if ($args['Slug']) {
                    return LinkMenuSet::get()->find('Slug', $args['Slug']);
                }
            })
            ->end();
        return $scaffolder;
    }

    /**
     * Returns true if this item was automatically generated
     * from the YAML configuration
     *
     * @author Benjamin Blake (sitelease.ca)
     *
     * @return boolean
     */
    public function wasAutoGenerated()
    {
        return $this->AutoGenerated;
    }
    
    /**
     * Convert a space seperated string (like this) to a camel case string (likeThis)
     *
     * By default, will replace all special characters with 
     * spaces before upercasing words and replacing spaces
     * 
     * @author Benjamin Blake (sitelease.ca)
     *
     * @param string $str The string you wish to convert
     * @param array $noStrip An array of characters to leave in the string
     * @return string
     */
    public static function toCamelCase($str, array $noStrip = [])
    {
        // non-alpha and non-numeric characters become spaces
        $str = preg_replace('/[^a-z0-9' . implode("", $noStrip) . ']+/i', ' ', $str);
        $str = trim($str);
        
        // Lowercase everything to destroy existing uppercase characters
        $str = strtolower($str);

        // uppercase the first character of each word
        $str = ucwords($str);
        $str = str_replace(" ", "", $str);

        return $str;
    }
}
