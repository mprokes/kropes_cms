<?php
/**
Plugin Name: Kropes CMS
Version: 0.3.1
Author: Michal Prokeš
Author URI: http://michalprokes.cz
*/


class Kropes_cms{

	// remove links section in administration
	var $admin_remove_links = true;
	// remove comments section in wordpress
	var $admin_remove_comments = true;
	// remove comments section in wordpress
	var $admin_remove_posts = false;

	// remove default dashboard widgets
	var $admin_clean_dashboard = true;

	var $admin_footer_text = '<span id="footer-thankyou">© <a href="http://www.personastudio.cz" target="_blank">Persona Studio</a></span>';

	// admin login logo href
	var $login_headerurl = 'http://personastudio.cz';
 
	// if is_arary, change labels of posts type 
	var $posts_menu_label =array(
		"name"=>'Aktuality',
		"singular_name"=>'Aktualita',
		"add_new"=>'Vložit aktualitu',
		"add_new_item"=>'Nová aktualita',
		"edit_item"=>'Upravit aktualitu',
		"new_item"=>'Aktuality',
		"view_item"=>'Zobraz aktualitu',
		"search_items"=>'Nalezené aktuality',
		"not_found"=>'Žádná aktualita nenalezena',
		"not_found_in_trash"=>'V koši není žádná aktualita',
	);


	public function __construct(){
 		add_action( 'admin_menu', array(&$this,'admin_menu'));
 		add_action( 'init', array(&$this,'init'));
		add_action( 'right_now_content_table_end',array(&$this,'right_now_content_table_end' ));

		add_filter('user_contactmethods',array(&$this,'extend_author_profile'),2,1);

		if($this->admin_footer_text) add_filter('admin_footer_text', array(&$this,'admin_footer_text'));

		add_filter('login_headertitle', array(&$this,'login_headertitle'));  
		if($this->login_headerurl) add_filter('login_headerurl', array(&$this,'login_headerurl'));  
	}



//TODO
	function admin_page_action(){
if ( empty($_POST) )
	                        return;
if ( isset($_POST['aax']) ) {
	                        // @TODO: Uploaded files are not removed here.
	                        check_admin_referer('custom-background-remove', '_wpnonce-custom-background-remove');
	                        set_theme_mod('aax', $_POST['aax']);
	                        $this->updated = true;
	                        return;
	                }
	}


// TODO
	function admin_page(){
?>
<?php if ( !empty($this->updated) ) { ?>
	<div id="message" class="updated">
	<p><?php printf( __( 'Background updated. <a href="%s">Visit your site</a> to see how it looks.' ), home_url( '/' ) ); ?></p>
	</div>
	<?php } ?>
	<form method="post" action="">
	<input name="aax" value="<? echo get_theme_mod('aax', '');?>">
	<?php wp_nonce_field('custom-background-remove', '_wpnonce-custom-background-remove'); ?>
	<?php submit_button( __( 'Remove Background Image' ), 'button', 'remove-background', false ); ?><br/>
	<?php _e('This will remove the background image. You will not be able to restore any customizations.') ?>
	</form>
<?php
	}

//TODO
	function admin_page_load(){
get_current_screen()->add_help_tab( array(
	                        'id'      => 'overview',
	                        'title'   => __('Overview'),
	                        'content' =>
	                                '<p>' . __( 'You can customize the look of your site without touching any of your theme&#8217;s code by using a custom background. Your background can be an image or a color.' ) . '</p>' .
	                                '<p>' . __( 'To use a background image, simply upload it, then choose your display options below. You can display a single instance of your image, or tile it to fill the screen. You can have your background fixed in place, so your site content moves on top of it, or you can have it scroll with your site.' ) . '</p>' .
	                                '<p>' . __( 'You can also choose a background color. If you know the hexadecimal code for the color you want, enter it in the Background Color field. If not, click on the Select a Color link, and a color picker will allow you to choose the exact shade you want.' ) . '</p>' .
	                                '<p>' . __( 'Don&#8217;t forget to click on the Save Changes button when you are finished.' ) . '</p>'
	                ) );

get_current_screen()->set_help_sidebar(
	                        '<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
	                       '<p>' . __( '<a href="http://codex.wordpress.org/Appearance_Background_Screen" target="_blank">Documentation on Custom Background</a>' ) . '</p>' .
	                        '<p>' . __( '<a href="http://wordpress.org/support/" target="_blank">Support Forums</a>' ) . '</p>'
	                );
	}



	function extend_author_profile( $fields )
	{
		$fields['phone'] = 'Telefon';
		$fields['company_function'] = 'Funkce ve firmě';
		return $fields;
	}


	/**
	 * Zjednoduší a upraví menu administrace
	 */
	function admin_menu(){
		if($this->admin_remove_links){
			remove_menu_page('link-manager.php');
			remove_meta_box('dashboard_incoming_links', 'dashboard', 'core');
		}
		if($this->admin_remove_comments){
			remove_menu_page('edit-comments.php');
			remove_meta_box('dashboard_recent_comments', 'dashboard', 'core');

			remove_submenu_page('options-general.php','options-discussion.php');
		}
		if($this->admin_remove_posts){
			remove_menu_page('edit.php');
			remove_meta_box('dashboard_recent_posts', 'dashboard', 'core');
		}


		// click and publish
		remove_submenu_page('tools.php','tools.php');


		if(is_array($this->posts_menu_label)) $this->change_post_menu_label();
		if($this->admin_clean_dashboard) $this->disable_default_dashboard_widgets();


		//TODO theme options
		if ( current_user_can('edit_theme_options') ){
		}
	}


	/**
	 * Init hook
	 */
	function init(){
		load_plugin_textdomain( "kropes_cms", false, basename(dirname(__FILE__)) );
		$this->change_post_object_label();
 	}



	/**
	 * Change name of posts type in admin menu
 	 */
	private function change_post_menu_label() {
		global $menu;
		global $submenu;

		$n = (array)$this->posts_menu_label;

		$menu[5][0] = $n[name];
		if($n[name]) $submenu['edit.php'][5][0] = __('Všechny').' '.$n[name];
		if($n[add_new]) $submenu['edit.php'][10][0] = $n[add_new];
		echo '';
	}

	/**
	 * Change name of posts type 
 	 */
	function change_post_object_label() {
		global $wp_post_types;
		$labels = &$wp_post_types['post']->labels;
		foreach((array)$this->posts_menu_label AS $key=>$val){
		  if(property_exists($labels,$key)) $labels->{$key}=$val;
		}
	}


	/**
	 * Disable default dasboard widgets
	 */
	public static function disable_default_dashboard_widgets() {

	//	remove_meta_box('dashboard_right_now', 'dashboard', 'core');
		remove_meta_box('dashboard_plugins', 'dashboard', 'core');
		remove_meta_box('dashboard_quick_press', 'dashboard', 'core');
	//	remove_meta_box('dashboard_recent_drafts', 'dashboard', 'core');
		remove_meta_box('dashboard_primary', 'dashboard', 'core');
		remove_meta_box('dashboard_secondary', 'dashboard', 'core');
	}



	/**
	 * Function to add custom post types and taxonomies to right_now dashboard
 	 */
	function right_now_content_table_end() {

		$args = array(
		  'public' => true ,
		  '_builtin' => false
		);
		$output = 'object';
		$operator = 'and';
	 	$post_types = get_post_types( $args , $output , $operator );


		// post types
		foreach( $post_types as $post_type ) {
		  $num_posts = wp_count_posts( $post_type->name );
		  $num = number_format_i18n( $num_posts->publish );
		  $text = _n( $post_type->labels->singular_name, $post_type->labels->name , intval( $num_posts->publish ) );
		  if ( current_user_can( 'edit_posts' ) ) {
		    $num = "<a href='edit.php?post_type=$post_type->name'>$num</a>";
		    $text = "<a href='edit.php?post_type=$post_type->name'>$text</a>";
		  }
		  echo '<tr><td class="second b b-' . $post_type->name . '">' . $num . '</td>';
		  echo '<td class="t ' . $post_type->name . '">' . $text . '</td></tr>';
		 }

		// taxonomies
		$taxonomies = get_taxonomies( $args , $output , $operator ); 
		foreach( $taxonomies as $taxonomy ) {
		  	$num_terms  = wp_count_terms( $taxonomy->name );
		  	$num = number_format_i18n( $num_terms );
		  	$text = _n( $taxonomy->labels->singular_name, $taxonomy->labels->name , intval( $num_terms ));
		  	if ( current_user_can( 'manage_categories' ) ) {
		   		$num = "<a href='edit-tags.php?taxonomy=$taxonomy->name'>$num</a>";
		   		$text = "<a href='edit-tags.php?taxonomy=$taxonomy->name'>$text</a>";
		 	}
		  	echo '<tr><td class="first b b-' . $taxonomy->name . '">' . $num . '</td>';
		  	echo '<td class="t ' . $taxonomy->name . '">' . $text . '</td></tr>';
		}
	}


	/** change footer text in administration */
	function admin_footer_text() {
	    echo $this->admin_footer_text ? $this->admin_footer_text : '';
	} 


	// Whitelabel - admin login title
	function login_headertitle()  
	{  
	    echo get_option('blogname'); 
	}

	// Whitelabel - admin login url
	function login_headerurl()  
	{  
	    echo $this->login_headerurl ? $this->login_headerurl : bloginfo('url');  
	}


	public static function columns_menu($menu,$columns=1){
		// if menu is string, find id
		if(!is_int($menu)){
 		  $menu_locations = get_nav_menu_locations();
		  $menu = $menu_locations[$menu];
		}   		
		$items = wp_get_nav_menu_items($menu);

		$cols = array();
		for($x=0; $items[$x]; $x++){
			$i = $items[$x];
			if($i->type=="post_type" && $i->object_id){
				$detail = get_post_meta($i->object_id,'kropes_cms_menu_excerpt',true);	
			}
			$cols[$x%$columns].="<li><a href='".$i->url."'>".$i->title."<span style='display: none' class='kropes_cms_menu_excerpt'>$detail</span></a></li>";
		}

		$colclass = "col".(12/$columns);
	
		foreach($cols AS $c){
			echo "<div class='".$colclass."'><ul>".$c."</ul></div>";
		}
	}
	      


}

$kropes_cms = new Kropes_cms();

?>
