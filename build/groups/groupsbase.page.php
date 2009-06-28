<?php


//------------------------------------------------------------------------------------
/**
 * base class for all pages in the groups system,
 * which don't belong to one specific group.
 *
 */

class GroupsBasePage extends PageWithActiveSkin
{

    /**
     * An array of messages that should be shown to the user
     * They are strings to be used in words->get
     *
     * @var array
     */
    protected $_messages;

    /**
     * set a message for the member to see
     *
     * @param string $message - Message to set
     * @access public
     */
    public function setMessage($message)
    {
        if (!isset($this->_messages))
        {
            $this->_messages = array();
        }

        $this->_messages[] = $message;
    }

    /**
     * get all set messages
     *
     * @access public
     * @return array
     */
    public function getMessages()
    {
        if (isset($this->_messages) && is_array($this->_messages))
        {
            return $this->_messages;
        }
        else
        {
            return array();
        }
    }


    protected function leftSidebar()
    {
        $layoutkit = $this->layoutkit;
        $words = $layoutkit->getWords();
        ?>
        <h3><?= $words->get('GroupsActions'); ?></h3>
        <ul class="linklist">
            <li><a href="groups"><?= $words->get('GroupsOverview'); ?></a></li>
            <li><a href="groups/mygroups"><?= $words->get('GroupsMyGroups'); ?></a></li>
        </ul>
        <?
    }
    

    protected function getPageTitle() {
        $words = $this->getWords();
        if (is_object($this->group)) {
            return  $words->getBuffered('Group') . " '".$this->group->Name . "' | BeWelcome";
        } else return $words->getBuffered('Groups') . ' | BeWelcome';
    }

    /**
     * returns the name of the group
     *
     * @todo return translated name
     * @access protected
     * @return string
     */
    protected function getGroupTitle()
    {
        if (!$this->group)
        {
            return '';
        }
        else
        {
            // use translation ... return $words->get($this->group->Name);
            return $this->group->Name;
        }
    }

    protected function isGroupMember() {
        if (!$this->group || !$this->member)
        {
            return false;
        }
        else
        {
            return $this->group->isMember($this->member);
        }
    }
    
    
    protected function teaserContent()
    {
        // &gt; or &raquo; ?
        $words = $this->getWords();
        ?>
        <div id="teaser" class="clearfix">
        <div id="teaser_l1"> 
        <h1><a href="groups"><?= $words->get('Groups');?></a> &raquo; <a href="groups/<?=$this->group->id ?>"><?=$this->group->Name ?></a></h1>
        </div>
        </div>
        <?php
    }
    
    protected function getTopmenuActiveItem()
    {
        return 'groups';
    }
    
    protected function getSubmenuItems()
    {
        $items = array();
        
        $layoutkit = $this->layoutkit;
        $words = $layoutkit->getWords();

        if ($this->group)
        {
            $group_id = $this->group->id;
            $items[] = array('start', 'groups/'.$group_id, $words->get('GroupOverview'));
            $items[] = array('forum', 'groups/'.$group_id.'/forum', $words->get('GroupDiscussions'));
            $items[] = array('wiki', 'groups/'.$group_id.'/wiki', $words->get('GroupWiki'));
            $items[] = array('members', 'groups/'.$group_id.'/members', $words->get('GroupMembers'));
            if ($this->isGroupMember())
            {
                $items[] = array('membersettings', 'groups/'.$group_id.'/membersettings', $words->get('GroupMembersettings'));
            }
            if ($this->member && $this->member->hasPrivilege('GroupsController', 'GroupSettings', $this->group))
            {
                $items[] = array('admin', "groups/{$this->group->getPKValue()}/groupsettings", $words->get('GroupGroupsettings'));
            }

        }
        return $items;
    }
    
    protected function getStylesheets() {
       $stylesheets = parent::getStylesheets();
       $stylesheets[] = 'styles/css/minimal/screen/custom/groups.css';
       $stylesheets[] = 'styles/css/minimal/screen/custom/forums.css';
       return $stylesheets;
    }

}

?>
