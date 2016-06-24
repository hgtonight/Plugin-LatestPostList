<?php
/**
 *  Copyright 2013-2016 Zachary Doll
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; either version 2
 *  of the License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details
 *
 * 	You should have received a copy of the GNU General Public License
 * 	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * @license GPLv2
 * @copyright 2013-2016 Zachary Doll
 */

$PluginInfo['LatestPostList'] = array(
    'Name' => 'Latest Post List',
    'Description' => 'Lists the latest posts in the panel. Respects permissions, has an AJAX refresh, and is configurable.',
    'Version' => '1.6',
    'RequiredApplications' => array('Vanilla' => '2.2.1'),
    'RequiredTheme' => false,
    'RequiredPlugins' => false,
    'HasLocale' => false,
    'SettingsUrl' => '/plugin/latestpostlist',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => "Zachary Doll",
    'AuthorEmail' => 'hgtonight@daklutz.com',
    'AuthorUrl' => 'http://www.daklutz.com',
    'License' => 'GPLv2'
);

/**
 * Creates a module showing the latest posts in the panel.
 */
class LatestPostList extends Gdn_Plugin {

    /**
     * Create a latestpostlist mini controller.
     * 
     * Creates a method called "LatestPostList" on the PluginController and
     * dispatch, making it act like a controller.
     * 
     * @param PluginController $sender The sending object.
     */
    public function pluginController_latestPostList_create($sender) {
        $this->Dispatch($sender, $sender->RequestArgs);
    }

    /**
     * Get the latest list of discussions as a JSON object.
     * 
     * This gets the latest post date, the latest post list and returns it as
     * json object. This is used to make the AJAX refresh intelligent
     * 
     * @param PluginController $sender The sending object.
     */
    public function controller_getNewList($sender) {
        $lplModule = new LatestPostListModule($sender);
        $data = array('date' => $lplModule->GetDate(), 'list' => $lplModule->PostList());
        echo json_encode($data);
    }

    /**
     * Show settings if no other method was specified.
     * 
     * @param PluginController $sender The base object.
     */
    public function controller_index($sender) {
        $sender->permission('Garden.Settings.Manage');
        $sender->title('Latest Post List Plugin');
        $sender->addSideMenu('plugin/latestpostlist');

        $sender->Form = new Gdn_Form();

        // Set data used by the view
        $sender->setData('PluginDescription', $this->getPluginKey('Description'));

        $Validation = new Gdn_Validation();
        $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
        $ConfigurationModel->setField(array(
            'LatestPostList.Pages' => 'all',
            'LatestPostList.Frequency' => 120,
            'LatestPostList.Count' => 5,
            'LatestPostList.Link' => 'discussions',
            'LatestPostList.Effects' => 'none',
        ));

        // Set the model on the form.
        $sender->Form->setModel($ConfigurationModel);

        // If seeing the form for the first time...
        if ($sender->Form->authenticatedPostBack() === false) {
            // Apply the config settings to the form.
            $sender->Form->setData($ConfigurationModel->Data);
        } else {
            $ConfigurationModel->Validation->applyRule('LatestPostList.Pages', 'Required');
            $ConfigurationModel->Validation->applyRule('LatestPostList.Frequency', 'Required');
            $ConfigurationModel->Validation->applyRule('LatestPostList.Frequency', 'Integer');
            $ConfigurationModel->Validation->applyRule('LatestPostList.Count', 'Required');
            $ConfigurationModel->Validation->applyRule('LatestPostList.Count', 'Integer');
            $ConfigurationModel->Validation->applyRule('LatestPostList.Effects', 'Required');

            $saved = $sender->Form->save();
            if ($saved) {
                $sender->informMessage('<span class="InformSprite Sliders"></span>' . T('Your changes have been saved.'), 'HasSprite');
            }
        }

        // Add the javascript needed for a live preview
        $sender->addJsFile($this->getResource('js/preview.js', false, false));
        // Render the settings view
        $sender->render($this->getView('settings.php'));
    }
    
    /**
     * Based on configuration, is the sending controller disallowed?
     * 
     * @param Gdn_Controller $sender The sending controller.
     * @return boolean Whether the page is disallowed.
     */
    private function disallowedPage($sender) {
        $result = false;
        $pages = c('LatestPostList.Pages', 'both');
        $controller = $sender->ControllerName;
        switch ($pages) {
            case 'announcements':
                $showOnController = array('profilecontroller', 'activitycontroller');
                break;
            case 'discussions':
                $showOnController = array('discussioncontroller', 'discussionscontroller', 'categoriescontroller', 'draftscontroller');
                break;
            case 'both':
            default:
                $showOnController = array(
                    'discussioncontroller',
                    'categoriescontroller',
                    'discussionscontroller',
                    'draftscontroller',
                    'profilecontroller',
                    'activitycontroller');
                break;
        }

        // leave if we aren't in an approved controller
        if ($pages != 'all' && !InArrayI($controller, $showOnController)) {
            $result = true;
        }
        return $result;
    }

    /**
     * Adds the module to the panel on every allowed non-admin page.
     * 
     * @param Gdn_Controller $sender The base controller.
     */
    public function base_render_before($sender) {
        // Never show on admin pages
        if ($sender->MasterView == 'admin' || $this->disallowedPage($sender)) {
            return;
        }

        // bring in the module into this controller
        $module = new LatestPostListModule($sender);
        $sender->addModule($module);

        // Only add the JS file and definition if needed
        $frequency = c('LatestPostList.Frequency', 120);
        if ($frequency > 0) {
            $sender->addJsFile($this->GetResource('js/latestpostlist.js', false, false));
            $sender->addDefinition('LatestPostListFrequency', $frequency);
            $sender->addDefinition('LatestPostListLastDate', $module->getDate());
            $sender->addDefinition('LatestPostListEffects', c('LatestPostList.Effects', 'none'));
        }
    }

    /**
     * Add a link to the dashboard menu to access the settings.
     * 
     * @param Gdn_Controller $sender The controller that wants menu items.
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = &$sender->EventArguments['SideMenu'];
        $menu->addLink('Add-ons', 'Latest Post List', 'plugin/latestpostlist', 'Garden.Settings.Manage');
    }

    /**
     * Set some default values to the config.
     */
    public function setup() {
        $this->updateConfig('Frequency', 120);
        $this->updateConfig('Effects', 'none');
        $this->updateConfig('Count', 5);
        $this->updateConfig('Pages', 'all');
        $this->updateConfig('Link', 'discussions');
    }
    
    /**
     * Update the config settings on update.
     */
    public function structure() {
        $this->setup();
    }
    
    /**
     * Removes old configs and updates to new style.
     * 
     * @param string $name Sub-config to update.
     * @param mixed $default Default to use if previous setting not found.
     */
    private function updateConfig($name, $default) {
        if (c('Plugins.LatestPostList.' . $name) !== false) {
            $default = c('Plugins.LatestPostList.' . $name);
            removeFromConfig('Plugins.LatestPostList.' . $name, true);
        }
        touchConfig('LatestPostList.' . $name, $default);
    }
}
