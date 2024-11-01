<?php echo "<?php\n" ?>
if ( !defined('ABSPATH') ) exit();	// sanity check

<?php foreach ($vusers as $u) : ?>
$vusers[] = '<?php echo $u ?>';
<?php endforeach ?>
<?php echo "\n?>" ?>
