<?php

namespace Sitelease\MenuSets\Extensions;

use Sitelease\MenuSets\Models\LinkMenuSet;
use Sitelease\MenuSets\Models\MenuLink;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataExtension;

/**
 * Provides the option to automatically create a menu link
 * after creating a page in the sitetree
 *
 * @package silverstripe
 * @subpackage silverstripe-menu
 */
class SiteTreeAutoCreateExtension extends DataExtension
{
    /**
     * Get list of menus owned by this page.
     * @return ArrayList
     */
    public function getOwnsMenu()
    {
        $owner = $this->owner;
        $owns = $owner->config()->get('owns_menu') ? : [];
        $menuSets = ArrayList::create();

        foreach ($owns as $key => $slug) {
            if ($linkMenuSet = LinkMenuSet::get_by_slug($slug)) {
                $menuSets->push($linkMenuSet);
            }
        }
        return $menuSets;
    }

    /**
     * Event handler called after Publishing to the live sitetree.
     */
    public function onAfterPublish()
    {
        $owner = $this->owner;
        foreach ($owner->OwnsMenu as $linkMenuSet) {
            $menuLink = MenuLink::get_by_sitetreeID($linkMenuSet, $owner->ID);
            if ($menuLink) {
                $menuLink->setField('Title', $owner->MenuTitle);
            } else {
                $menuLink = MenuLink::create([
                    'Type' => 'SiteTree',
                    'LinkMenuSetID' => $linkMenuSet->ID,
                    'SiteTreeID' => $owner->ID
                ]);
            };
            $menuLink->write();
        }
    }

    /**
     * Event handler called before unpublishing from live sitetree.
     */
    public function onBeforeUnpublish()
    {
        $owner = $this->owner;
        foreach ($owner->OwnsMenu as $linkMenuSet) {
            $menuLink = MenuLink::get_by_sitetreeID($linkMenuSet, $owner->ID);
            if ($menuLink) {
                $menuLink->delete();
            }
        }
    }

    /**
     * Event handler called before deleting from the database.
     */
    public function onBeforeDelete()
    {
        $owner = $this->owner;

        foreach ($owner->OwnsMenu as $linkMenuSet) {
            $menuLink = MenuLink::get_by_sitetreeID($linkMenuSet, $owner->ID);
            if ($menuLink) {
                $menuLink->delete();
            }
        }
    }
}
