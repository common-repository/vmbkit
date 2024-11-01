<?php
	if ( !defined('ABSPATH') )
		exit('Sorry, you are not allowed to access this page directly.');
?>

<div class="wrap">
<h2><?php $this->e('Virtual Multiblog Operation') ?></h2>

<?php
//---------------------------------------------------------------------------------------------
	$slugHome = $slugWP = 'vmbkit';
	//include 'template-sideinfo.php';
	//$admPageTitle = $this->PLUGIN_TITLE.' '.$this->t('Administration');
	include 'hackadelic-vmbkit-admx.php';
?>

<?php
//---------------------------------------------------------------------------------------------
	$messages = $this->messages;
	//$messages = array('Hi there', 'How are you today', );
?>
<?php if ($messages): ?>
<div id="message" class="updated fade">
<?php foreach ($messages as $msg) : ?>
	<p><?php echo $msg ?></p>
<?php endforeach ?>
</div>
<?php endif ?>

<?php
//---------------------------------------------------------------------------------------------
?>
<style type="text/css">
<?php
	$R = '3px';
	$sideWidth = '13em';
?>
.wp-admin table.form-table { margin-bottom: 1em; clear: none; }

div.main { margin-right: <?php echo $sideWidth ?> }

dl { padding: 0; margin: 10px 1em 20px 0; background-color: white; border: 1px solid #ddd; }
dt { font-size: 10pt; font-weight: bold; margin: 0; padding: 4px 10px 4px 10px;
	background: #dfdfdf url(<?php echo "$pluginURL/bg-pane-header-gray.png" ?>) repeat-x left top;/*
	border-bottom: 1px solid #ddd;*/
}
dd { margin: 0; padding: 10px 20px 10px 20px }
dl {<?php foreach (array('-moz-', '-khtml-', '-webkit-', '') as $pfx) echo " {$pfx}border-radius: $R;" ?> }

dd form table.form-table { margin-bottom: 1em }/*
dd form table.form-table td, dd form table.form-table th { border: 1px solid whitesmoke }*/
dd form table.form-table td input[type=text] { width: 100% }
dd form table.form-table { text-align: left }

dd p.caveat { font-weight: bold; color: #C00; text-align: center }
</style>

<?php
//---------------------------------------------------------------------------------------------
?>
<div class="main">

<?php
//---------------------------------------------------------------------------------------------
if (isset($action) && $nextURL):
//---------------------------------------------------------------------------------------------
?>
<dl>
	<dt><?php $this->e('Operation Completed') ?></dt>
	<dd>
		<div align="center">
			<strong><?php $this->e('Done'); echo ": $action."; ?></strong>
			<a class="button" href="<?php echo $nextURL ?>"><?php
				$this->e('Click To Continue...') ?></a>
		</div>
	</dd>
</dl>

<?php
//---------------------------------------------------------------------------------------------
elseif ($vmb->errors):
//---------------------------------------------------------------------------------------------
?>
<dl>
	<dt><?php $this->e('Virtual Multiblog Installation Issues') ?></dt>
	<dd>
		<p><?php $this->e('Virtual Mutliblog appears to be present') ?>
(<?php echo $vmb->state == 'active' ? 'and active' : 'though inactive' ?>),
		<?php $this->e('but there are some issues with it') ?>:</p>
		<ol style="list-style:decimal inside; font-size:.85em">
<?php foreach ($vmb->errors as $msg) : ?>
			<li><?php $this->e($msg) ?></li>
<?php endforeach ?>
		</ol>
	</dd>
</dl>

<?php
//---------------------------------------------------------------------------------------------
elseif ($vmb->state == 'active'):
//---------------------------------------------------------------------------------------------
	global $vmb_const, $vusers;
?>

<dl>
	<dt><?php $this->e('Create a new blog using the same database as this blog') ?></dt>
	<dd>
		<form method="post">
			<input type="hidden" name="action" value="CreateNewBlog" />
			<?php wp_nonce_field($context) ?>
			<table class="form-table">
			<tr>
				<th scope="row"><label for="url"><?php $this->e('URL of new blog') ?></label></th>
				<td><input id="url" name="url" type="text" value="http://example.com" />
				<?php $this->e(
					'Please beware that, currently, <strong>subdirectories and ports are not supported</strong>.')
				?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="pfx"><?php $this->e('Database table prefix') ?></label></th>
				<td><input id="pfx" name="pfx" type="text" value="<?php printf("wp%03d_", count($vusers)+1) ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="lang"><?php $this->e('Language code; ex.: en, de; empty = no localization') ?></label></th>
				<td><input id="lang" name="lang" type="text" value="<?php echo defined('WPLANG') ? WPLANG : '' ?>" /></td>
			</tr>
			</table>
			<div align="center">
				<input class="button" type="submit" value="<?php $this->e('Create New Blog') ?>" /><br />
				<input type="checkbox" name="confirmation" value="given">
					<?php $this->e('Yes, I want to continue on my own risk') ?>
				</input><br />
				<?php $this->e('(If not checked, a dry run will be performed)') ?>
			</div>
		</form>
	</dd>
</dl>

<dl>
	<dt><?php $this->e("Deactivate Virtual Multiblog") ?></dt>
	<dd>
		<p><?php $this->e('Befor uninstalling Virtual Multiblog, you should <em>deactivate</em> it.') ?></p>
		<p><?php $this->e('To deactivate Virtual Multiblog, '
			.'the previously backed up <tt>wp-config.php</tt> file will be restored.') ?></p>
<?php if (is_file($vmb->pathToBlogWpConfigBackup())): ?>
		<p class="caveat">
			<?php $this->e(
				'Before attempting this operation, be sure the backup file,<br /><tt>%s</tt>,<br /> is a good backup of your original WordPress configuration file, <tt>wp-config.php</tt>.',
				$vmb->pathToBlogWpConfigBackup() ) ?></p>
		<form method="post">
			<input type="hidden" name="action" value="DeactivateVMB" />
			<?php wp_nonce_field($context) ?>
			<div align="center">
				<input class="button" type="submit" value="<?php $this->e('Dectivate Virtual Mutliblog') ?>" /><br />
				<input type="checkbox" name="confirmation" value="given">
					<?php $this->e('Yes, I want to continue on my own risk') ?>
				</input><br />
				<?php $this->e('(If not checked, a dry run will be performed)') ?>
			</div>
		</form>
<?php else: ?>
		<p class="caveat">
			<?php $this->e('Backup file not found. Operation cannot be carried out. Make sure the file<br /><tt>%s</tt><br />exists and is a good backup of your original WordPress configuration file, <tt>wp-config.php</tt>.',
				$vmb->pathToBlogWpConfigBackup() ) ?>.
		</p>
<?php endif ?>
	</dd>
</dl>


<?php
//---------------------------------------------------------------------------------------------
elseif ($vmb->state == 'inactive'):
//---------------------------------------------------------------------------------------------
?>

<dl>
	<dt><?php $this->e("Activate Virtual Multiblog") ?></dt>
	<dd>
		<p><?php $this->e(
		"Virtual MultiBlog (VMB) appears to be present, but not active.
		This is usually the case after a fresh upload of the VMB code files.") ?></p>
		<p><?php $this->e(
		"Upon activation, initial VMB configuration files will be derived from the current contents of <em>wp-config.php</em>, if those files don't exist yet, then a backup of <em>wp-config.php</em> will be created, and then <em>wp-config.php</em> will be replaced with a VMB-specific version.")
		?></p>
		<p class="caveat"><?php $this->e(
			"Before attempting this operation, make sure both, the WordPress configuration file,<br /><tt>%s</tt>,<br />and it's backup file,<br /><tt>%s</tt>,<br />can be safely (over)written!",
			$vmb->pathToBlogWpConfig(),
			$vmb->pathToBlogWpConfigBackup() ) ?></p>
		<p class="caveat"><?php $this->e('And remember: Backup early! Backup often!') ?></p>
		<form method="post">
			<input type="hidden" name="action" value="ActivateVMB" />
			<?php wp_nonce_field($context) ?>
			<div align="center">
				<input class="button" type="submit" value="<?php $this->e('Activate Virtual Mutliblog') ?>" /><br />
				<input type="checkbox" name="confirmation" value="given">
					<?php $this->e('Yes, I want to continue on my own risk') ?>
				</input><br />
				<?php $this->e('(If not checked, a dry run will be performed)') ?>
			</div>
		</form>
	</dd>
</dl>

<?php
//---------------------------------------------------------------------------------------------
else:
//---------------------------------------------------------------------------------------------
?>

<dl>
	<dt><?php $this->e('Virtual Mutliblog Not Found') ?></dt>
	<dd>
		<p><?php $this->e('To use Virtual Mutliblog, it needs to be uploaded into the <tt>wp-content</tt> directory.') ?></p>
		<p><?php $this->e('Download Virtual Mutliblog at') ?>
		<a href="http://striderweb.com/nerdaphernalia/features/virtual-multiblog/">striderweb.com</a>,
		<?php $this->e('or read') ?>
		<a href="http://hackadelic.com/wpmu-is-out-virtual-multiblog-is-in"><?php
			$this->e('a review about its features') ?></a>.</p>
	</dd>
</dl>

<?php endif ?>

</div><!-- END div.main -->
<script type="text/javascript">
jQuery(function($){
	var dt = $('div.wrap dt');
	dt.attr('title', "<?php $this->e('Click to expand') ?>");
	dt.css('cursor', 'pointer');
	dt.click(function(){
		var dl = $(this).parent();
		var dd = $('dd', dl);
		dd.slideToggle('fast');
	});
	//dt.each(function(i, e){
	//	if (i > 0) $(e).click();
	//});
});
</script>
</div><!-- END div.wrap -->

<?php if (is_file(dirname(__FILE__).'/dbg/template-debuginfo.php')) include 'dbg/template-debuginfo.php'; ?>
