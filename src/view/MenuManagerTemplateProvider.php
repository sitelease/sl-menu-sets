<?php

namespace Sitelease\LinkMenu\View;

use Sitelease\LinkMenu\Models\LinkMenuSet;
use SilverStripe\View\TemplateGlobalProvider;

/**
 * Adds LinkMenuSet variable to templates
 *
 * @package silverstripe-menu
 */
class MenuManagerTemplateProvider implements TemplateGlobalProvider
{
    /**
     * @return array|void
     */
    public static function get_template_global_variables()
    {
        return [
            'LinkMenuSet' => 'LinkMenuSet'
        ];
    }

    /**
     * @param string $name
     * @return DataList|false
     */
    public static function LinkMenuSet($name)
    {
        if (!$name) {
            return;
        }
        if ($linkMenuSet = LinkMenuSet::get_by_name($name)) {
            return $linkMenuSet->Links();
        }
    }
}
