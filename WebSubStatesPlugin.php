<?php

if (!defined('GNUSOCIAL')) {
    exit(1);
}

class WebSubStatesPlugin extends Plugin
{
    const VERSION = '0.0.2';

    function onRouterInitialized($m)
    {
        $m->connect(
            'panel/websub', array(
                'action' => 'websubadminpanel'
            )
        );

        return true;
    }

    function onEndShowStyles($action) {
        $action->cssLink($this->path('css/websub-states.css'));

        return true;
    }

    /**
     * If the plugin's installed, this should be accessible to admins
     */
    function onAdminPanelCheck($name, &$isOK)
    {
        if ($name === 'websub') {
            $isOK = true;

            return false;
        }

        return true;
    }

    function onEndAdminPanelNav($nav) {
        if (AdminPanelAction::canAdmin('user')) {
            $menu_title = _('WebSub States');
            $action_name = $nav->action->trimmed('action');

            $nav->out->menuItem(common_local_url('websubadminpanel'), _m('MENU','WebSub States'),
                                 $menu_title, $action_name == 'websubadminpanel', 'files_admin_panel');
        }
    }

    function onPluginVersion(array &$versions)
    {
        $versions[] = array('name' => 'WebSub States',
                            'version' => self::VERSION,
                            'author' => 'chimo',
                            'homepage' => 'https://github.com/chimo/gs-websubStates',
                            'description' =>
                            // TRANS: Plugin description.
                            _m('See the status of all WebSubs across your instance'));
        return true;
    }
}
