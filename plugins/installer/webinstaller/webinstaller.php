<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Installer.webinstaller
 *
 * @copyright   Copyright (C) 2013-2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

/**
 * Support for the "Install from Web" tab
 *
 * @package     Joomla.Plugin
 * @subpackage  System.webinstaller
 * @since       3.2
 */
class PlgInstallerWebinstaller extends JPlugin
{
	public $appsBaseUrl = 'https://appscdn.joomla.org/webapps/';

	private $_installfrom = null;
	private $_rtl = null;

	public function onInstallerBeforeDisplay(&$showJedAndWebInstaller)
	{
		$showJedAndWebInstaller = false;
	}
	
	public function onInstallerViewBeforeFirstTab()
	{
		$app = JFactory::getApplication();
 
		$lang = JFactory::getLanguage();
		$lang->load('plg_installer_webinstaller', JPATH_ADMINISTRATOR);
		if (!$this->params->get('tab_position', 0)) {
			$this->getChanges();
		}
	}
	
	public function onInstallerViewAfterLastTab()
	{
		if ($this->params->get('tab_position', 0)) {
			$this->getChanges();
		}
		$installfrom = $this->getInstallFrom();
		$installfromon = $installfrom ? 1 : 0;

		$document = JFactory::getDocument();
		$ver = new JVersion;

		JHtml::_('script', 'plg_installer_webinstaller/client.min.js', array('version' => 'auto'));
		JHtml::_('stylesheet', 'plg_installer_webinstaller/client.min.js', array('version' => 'auto'));

		$installer = new JInstaller();
		$manifest = $installer->isManifest(JPATH_PLUGINS . DIRECTORY_SEPARATOR . 'installer' . DIRECTORY_SEPARATOR . 'webinstaller' . DIRECTORY_SEPARATOR . 'webinstaller.xml');

		$apps_base_url = addslashes($this->appsBaseUrl);
		$apps_installat_url = base64_encode(JURI::current(true) . '?option=com_installer&view=install');
		$apps_installfrom_url = addslashes($installfrom);
		$apps_product = base64_encode(JVersion::PRODUCT);
		$apps_release = base64_encode(JVersion::MAJOR_VERSION . JVersion::MINOR_VERSION . JVersion::PATCH_VERSION);
		$apps_dev_level = base64_encode(JVersion::PATCH_VERSION);
		$btntxt = JText::_('COM_INSTALLER_MSG_INSTALL_ENTER_A_URL', true);
		$pv = base64_encode($manifest->version);
		$updatestr1 = JText::_('COM_INSTALLER_WEBINSTALLER_INSTALL_UPDATE_AVAILABLE', true);
		$obsoletestr = JText::_('COM_INSTALLER_WEBINSTALLER_INSTALL_OBSOLETE', true);
		$updatestr2 = JText::_('JLIB_INSTALLER_UPDATE', true);

		$javascript = <<<END
var apps_base_url = '$apps_base_url',
apps_installat_url = '$apps_installat_url',
apps_installfrom_url = '$apps_installfrom_url',
apps_product = '$apps_product',
apps_release = '$apps_release',
apps_dev_level = '$apps_dev_level',
apps_installfromon = $installfromon,
apps_btntxt = '$btntxt',
apps_pv = '$pv',
apps_updateavail1 = '$updatestr1',
apps_updateavail2 = '$updatestr2',
apps_obsolete = '$obsoletestr';

jQuery(document).ready(function() {
	if (apps_installfromon)
	{
		jQuery('#myTabTabs a[href="#web"]').click();
	}
	var link = jQuery('#myTabTabs a[href="#web"]').get(0);
	var eventpoint = jQuery(link).closest('li');

	jQuery(eventpoint).click(function (event){
		if (!Joomla.apps.loaded) {
			Joomla.apps.initialize();
		}
	});
	
	if (apps_installfrom_url != '') {
		var tag = 'li';
		jQuery(link).closest(tag).click();
	}

	jQuery('#myTabTabs a[href="#web"]').on('shown.bs.tab', function (e) {
        	if (!Joomla.apps.loaded){
           		Joomla.apps.initialize();
        	}
    	});
});

		
END;
		$document->addScriptDeclaration($javascript);
	}

	private function isRTL() {
		if (is_null($this->_rtl)) {
			$document = JFactory::getDocument();
			$this->_rtl = strtolower($document->direction) == 'rtl' ? 1 : 0;
		}
		return $this->_rtl;
	}
	
	private function getInstallFrom()
	{
		if (is_null($this->_installfrom))
		{
			$app = JFactory::getApplication();
			$installfrom = base64_decode($app->input->get('installfrom', '', 'base64'));

			$field = new SimpleXMLElement('<field></field>');
			$rule = new JFormRuleUrl;
			if ($rule->test($field, $installfrom) && preg_match('/\.xml\s*$/', $installfrom)) {
				jimport('joomla.updater.update');
				$update = new JUpdate;
				$update->loadFromXML($installfrom);
				$package_url = trim($update->get('downloadurl', false)->_data);
				if ($package_url) {
					$installfrom = $package_url;
				}
			}
			$this->_installfrom = $installfrom;
		}
		return $this->_installfrom;
	}
	
	private function getChanges()
	{
		$installfrom = $this->getInstallFrom();
		$installfromon = $installfrom ? 1 : 0;
		$dir = '';
		if ($this->isRTL()) {
			$dir = ' dir="ltr"';
		}

		echo JHtml::_('bootstrap.addTab', 'myTab', 'web', JText::_('COM_INSTALLER_INSTALL_FROM_WEB', true)); ?>
			<div id="jed-container" class="tab-pane">
				<div class="well" id="web-loader">
					<h2><?php echo JText::_('COM_INSTALLER_WEBINSTALLER_INSTALL_WEB_LOADING'); ?></h2>
				</div>
				<div class="alert alert-error" id="web-loader-error" style="display:none">
					<a class="close" data-dismiss="alert">×</a><?php echo JText::_('COM_INSTALLER_WEBINSTALLER_INSTALL_WEB_LOADING_ERROR'); ?>
				</div>
			</div>

			<fieldset class="uploadform" id="uploadform-web" style="display:none"<?php echo $dir; ?>>
				<div class="control-group">
					<strong><?php echo JText::_('COM_INSTALLER_WEBINSTALLER_INSTALL_WEB_CONFIRM'); ?></strong><br />
					<span id="uploadform-web-name-label"><?php echo JText::_('COM_INSTALLER_WEBINSTALLER_INSTALL_WEB_CONFIRM_NAME'); ?>:</span> <span id="uploadform-web-name"></span><br />
					<?php echo JText::_('COM_INSTALLER_WEBINSTALLER_INSTALL_WEB_CONFIRM_URL'); ?>: <span id="uploadform-web-url"></span>
				</div>
				<div class="form-actions">
					<input type="button" class="btn btn-primary" value="<?php echo JText::_('COM_INSTALLER_INSTALL_BUTTON'); ?>" onclick="Joomla.submitbutton<?php echo $installfrom != '' ? 4 : 5; ?>()" />
					<input type="button" class="btn btn-secondary" value="<?php echo JText::_('JCANCEL'); ?>" onclick="Joomla.installfromwebcancel()" />
				</div>
			</fieldset>

		<?php echo JHtml::_('bootstrap.endTab');

	}
}
