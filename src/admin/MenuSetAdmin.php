<?php

namespace Sitelease\MenuSets\Admin;

use Sitelease\MenuSets\Models\LinkMenuSet;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;

/**
 * CMS Admin area to maintain menus
 *
 * @package    silverstripe
 * @subpackage silverstripe-menu
 */
class MenuSetAdmin extends ModelAdmin
{
    /**
     * Managed data objects for CMS
     * @var array
     */
    private static $managed_models = [
        LinkMenuSet::class
    ];

    /**
     * URL Path for CMS
     * @var string
     */
    private static $url_segment = 'link-menu-set-manager';

    /**
     * Menu title for Left and Main CMS
     * @var string
     */
    private static $menu_title = 'Menus';

    /**
     * Menu icon for Left and Main CMS
     * @var string
     */
    private static $menu_icon_class = 'font-icon-list';

    /**
     * @var int
     */
    private static $menu_priority = 9;

    /**
     * @param Int       $id
     * @param FieldList $fields
     * @return Form
     */
    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        $form->Fields()
            ->fieldByName($this->sanitiseClassName($this->modelClass))
            ->getConfig()
            ->removeComponentsByType([
                GridFieldImportButton::class,
                GridFieldExportButton::class,
                GridFieldPrintButton::class,
            ]);
        return $form;
    }
}
