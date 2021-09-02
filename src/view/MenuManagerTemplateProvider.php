<?php

namespace Sitelease\MenuSets\View;

use Sitelease\MenuSets\Models\LinkMenuSet;
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
     * @param string $slug
     * @return DataList|false
     */
    public static function LinkMenuSet($slug)
    {
        if (!$slug) {
            return;
        }
        if ($linkMenuSet = LinkMenuSet::get_by_slug($slug)) {
            return $linkMenuSet->Links();
        }
    }
}
