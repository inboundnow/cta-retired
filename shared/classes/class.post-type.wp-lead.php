<?php


if ( !class_exists('Inbound_Leads') ) {

	class Inbound_Leads {

		/**
		*  Initialize Inbound_Leads class
		*/
		function __construct() {
			self::load_hooks();
		}

		/**
		*  Load action hooks & filters
		*/
		private function load_hooks() {
			/* Register Leads Post Type */
			add_action( 'init', array(__CLASS__, 'register_post_type' ));
			add_action( 'admin_init' , array( __CLASS__ , 'register_role_capabilities' ) ,999);
			add_action( 'init', array(__CLASS__, 'register_taxonomies' ));

			/* Modify columns on lead list creation page */
			add_filter( 'manage_edit-wplead_list_category_columns', array(__CLASS__, 'register_lead_list_columns' ));
			add_filter( 'manage_wplead_list_category_custom_column', array(__CLASS__, 'support_lead_list_columns' ), 10, 3);
            
            /* Add the setting saver and getter */
            add_action('wplead_list_category_edit_form', array(__CLASS__, 'lead_list_save_settings'));
            add_action('wplead_list_category_edit_form', array(__CLASS__, 'lead_list_do_settings'));

			if (is_admin()) {
				add_action( 'edit_form_after_title', array(__CLASS__, 'install_leads_prompt' ) );
			}
            
            /**AJAX**/
            /* Save the settings to the term meta */
            add_action('wp_ajax_lead_list_save_settings', array(__CLASS__, 'ajax_lead_list_save_settings'));
		}
		/**
		*	Register wp-lead post type
		*/
		public static function register_post_type() {

			$lead_active = ( defined('WPL_CURRENT_VERSION') ) ? true : false ; // Check if leads is activated

			$labels = array(
				'name' => _x('Leads', 'post type general name'),
				'singular_name' => _x('Lead', 'post type singular name'),
				'add_new' => _x('Add New', 'Lead'),
				'add_new_item' => __('Add New Lead'),
				'edit_item' => __('Edit Lead'),
				'new_item' => __('New Leads'),
				'view_item' => __('View Leads'),
				'search_items' => __('Search Leads'),
				'not_found' =>	__('Nothing found'),
				'not_found_in_trash' => __('Nothing found in Trash'),
				'parent_item_colon' => ''
			);

			$args = array(
				'labels' => $labels,
				'public' => false,
				'publicly_queryable' => false,
				'show_ui' => true,
				'query_var' => true,
				'menu_icon' => INBOUNDNOW_SHARED_URLPATH . 'assets/images/global/leads.png',
				'capability_type' => array('lead','leads'),
				'map_meta_cap' => true,
				'hierarchical' => false,
				'menu_position' => 31,
				'supports' => array('custom-fields','thumbnail')
			);

			$args['show_in_menu'] = ($lead_active) ? true : false;

			register_post_type( 'wp-lead', $args );

		}

		/**
		 * Register Role Capabilities
		 */
		public static function register_role_capabilities() {
			// Add the roles you'd like to administer the custom post types
			$roles = array('inbound_marketer','editor','administrator');

			// Loop through each role and assign capabilities
			foreach($roles as $the_role) {

				$role = get_role($the_role);
				if (!$role) {
					continue;
				}

				$role->add_cap( 'read' );
				$role->add_cap( 'read_lead');
				$role->add_cap( 'read_private_leads' );
				$role->add_cap( 'edit_lead' );
				$role->add_cap( 'edit_leads' );
				$role->add_cap( 'edit_others_leads' );
				$role->add_cap( 'edit_published_leads' );
				$role->add_cap( 'publish_leads' );
				$role->add_cap( 'delete_others_leads' );
				$role->add_cap( 'delete_private_leads' );
				$role->add_cap( 'delete_published_leads' );
			}
		}

		/**
		*	Register Category Taxonomy
		*/
		public static function register_taxonomies() {

			/* bail if taxonomy already registered */
			if (taxonomy_exists('wplead_list_category')) {
				return;
			}

			/* Register lead lists */
			$list_labels = array(
				'name'						=> __( 'Lists', 'inbound-pro' ),
				'singular_name'				=> __( 'Lead List', 'inbound-pro' ),
				'search_items'				=> __( 'Search Lead Lists', 'inbound-pro' ),
				'popular_items'				=> __( 'Popular Lead Lists', 'inbound-pro' ),
				'all_items'					=> __( 'All Lead Lists', 'inbound-pro' ),
				'parent_item'				=> null,
				'parent_item_colon'			=> null,
				'edit_item'					=> __( 'Edit Lead List', 'inbound-pro' ),
				'update_item'				=> __( 'Update Lead List', 'inbound-pro' ),
				'add_new_item'				=> __( 'Add New Lead List', 'inbound-pro' ),
				'new_item_name'				=> __( 'New Lead List', 'inbound-pro' ),
				'separate_items_with_commas' => __( 'Separate Lead Lists with commas', 'inbound-pro' ),
				'add_or_remove_items'		=> __( 'Add or remove Lead Lists', 'inbound-pro' ),
				'choose_from_most_used'		=> __( 'Choose from the most used lead List', 'inbound-pro' ),
				'not_found'					=> __( 'No Lead Lists found.', 'inbound-pro' ),
				'menu_name'					=> __( 'Lead Lists', 'inbound-pro' ),
			);

			$list_args = array(
				'hierarchical'			=> true,
				'labels'				=> $list_labels,
				'singular_label'		=> __( 'List Management', 'inbound-pro' ),
				'show_ui'				=> true,
				'show_in_menu'			=> true,
				'show_in_nav_menus'		=> false,
				'show_admin_column'		=> true,
				'query_var'				=> true,
				'rewrite'				=> false,
			);

			register_taxonomy('wplead_list_category','wp-lead', $list_args );

			/* Register Lead Tags Taxonomy */
			$labels = array(
				'name'						=> __( 'Tags', 'inbound-pro' ),
				'singular_name'				=> __( 'Lead Tag', 'inbound-pro' ),
				'search_items'				=> __( 'Search Lead Tags' , 'inbound-pro' ),
				'popular_items'				=> __( 'Popular Lead Tags' , 'inbound-pro'),
				'all_items'					=> __( 'All Lead Tags' , 'inbound-pro' ),
				'parent_item'				=> null,
				'parent_item_colon'			=> null,
				'edit_item'					=> __( 'Edit Lead Tag' , 'inbound-pro' ),
				'update_item'				=> __( 'Update Lead Tag' , 'inbound-pro' ),
				'add_new_item'				=> __( 'Add New Lead Tag' , 'inbound-pro' ),
				'new_item_name'				=> __( 'New Lead Tag' , 'inbound-pro' ),
				'separate_items_with_commas'=> __( 'Separate Lead Tags with commas' , 'inbound-pro' ),
				'add_or_remove_items'		=> __( 'Add or remove Lead Tags' , 'inbound-pro'),
				'choose_from_most_used'		=> __( 'Choose from the most used lead tags', 'inbound-pro' ),
				'not_found'					=> __( 'No lead tags found.' , 'inbound-pro'),
				'menu_name'					=> __( 'Lead Tags', 'inbound-pro' ),
			);

			$args = array(
				'hierarchical'			=> false,
				'labels'				=> $labels,
				'show_ui'				=> true,
				'show_admin_column'		=> true,
				'show_in_menus'			=> false,
				'show_in_nav_menus'		=> false,
				'update_count_callback' => '_update_post_term_count',
				'query_var'				=> true,
				'rewrite'				=> false
			);

			register_taxonomy( 'lead-tags', 'wp-lead', $args );
		}

		/**
		 *  Adds ID and Double Opt In columns to lead-tags WP List Table
		 */
		public static function register_lead_list_columns( $cols ) {
			$new_columns = array(
				'cb' => '<input type="checkbox" />',
				'lead_id' => __('ID', 'inbound-pro' ),
                'double_optin' => __('Double Opt In', 'inbound-pro'),
				'name' => __('Name', 'inbound-pro' ),
				'description' => __('Description', 'inbound-pro' ),
				'slug' => __('Slug', 'inbound-pro' ),
				'posts' => __('Posts', 'inbound-pro' )
				);
			return $new_columns;
		}

		/**
		 *  Displays the list id and double option status in the lead-tags WP List Table
		 */
		public static function support_lead_list_columns( $out, $column_name, $term_id ) {
			
            switch($column_name){
				case 'lead_id':
					echo $term_id;
				break;
				
				case 'double_optin':
                    /*get the double optin waiting list id*/
                    if(!defined('INBOUND_PRO_CURRENT_VERSION')){
                        $double_optin_list_id = get_option('list-double-optin-list-id', '');
                    }else{
                        $settings = Inbound_Options_API::get_option('inbound-pro', 'settings', array());
                        $double_optin_list_id = $settings['leads']['list-double-optin-list-id'];
                    }
                    /*if the current term isn't the double optin list, display the double optin status*/
                    if($term_id != $double_optin_list_id){
                        $settings = get_term_meta($term_id, 'wplead_lead_list_meta_settings');
                        if(!empty($settings[0]['double_optin']) &&  $settings[0]['double_optin'] == 1){
                            echo '<span>' . __('on', 'inbound-pro') . '</span>';
                        }else{
                            echo '<span>' . __('off', 'inbound-pro') . '</span>';
                        }
                    }
				break;
			
			}
		}
        
        /**
         * Gets the values of all inboundnow-lead-list-option class inputs, and sends them to ajax_lead_list_save_settings for saving.
         * The element attribute "name" is the key for the settings.
         */
        public static function lead_list_save_settings($list){
            ?>
            <script>
                jQuery(document).ready(function(){
                    jQuery('input#submit').on('click', function(){
                        var settingData = {};
                        var id = "<?php echo $list->term_id; ?>";
                        /*get the value of all inboundnow lead list options*/
                        jQuery('.inboundnow-lead-list-option').each(function(){
                            settingData[jQuery(this).attr("name")] = jQuery(this).val();
                        });

                        jQuery.ajax({
                            type: 'POST',
                            url: ajaxurl,
                            data: {
                                action: 'lead_list_save_settings',
                                id: id,
                                data: settingData,
                            },
                            success: function(response){
                                console.log(JSON.parse(response));
                            },
                        });
                        
                    });
                });
            </script>
            <?php
        }        
        
        /**
         * Sets the inboundnow-lead-list-option class inputs to the values stored in the term meta.
         * The elements are selected by the "name" attribute, which is also the setting key.
         */
        public static function lead_list_do_settings($list){
            $settings = get_term_meta($list->term_id, 'wplead_lead_list_meta_settings', true);
                /*remove the email text input, the editor info is supplied as the editor default. Also quotes make the json parse fail*/
                unset($settings['is_html_inbound-email-response-text-data-input']);
            ?>
            
            <script>
                jQuery(document).ready(function(){
                    var settings = jQuery.parseJSON('<?php echo json_encode($settings, JSON_FORCE_OBJECT); ?>');
                    console.log(settings);
                    for(var a in settings){
                        jQuery('.inboundnow-lead-list-option[name="' + a + '"]').val(settings[a]);
                    }
                });
            
            </script>
            <?php
        }

        /**
         * Saves form options to the term meta
         * is_html_ is a token for passing html to the settings.
         * To use it, prefix the html "name" value with it.
         * So <input name="inbound-html"> becomes <input name="is_html_inbound_html">
         */
        public static function ajax_lead_list_save_settings(){
            $data = stripslashes_deep($_POST['data']);
            $cleaned = array();
            foreach($data as $key => $value){
                /*if html has been passed*/
                if(substr($key, 0, 8) == 'is_html_'){
                    /*add the cleaned html string to the array of cleaned values*/
                    $cleaned[sanitize_text_field($key)] = wp_kses_post($value);
                }else{
                    $cleaned[sanitize_text_field($key)] = sanitize_text_field($value);	
                }
            }
            
            /*get the existing stored settings*/
            $meta = get_term_meta((int)$_POST['id'], 'wplead_lead_list_meta_settings', true);
            
            /**if the settings aren't empty, add each cleaned setting to the settings**/
            if(!empty($meta)){
                foreach($cleaned as $setting_name => $setting_value){
                    $meta[$setting_name] = $setting_value;
                }
            }else{
            /*if the settings are empty, just push the cleaned data*/
                $meta = $cleaned;
            }
            
            update_term_meta((int)$_POST['id'], 'wplead_lead_list_meta_settings', $meta);

            echo json_encode(__('Settings Updated!', 'inbound-pro'));
            die();
        }

		/**
		*	Make sure that all list ids are intval
		*
		*	@param MIXED $lists
		*	@return ARRAY
		*
		*/
		public static function intval_list_ids( $lists ) {

			if (is_array($lists)) {
				foreach ($lists as $key => $id) {
					$lists[ $key ] = intval($id);
				}
			} else {
				$lists = intval($lists);
			}

			return $lists;
		}


		/**
		* Adds lead to list
		*
		* @param lead_id INT
		* @param list_id MIXED INT,ARRAY
		*
		*/
		public static function add_lead_to_list( $lead_id, $list_id ) {

			/* intval list ids */
			$list_id = Inbound_Leads::intval_list_ids( $list_id );

			wp_set_object_terms( $lead_id, $list_id, 'wplead_list_category', true );
			do_action('add_lead_to_lead_list', $lead_id, $list_id );
		}


		/**
		* Removes lead from list
		*
		* @param lead_id INT
		* @param list_id MIXED INT, ARRAY
		*
		*/
		public static function remove_lead_from_list( $lead_id, $list_id ) {
			/* intval list ids */
			$list_id = Inbound_Leads::intval_list_ids( $list_id );

			wp_remove_object_terms( $lead_id, $list_id, 'wplead_list_category', true );
			do_action('remove_lead_from_list', $lead_id, $list_id );
		}

		/**
		* Get an array of all lead lists belonging to lead id
		*
		* @param INT $lead_id ID of lead
		*
		* @returns ARRAY of lead lists with term id as key and list name as value
		*/
		public static function get_lead_lists_by_lead_id( $lead_id ) {

			$args = array(
				'hide_empty' => false
			);

			$terms = get_the_terms( $lead_id, 'wplead_list_category' );

			if (!$terms) {
				return array();
			}

			foreach ( $terms as $term	) {
				$array[$term->term_id] = $term->name;
			}

			return $array;
		}

		/**
		 *  Adds a new lead list
		 */
		public static function create_lead_list( $args ) {

			$params = array();

			/* if no list name is present then return null */
			if ( !isset( $args['name'] )) {
				return null;
			}

			if (isset( $args['description'] )) {
				$params['description'] = $args['description'];
			}

			if (isset( $args['parent'] )) {
				$params['parent'] = $args['parent'];
			} else {
				$params['parent'] = 0;
			}

			$term = term_exists(  $args['name'], 'wplead_list_category', $params['parent'] );

			/* if term does not exist then create it */
			if ( !$term ) {
				$term = wp_insert_term(	$args['name'], 'wplead_list_category', $params );
			}

			if ( is_array($term) && isset( $term['term_id'] ) ) {
				return array( 'id' => $term['term_id'] );
			} else if ( is_numeric($term) ) {
				return array( 'id' => $term );
			} else {
				return $term;
			}
		}

		/**
		 *  updates a lead list
		 *  @param ARRAY $args
		 *  @retun ARRAY ontaining list id
		 */
		public static function update_lead_list( $args ) {

			/* id is required */
			if (!isset($args['id'])) {
				return null;
			}

			if (isset( $args['name'] )) {
				$params['name'] = $args['name'];
			}

			if (isset( $args['description'] )) {
				$params['description'] = $args['description'];
			}

			if (isset( $args['parent'] )) {
				$params['parent'] = $args['parent'];
			}

			$term = get_term_by( 'id', $args['id'], 'wplead_list_category', ARRAY_A );

			if ( $term ) {
				$term = wp_update_term( $args['id'], 'wplead_list_category', $params	);
			}

			if ( is_array($term) && isset( $term['term_id'] ) ) {
				return array( 'list_id' => $term['term_id'] );
			} else if ( is_numeric($term) ) {
				return array( 'list_id' => $term );
			} else {
				return $term;
			}
		}

		/**
		 *  Deletes a lead list
		 */
		public static function delete_lead_list( $id = null ) {

			/* id is required */
			if (!isset($id)) {
				return array( 'error' => __( 'must include an id parameter', 'inbound-pro' ) );
			}

			wp_delete_term( $id, 'wplead_list_category' );

			return array( 'message' => __( 'lead list deleted', 'inbound-pro' ) );
		}

		/**
		 *  Deletes a lead list
		 */


		/**
		* Get an array of all lead lists
		*
		* @returns ARRAY of lead lists with term id as key and list name as value
		*/
		public static function get_lead_lists_as_array() {
			self::register_taxonomies();

			$array = array();

			$args = array(
				'hide_empty' => false,
			);

			$terms = get_terms('wplead_list_category', $args);

			foreach ( $terms as $term	) {
				$array[$term->term_id] = $term->name;
			}

			return $array;
		}

		/**
		*  Get lead list infomration
		*
		*  @param STRING $search accepts 'id', 'slug', 'name' or 'term_taxonomy_id'
		*  @param INT $list_id
		*
		*  @returns ARRAY
		*/
		public static function get_lead_list_by( $search, $list_id ) {
			return  get_term_by( $search, $list_id, 'wplead_list_category', ARRAY_A);
		}

		/**
		 * Get lead status given lead id
		 * @dev
		 */
		public static function get_lead_status( $lead_id ) {
			$status = get_post_meta( $lead_id , 'wp_lead_status' , true );
			return ($status) ? $status : 'new';
		}

		/**
		 * Get lead status given lead id
		 * @dev
		 */
		public static function get_lead_statuses( ) {
			$default = array(
				'new' => array(
					'priority' => 1,
					'key' => 'new',
					'label' => __('New Lead' , 'inbound-pro'),
					'color' => '#f0ad4e',
					'nature' => 'core'
				),
				'read' => array(
					'priority' => 2,
					'key' => 'read',
					'label' => __('Read' , 'inbound-pro'),
					'color' => '#27ae60',
					'nature' => 'core'
				),
				'needs-attention' => array(
					'priority' => 3,
					'key' => 'needs-attention',
					'label' => __('Needs Attention' , 'inbound-pro'),
					'color' => '#ffcc33',
					'nature' => 'core'
				),
				'lost' => array(
					'priority' => 4,
					'key' => 'lost',
					'label' => __('Lost' , 'inbound-pro'),
					'color' => '#05022E',
					'nature' => 'core'
				),
				'active' => array(
					'priority' => 5,
					'key' => 'active',
					'label' => __('Active' , 'inbound-pro'),
					'color' => '#FE984E',
					'nature' => 'core'
				),
				'customer' => array(
					'priority' => 6,
					'key' => 'customer',
					'label' => __('Customer' , 'inbound-pro'),
					'color' => '#48F7A1',
					'nature' => 'core'
				),
				'archive' => array(
					'priority' => 7,
					'key' => 'archive',
					'label' => __('Archive' , 'inbound-pro'),
					'color' => '#7A3068',
					'nature' => 'core'
				),
				'double-optin' => array(
					'priority' => 7,
					'key' => 'double-optin',
					'label' => __('Waiting for Double Opt-in Confirmation' , 'inbound-pro'),
					'color' => '#0333b9',
					'nature' => 'core'
				),
			);

			return apply_filters('leads/statuses' , $default );
		}


		/**
		 * Get lead status count given status key
		 * @dev
		 */
		public static function get_status_lead_count( $status ) {

			$args = array(
				'post_type' => 'wp-lead',
				'meta_key' => 'wp_lead_status',
				'meta_value' => $status,
				'meta_compare' => '=',
				'posts_per_page' => -1
			);

			$query = new WP_Query( $args );

			return $query->post_count;
		}

		/**
		* Adds tag to lead
		*
		* @param lead_id INT
		* @param tag_id MIXED INT, STRING, ARRAY
		*
		*/
		public static function add_tag_to_lead( $lead_id, $tag , $append = true ) {
			wp_set_object_terms( $lead_id, $tag, 'lead-tags', $append );
		}

		/**
		* Remove tag from lead
		*
		* @param lead_id INT
		* @param tag_id MIXED INT,STRING,ARRAY
		*
		*/
		public static function remove_tag_from_lead( $lead_id, $list_id ) {
			wp_remove_object_terms( $lead_id, $list_id, 'lead-tags', true );
		}

		/**
		* Shows message to install leads when leads is not installed or activated
		*
		*/
		public static function install_leads_prompt() {
			global $post;

			if ( empty ( $post ) || 'wp-lead' !== get_post_type( $GLOBALS['post'] ) ) {
				return;
			}

			if (!defined('WPL_CURRENT_VERSION')) {
				_e( 'WordPress Leads is not currently installed/activated to view and manage leads please turn it on.', 'inbound-pro' );
			}
		}

		/**
		* Gets number of leads in list
		*
		* @param list_id INT of lead list taxonomy object
		*
		*/
		public static function get_leads_count_in_list( $list_id ) {

			$query = new WP_Query( array(
					'post_type' => 'wp-lead',
					'tax_query' => array (
						'relation' => 'AND',
						array (
							'taxonomy' => 'wplead_list_category' ,
							'field' => 'id' ,
							'terms' => array(	$list_id )
						)
					),
					'posts_per_page' => -1
			) );

			$count = $query->post_count;

			return sprintf( __( '%d leads', 'inbound-pro' ), $count );

		}

	}

	/**
	*  	Register 'wp-lead' CPT
	*/
	new Inbound_Leads();


}
