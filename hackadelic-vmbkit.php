<?php 
/*
Plugin Name: Hackadelic MultiBlog Kit
Version: 0.1
Plugin URI: http://hackadelic.com/solutions/wordpress/vmbkit
Description: Aids the creation of blogs with Virtual MultiBlog.
Author: Hackadelic
Author URI: http://hackadelic.com
*/

//---------------------------------------------------------------------------------------------

add_action('plugins_loaded', array('HackadelicMultiblogKit', 'start'));

//---------------------------------------------------------------------------------------------

class HackadelicMultiblogKitContext
{
	function CTXID() { return get_class($this); }

	// I18N -------------------------------------------------------------------------------

	function t($s) { return __($s, $this->CTXID());	}
	function e($s) {
		//_e($s, $this->CTXID());
		$s = $this->t($s);
		$args = func_get_args();
		$s = call_user_func_array('printf', $args);
	}

	// Option Access ----------------------------------------------------------------------

	function fullname($name) {
		return $this->CTXID() . '__' . $name;
	}
	function load_option(&$option, $name, $eval=null) {
		$name = $this->fullname($name);
		$value = get_option($name);
		if ($value == null) return false;
		$option = ($eval == null) ? $value : call_user_func($eval, $value);
		return true;
	}
	function save_option(&$option, $name) {
		$name = $this->fullname($name);
		update_option($name, $option);
	}
	function erase_option($name) {
		$name = $this->fullname($name);
		delete_option($name);
	}

	// Informative Messages -----------------------------------------------------------------------------------

	var $messages = array(); // array of messages to display on top of the admin page
	
	function log($format) {
		$format = $this->t($format);
		$args = func_get_args();
		$msg = call_user_func_array('sprintf', $args);
		$this->messages[] = $this->t($msg);
	}
}

//---------------------------------------------------------------------------------------------

class HackadelicMultiblogKit extends HackadelicMultiblogKitContext
{
	// constants --------------------------------------------------------------------------

	var $PLUGIN_TITLE = 'Multiblog Kit';

	// variables --------------------------------------------------------------------------

	var $vmb;
	var $dry_run = true;

	//-------------------------------------------------------------------------------------

	function start() {
		if (!is_admin()) return;
		new HackadelicMultiblogKit();
	}

	//-------------------------------------------------------------------------------------

	function HackadelicMultiblogKit() {
		$this->vmb = new HackadelicVmb($this);
		add_action('admin_menu', array(&$this, 'addAdminMenu'));
	}

	// ------------------------------------------------------------------------------------
	// admin page functions ---------------------------------------------------------------
	// ------------------------------------------------------------------------------------

	function addAdminMenu() {
		$title = $this->PLUGIN_TITLE;
		add_management_page($title, $title, 10, __FILE__, array(&$this, 'handleAdmin'));
	}

	// ------------------------------------------------------------------------------------

	function handleAdmin() {
		$context = $this->CTXID();
		$vmb = $this->vmb;
		//$this->messages = $vmb->errors;
		$actionURL = $_SERVER['REQUEST_URI'];

		if ( isset($_REQUEST['action']) ) {
			check_admin_referer($context);
			$action = $_REQUEST['action'];
			$method = "doAction_$action";
			if (!method_exists($this, $method))
				exit("Unknown request: $action");
			$this->dry_run = $_REQUEST['confirmation'] != 'given';
			$nextURL = $actionURL;    // default value...
			$this->$method($nextURL); // ... which the method may change
		}

		$pluginURL = WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__));

		include 'template-admin.php';
	}

	// ------------------------------------------------------------------------------------
	// actions ----------------------------------------------------------------------------
	// ------------------------------------------------------------------------------------

	function doAction_ActivateVMB() {
		$vmb = $this->vmb;
		$cfgtext0 = file_get_contents( $vmb->pathToBlogWpConfig() );
		$this->initAutoConfigFile($cfgtext0);
		$this->initMbConfigFile($cfgtext0);
		$this->copyWpConfigFromVMB('wp-config-vmb.php');
		$this->copyWpConfigFromVMB('wp-config.php', true);
	}

	// ------------------------------------------------------------------------------------

	function doAction_DeactivateVMB(&$nextURL) {
		$vmb = $this->vmb;
		$bak = $vmb->pathToBlogWpConfigBackup();
		if (!is_file($bak)):
			$this->log('Backup file not found: %s', $bak);
			$nextURL = null;
		else:
			$cfg = $vmb->pathToBlogWpConfig();
			$this->copyFile($bak, $cfg);
		endif;
	}

	// ------------------------------------------------------------------------------------

	function doAction_CreateNewBlog(&$nextURL) {
		global $vusers;
		if (!isset($vusers)) die('Invalid operation');
		$url = $_REQUEST['url'];
		$pfx = $_REQUEST['pfx'];
		$lang = $_REQUEST['lang'];
		//$this->log('CreateNewBlog: url = %s; pfx = %s; lang = %s;', $url, $pfx, $lang);

		$this->addVuser($url, $pfx, $lang, true);
		$vmb = $this->vmb;
		$nextURL = $vmb->urlToWpTwinAt($url); // Jump into installation of new blog
	}

	// ------------------------------------------------------------------------------------
	// auxiliary functions ----------------------------------------------------------------
	// ------------------------------------------------------------------------------------

	function initMbConfigFile($cfgtext0) {
		$currentURL = get_bloginfo('url');
		$p = array(
			'@^\s*define\s*\(\s*\'WPLANG\'\s*,\s*\'(.*?)\'\s*\)\s*;\n?@m',
			'@^\s*\$table_prefix\s*=\s*\'(.*?)\'\s*;\n?@m',
			);
		preg_match($p[0], $cfgtext0, $m); $lang = $m[1];
		preg_match($p[1], $cfgtext0, $m); $dbpfx = $m[1];
		$this->addVuser($currentURL, $dbpfx, $lang, false);
	}

	// ------------------------------------------------------------------------------------

	function initAutoConfigFile($cfgtext0) {
		$vmb = $this->vmb;
		$cfgfile = $vmb->pathToAutoConfig();
		if ($this->canWriteFile($cfgfile)):
			$p = array(
				'@^\<\?php@',
				'@if +\( !defined\(\'ABSPATH\'\) \)\s+define\(\'ABSPATH\', dirname\(__FILE__\) \. \'/\'\);@m',
				'@require_once\(ABSPATH \. \'wp-settings.php\'\);@m',
				'@(?:^//.*\n)*define\s*\(\s*\'WPLANG\'\s*,\s*(.*?)\s*\)\s*;@m',
				'@define\s*\(\s*\'(?!ABSPATH|WPLANG)(.*?)\'\s*,\s*(.*?)\s*\);@m',
				'@(?:^//.*\n)*^(?://)?\s*\$table_prefix\s*=\s*.*?\s*;(?:\s*//.*$)?@m',
				'@(?:\r?\n){2,}@m',
				);
			$s = array(
				"<?php\nif ( ! defined( 'ABSPATH' ) ) exit();	// sanity check\n",
				'',
				'',
				'',
				'$vmb_const[\'\1\'] = \2;',
				'',
				"\n\n",
				);
			$cfgtext = preg_replace($p, $s, $cfgtext0);
			$this->writeToFile($cfgfile, $cfgtext);
		endif;
	}

	// ------------------------------------------------------------------------------------

	function addVuser($url, $pfx, $lang, $overwrite=false) {
		global $vusers;
		$vmb = $this->vmb;
		$v = $vusers;
		$u = $vmb->urlToVuser($url);
		if (isset($v) && in_array($u, $v)):
			$this->log("VUSER '%s' exists. Nothing to do.", $u);
		else:
			$v[] = $u;
			$this->writeMbUsersFile($v, $overwrite);
			$this->writeMbConfigFile($url, $pfx, $lang, $overwrite);
		endif;
	}

	function writeMbUsersFile($vusers, $overwrite=false) {
		//$this->log('writeMbUsersFile: url=%s, vusers=%s', $url, print_r($vusers, true));
		$vmb = $this->vmb;
		$cfgfile = $vmb->pathToVusers();
		//die(sprintf("$cfgfile %s", is_file($cfgfile) ? 'exists' : 'does not exist'));
		if (!$this->canWriteFile($cfgfile, $overwrite)) return;
		$cfgtext = $this->template2s('template-mbusers.php', array('vusers' => $vusers));
		//$this->log('%s:<pre>%s</pre>', $cfgfile, htmlentities($cfgtext));
		$this->writeToFile($cfgfile, $cfgtext);
	}

	// ------------------------------------------------------------------------------------

	function writeMbConfigFile($url, $dbpfx, $lang, $overwrite=false) {
		//$this->log('writeMbConfigFile: url=%s, dbpfx=%s, lang=%s', $url, $dbpfx, $lang);
		$vmb = $this->vmb;
		$cfgfile = $vmb->pathToConfigAtURL($url);
		if (!$this->canWriteFile($cfgfile, $overwrite)) return;
		$cfgtext = $this->template2s('template-mbconfig.php', array(
			'dbpfx' => $dbpfx, 'lang' => $lang));
		//$this->log('%s:<pre>%s</pre>', $cfgfile, htmlentities($cfgtext));
		$this->writeToFile($cfgfile, $cfgtext);
	}

	// ------------------------------------------------------------------------------------

	function canWriteFile($path, $overwrite=false) {
		if (!is_file($path)) return true;
		if ($overwrite):
			$this->log("File '%s' exists, OVERWRITING!", $path);
			return true;
		else:;
			$this->log("File '%s' exists, nothing will be written!", $path);
			return false;
		endif;
	}

	// ------------------------------------------------------------------------------------

	function copyWpConfigFromVMB($filename='wp-config.php', $overwrite=false, $backup=true) {
		$vmb = $this->vmb;
		$t = $vmb->pathToBlogWpConfig($filename);
		if (is_file($t)):
			if (!$overwrite):
				$this->log("File '%s' exists, nothing will be written.", $t);
				return;
			elseif ($backup):
				$s = $vmb->pathToBlogWpConfigBackup($filename);
				if (is_file($s))
					$this->log("Backup file '%s' exists, OVERWRITING!", $s);
				$this->copyFile($t, $s);
			endif;
		endif;
		$s = $vmb->pathToVmbWpConfig($filename);
		$this->copyFile($s, $t);
	}

	// ------------------------------------------------------------------------------------

	function copyFile($s, $t) {
		if ($this->dry_run):
			$this->log("Would have copied '%s' to '%s'", $s, $t);
		else:
			copy($s, $t);
			$this->log("Copied file '%s' to '%s'", $s, $t);
		endif;
	}

	// ------------------------------------------------------------------------------------

	function writeToFile($path, $content) {
		if ($this->dry_run):
			$this->log('Would have written to %s:<pre>%s</pre>', $path, htmlentities($content));
		else:
			file_put_contents($path, $content, FILE_TEXT);
			$this->log('File written: %s', $path);
		endif;
	}

	// ------------------------------------------------------------------------------------

	function template2s($templateName, $data) {
		extract($data, EXTR_SKIP);
		ob_start();
		include $templateName;
		$s = ob_get_contents();
		ob_end_clean();
		return $s;
	}

} // END class HackadelicMultiblogKit


//---------------------------------------------------------------------------------------------

class HackadelicVmb
{
	var $state = ''; // assumed state; one of 'active', 'inactive', 'absent'
	var $errors = array(); // error messages; if not empty, then the assumed state is not valid

	var $dir = ''; // main and configuration locations
	var $cfgdir = '';

	var $context = null; // for I18N

	// construct --------------------------------------------------------------------------

	function HackadelicVmb($context) {
		$this->context = $context;
		$wpContentDir = trailingslashit(WP_CONTENT_DIR);
		if (defined('VMB_DIR')):
			$this->state = 'active';
			$this->_setdir($this->dir, VMB_DIR);
			$this->_setdir($this->cfgdir, defined('VMB_CONFIG_DIR') ? VMB_CONFIG_DIR : $this->dir.'config');
			//$this->_shouldHaveFile($this->pathToAutoConfig());
			//$this->_shouldHaveFile($this->pathToVusers());
			//$this->_shouldHaveFile(ABSPATH.'wp-config-vmb.php');
		elseif (is_dir($this->dir = trailingslashit(WP_CONTENT_DIR) . 'multiblog/')):
			$this->state = 'inactive';
			$this->_setdir($this->cfgdir, $this->dir.'config');
			$this->_shouldHaveFile($this->dir.'wp-config.php');
			$this->_shouldHaveFile($this->dir.'wp-config-vmb.php');
			//$this->_shouldNotHaveFile($this->pathToAutoConfig());
			//$this->_shouldNotHaveFile($this->pathToVusers());
			//$this->_shouldNotHaveFile(ABSPATH.'wp-config-vmb.php');
		else:
			$this->state = 'absent';
			$this->dir = $this->cfgdir = '';
		endif;
	}

	// private helpers --------------------------------------------------------------------

	function _logerr($msg) {
		if (!in_array($msg, $this->errors)) $this->errors[] = $msg;
	}
	function _should($ok, $msg) {
		if (!$ok) $this->_logerr($msg);
	}
	function _setdir(&$var, $value) {
		if (is_dir($value))
			$var = trailingslashit($value);
		else {
			$this->_logerr($this->context->t('Not a directory').": $value");
			$var = '';
		}
		return $var;
	}
	function _shouldHaveFile($path) {
		$this->_should(is_file($path), $this->context->t('Not a file').": $path");
	}
	function _shouldNotHaveFile($path) {
		$this->_should(!is_file($path), $this->context->t('File exists').": $path");
	}

	// queries ----------------------------------------------------------------------------

	function pathToBlogWpConfig($filename='wp-config.php') {
		return ABSPATH . $filename;
	}
	function pathToBlogWpConfigBackup($filename='wp-config.php') {
		return $this->pathToBlogWpConfig($filename) . '.vmbkit-backup';
	}
	function pathToVmbWpConfig($filename='wp-config.php') {
		return $this->dir. $filename;
	}
	function pathToConfigFileNamed($fname) {
		return $this->cfgdir . $fname;
	}
	function pathToVusers($sample=false) {
		$sample = $sample ? '-sample' : '';
		return $this->pathToConfigFileNamed("mb-users$sample.php");
	}
	function pathToAutoConfig($sample=false) {
		$sample = $sample ? '-sample' : '';
		return $this->pathToConfigFileNamed("mb-autoconfig$sample.php");
	}
	function pathToConfigOfVuser($vuser) {
		$vuser = $this->sanitizedVuser($vuser);
		return $this->pathToConfigFileNamed("mb-config-$vuser.php");
	}
	function pathToConfigAtURL($url) {
		$vuser = $this->urlToVuser($url);
		return $this->pathToConfigOfVuser($vuser);
	}
	function sanitizedVuser($vuser) {
		return str_replace(array('/', '.'), '_', $vuser);
	}
	function urlToVuser($url) {
		$url = untrailingslashit($url);
		$u = parse_url($url);
		return $u['host'] . $u['path'];
	}
	function urlToWpTwinAt($url) {
		$thisURL = get_bloginfo('wpurl');
		$u0 = parse_url($thisURL);
		$u = parse_url($url);
		$thatURL = $u0['scheme'] . '://' . $u['host'] . $u0['path'];
		return $thatURL;
	}
}

?>