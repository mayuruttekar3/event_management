<?php
/* Custom theme option start */
function theme_settings_page() {
?>
<div class="wrap">
	<h1>Custom Theme Options</h1>
		<form method="post" action="options.php" enctype="multipart/form-data">
		    <?php
		        settings_fields("section");
		        do_settings_sections("theme-options");      
		        submit_button(); 
		    ?>          
		</form>
</div>
<?php
}

function maintenance_flag_function() {
?>
	<input type="checkbox" name="maintenance_flag" id="maintenance_flag" value="1" <?php checked(1, get_option('maintenance_flag'), true); ?> />
	<label for="maintenance_flag">Enable maintenance mode</label>
<?php
}

add_action("admin_init", "display_theme_panel_fields");
function display_theme_panel_fields() {
	
	add_settings_section("section", "Custom Theme Option", '', "theme-options");

	add_settings_field("maintenance_flag", "Maintenance Mode", "maintenance_flag_function", "theme-options", "section");
	
	register_setting("section", "maintenance_flag");
}

add_action('admin_menu', 'add_theme_menu_item');
function add_theme_menu_item() {
	add_menu_page('Custom Theme Options', 'Custom Theme Options', 'manage_options', 'theme-panel', 'theme_settings_page', 'dashicons-admin-generic', 99);
}
/* Custom theme option end */
