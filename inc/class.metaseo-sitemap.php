<?php

class MetaSeo_Sitemap {

    public $html = '';
    public $wpms_sitemap_name = 'wpms-sitemap.xml';
    public $wpms_sitemap_default_name = 'sitemap.xml';
    public $columns = array('Zezo' , 'One' , 'Two' , 'Three');
    public $level = array();
    
    function __construct() {
        $this->settings_sitemap = array(
            "wpms_sitemap_add" => 0,
            "wpms_sitemap_root" => 0,
            "wpms_sitemap_author" => 0,
            "wpms_sitemap_taxonomies" => array(),
            "wpms_category_link" => array(),
            "wpms_html_sitemap_page" => 0,
            "wpms_html_sitemap_column" => 1,
            "wpms_html_sitemap_position" => "after",
            "wpms_display_column_menus" => (object)array(0),
            "wpms_display_column_posts" => 1,
            "wpms_display_column_pages" => 1,
        );

        if (is_multisite()) {
            $home_url = preg_replace("/[^a-zA-ZА-Яа-я0-9\s]/", "_", str_replace('http://', '', str_replace('https://', '', site_url())));
            $this->settings_sitemap['wpms_sitemap_link'] = site_url() . '/wpms-sitemap_' . $home_url . '.xml';
        } else {
            $this->settings_sitemap['wpms_sitemap_link'] = site_url() . '/'.$this->wpms_sitemap_name;
        }
        
        $settings = get_option('_metaseo_settings_sitemap');
        if (is_array($settings)) {
            $this->settings_sitemap = array_merge($this->settings_sitemap, $settings);
        }
        
        add_action('admin_init', array($this, 'metaseo_field_settings_sitemap'));
        add_action('admin_enqueue_scripts', array($this, 'metaseo_sitemap_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'site_metaseo_sitemap_scripts'));
        add_filter('the_content', array($this, 'wpms_html_sitemap_content'));
        add_shortcode('wpms_html_sitemap', array($this, 'wpms_sitemap_shortcode'));
        add_action('wp_ajax_wpms_regenerate_sitemaps',array($this,'wpms_regenerate_sitemaps'));
        add_action('wp_ajax_wpms_save_sitemap_settings',array($this,'wpms_save_sitemap_settings'));
    }
    
    public function site_metaseo_sitemap_scripts(){
        global $post;
        if(empty($post)) return;
        if(!empty($this->settings_sitemap) && $this->settings_sitemap['wpms_html_sitemap_page'] != $post->ID) return;
        wp_enqueue_script(
            'site-jPages-js', plugins_url('js/site-jPages.js', dirname(__FILE__)), array('jquery'), WPMSEO_VERSION, true
        );
        wp_localize_script('site-jPages-js', 'wpms_avarible', $this->wpms_localize_script());
        wp_enqueue_script(
            'jpage-js', plugins_url('js/jPages.js', dirname(__FILE__)), array('jquery'), WPMSEO_VERSION, true
        );
       
        wp_enqueue_style(
            'jpage-css', plugins_url('css/jPages.css', dirname(__FILE__)), array(), WPMSEO_VERSION
        );
    }
    
    public function wpms_localize_script(){
        $arrays = array('wpms_display_column_menus' => $this->settings_sitemap['wpms_display_column_menus']);
        return $arrays;
    }

    public function metaseo_sitemap_scripts(){
        global $current_screen;
        if(!empty($current_screen) && $current_screen->base != 'wp-meta-seo_page_metaseo_google_sitemap') return;
        wp_enqueue_script(
            'metaseositemap', plugins_url('js/metaseo_sitemap.js', dirname(__FILE__)), array('jquery'), WPMSEO_VERSION, true
        );
        
        wp_enqueue_script(
            'jpage-js', plugins_url('js/jPages.js', dirname(__FILE__)), array('jquery'), WPMSEO_VERSION, true
        );
        
        wp_enqueue_style(
            'metaseositemapstyle', plugins_url('css/metaseo_sitemap.css', dirname(__FILE__)), array(), WPMSEO_VERSION
        );
        
        wp_enqueue_style(
            'jpage-css', plugins_url('css/jPages.css', dirname(__FILE__)), array(), WPMSEO_VERSION
        );
        
        wp_enqueue_style('m-style-qtip', plugins_url('css/jquery.qtip.css', dirname(__FILE__)), array(), WPMSEO_VERSION);
        wp_enqueue_script('jquery-qtip', plugins_url('js/jquery.qtip.min.js', dirname(__FILE__)), array('jquery'), '2.2.1', true);
        
    }

    public function metaseo_field_settings_sitemap() {
        register_setting('MetaSEO Sitemap', '_metaseo_settings_sitemap');
        add_settings_section('metaseo_sitemap', '', array($this, 'showSettingSitemap'), 'metaseo_settings_sitemap');
        add_settings_field('wpms_sitemap_link', __('XML sitemap link', 'wp-meta-seo'), array($this, 'wpms_sitemap_link'), 'metaseo_settings_sitemap', 'metaseo_sitemap' , array( 'label_for' => __('Link to the xml file generated. It’s highly recommended to add this sitemap link to your Google search console' , 'wp-meta-seo') ));
        add_settings_field('wpms_html_sitemap_page', __('HTML Sitemap page', 'wp-meta-seo'), array($this, 'wpms_html_sitemap_page'), 'metaseo_settings_sitemap', 'metaseo_sitemap' , array( 'label_for' => __('A page is automatically generated to display your HTML sitemap. You can also use any of the existing pages' , 'wp-meta-seo') ));
        add_settings_field('wpms_sitemap_taxonomies', __('Additional content', 'wp-meta-seo'), array($this, 'wpms_sitemap_taxonomies'), 'metaseo_settings_sitemap', 'metaseo_sitemap' , array( 'label_for' => __('The additional WordPress taxonomies that you want to load in your sitemaps' , 'wp-meta-seo') ));
        add_settings_field('wpms_sitemap_author', __('Display author posts', 'wp-meta-seo'), array($this, 'wpms_sitemap_author'), 'metaseo_settings_sitemap', 'metaseo_sitemap' , array( 'label_for' => __('You can include a list of posts by author in your sitemaps' , 'wp-meta-seo') ));
        add_settings_field('wpms_html_sitemap_column', __('HTML Sitemap display', 'wp-meta-seo'), array($this, 'wpms_html_sitemap_column'), 'metaseo_settings_sitemap', 'metaseo_sitemap' , array( 'label_for' => __('Number of columns of the HTML sitemap. You can also setup where your content will be displayed using the tabs above' , 'wp-meta-seo') ));
        add_settings_field('wpms_html_sitemap_position', __('HTML Sitemap Position', 'wp-meta-seo'), array($this, 'wpms_html_sitemap_position'), 'metaseo_settings_sitemap', 'metaseo_sitemap');
        add_settings_field('wpms_sitemap_add', __('Sitemap and robot.txt', 'wp-meta-seo'), array($this, 'wpms_sitemap_add'), 'metaseo_settings_sitemap', 'metaseo_sitemap' , array( 'label_for' => __('You can include a link to your xml sitemap in the robot.txt. It helps some search engines to find it' , 'wp-meta-seo') ));
        add_settings_field('wpms_sitemap_root', __('Sitemap root', 'wp-meta-seo'), array($this, 'wpms_sitemap_root'), 'metaseo_settings_sitemap', 'metaseo_sitemap' , array( 'label_for' => __('Add a copy of the lastest version of your .xml sitemap at the root of your WordPress install named sitemap.xml. Some SEO tools and search engines bots are searching for it.' , 'wp-meta-seo') ));
    }

    public function showSettingSitemap() {
        
    }

    public function wpms_sitemap_link() {
        echo '<input id="wpms_check_firstsave" name="_metaseo_settings_sitemap[wpms_check_firstsave]" type="hidden" value="1">';
        $wpms_sitemap_link = isset($this->settings_sitemap['wpms_sitemap_link']) ? $this->settings_sitemap['wpms_sitemap_link'] : '';
        echo '<input readonly id="wpms_sitemap_link" name="_metaseo_settings_sitemap[wpms_sitemap_link]" type="text" value="' . esc_attr($wpms_sitemap_link) . '" size="50"/>';
        echo '<a class="button" href="' . $wpms_sitemap_link . '" target="_blank">' . __('Open', 'wp-meta-seo') . '</a>';
    }

    public function wpms_sitemap_add() {
        ?>
        <?php if (is_multisite()) { ?>
            <label><input id="wpms_sitemap_add" type='checkbox' disabled="disabled" name='_metaseo_settings_sitemap[wpms_sitemap_add]' value="1" <?php checked(1, $this->settings_sitemap['wpms_sitemap_add']); ?> /> <?php _e("add sitemap file path in robots.txt", 'wp-meta-seo'); ?></label>
            <p style="color:red"><?php _e("Since you are using multisiting, the plugin does not allow to add a sitemap to robots.txt", 'wp-meta-seo'); ?></div>
        <?php } else { ?>
            <!-- for robots.txt we need to use site_url instead home_url ! -->
            <label><input id="wpms_sitemap_add" type='checkbox' name='_metaseo_settings_sitemap[wpms_sitemap_add]' value="1" <?php checked(1, $this->settings_sitemap['wpms_sitemap_add']); ?> /> <?php _e("add sitemap link in the", 'wp-meta-seo'); ?> <a href="<?php echo site_url('/'); ?>robots.txt" target="_new">robots.txt</a></label>
            <?php
        }
    }
    
    public function wpms_sitemap_root() {
        ?>
        <!-- for robots.txt we need to use site_url instead home_url ! -->
        <label><input id="wpms_sitemap_root" type='checkbox' name='_metaseo_settings_sitemap[wpms_sitemap_root]' value="1" <?php checked(1, $this->settings_sitemap['wpms_sitemap_root']); ?> /> <?php _e("Add a sitemap.xml copy @ the site root", 'wp-meta-seo'); ?></label>
        <?php
    }
    
    public function wpms_sitemap_author() {
        ?>
        <!-- for robots.txt we need to use site_url instead home_url ! -->
        <label><input id="wpms_sitemap_author" type='checkbox' name='_metaseo_settings_sitemap[wpms_sitemap_author]' value="1" <?php checked(1, $this->settings_sitemap['wpms_sitemap_author']); ?> /> <?php _e("Display author post archive", 'wp-meta-seo'); ?></label>
        <?php
    }

    public function wpms_sitemap_taxonomies() {
        $wpms_taxonomies = array(
            'category' => 'Post category',
            'post_tag' => 'Post tag'
        );
        foreach ($wpms_taxonomies as $key => $value) {
        ?>
        <label><input class="wpms_sitemap_taxonomies" id="wpms_sitemap_taxonomies_<?php echo $key; ?>" type="checkbox" name="_metaseo_settings_sitemap[wpms_sitemap_taxonomies][]" value="<?php echo $key; ?>" <?php if (in_array($key, $this->settings_sitemap['wpms_sitemap_taxonomies'])) echo 'checked' ?>/><span style="padding-left: 5px;"><?php echo $value; ?></span></label><br />
            <?php
        }
    }

    public function wpms_html_sitemap_page() {
        global $wpdb;
        $pages = get_pages();
        $sql = $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "posts WHERE post_title = %s AND post_excerpt = %s AND post_type = %s", array("WPMS HTML Sitemap", "metaseo_html_sitemap", "page"));
        $sitemap_page = $wpdb->get_row($sql);

        if (empty($this->settings_sitemap['wpms_html_sitemap_page']) && !empty($sitemap_page))
            $this->settings_sitemap['wpms_html_sitemap_page'] = $sitemap_page->ID;
            ?>
                <select id="wpms_html_sitemap_page" name="_metaseo_settings_sitemap[wpms_html_sitemap_page]">
                    <option value="0"><?php _e('- Choose Your Sitemap Page -', 'wp-meta-seo') ?></option>
        <?php
        foreach ($pages as $page) {
            if ($this->settings_sitemap['wpms_html_sitemap_page'] == $page->ID) {
                echo '<option selected value="' . $page->ID . '">' . $page->post_title . '</option>';
            } else {
                echo '<option value="' . $page->ID . '">' . $page->post_title . '</option>';
            }
        }
        ?>
                </select>
        <?php
        if (!empty($this->settings_sitemap['wpms_html_sitemap_page'])) {
            echo '<a class="button" href="' . get_post_permalink($this->settings_sitemap['wpms_html_sitemap_page']) . '" target="_blank">' . __('Open', 'wp-meta-seo') . '</a>';
        }
    }

    public function wpms_html_sitemap_column() {
        ?>
        <select id="wpms_html_sitemap_column" name="_metaseo_settings_sitemap[wpms_html_sitemap_column]">
            <option <?php selected($this->settings_sitemap['wpms_html_sitemap_column'], 1) ?> value="1"><?php _e('1 column', 'wp-meta-seo') ?></option>
            <option <?php selected($this->settings_sitemap['wpms_html_sitemap_column'], 2) ?> value="2"><?php _e('2 column', 'wp-meta-seo') ?></option>
            <option <?php selected($this->settings_sitemap['wpms_html_sitemap_column'], 3) ?> value="3"><?php _e('3 column', 'wp-meta-seo') ?></option>
        </select>
        <?php
    }

    public function wpms_html_sitemap_position() {
        ?>
        <select id="wpms_html_sitemap_position" name="_metaseo_settings_sitemap[wpms_html_sitemap_position]">
            <option <?php selected($this->settings_sitemap['wpms_html_sitemap_position'], 'replace') ?> value="replace"><?php _e('Replace the Page Content', 'wp-meta-seo') ?></option>
            <option <?php selected($this->settings_sitemap['wpms_html_sitemap_position'], 'before') ?> value="before"><?php _e('Before Page Content', 'wp-meta-seo') ?></option>
            <option <?php selected($this->settings_sitemap['wpms_html_sitemap_position'], 'after') ?> value="after"><?php _e('After Page Content', 'wp-meta-seo') ?></option>
        </select>
        <?php
    }

    function wpms_get_path_filename_sitemap() {
        if (is_multisite()) {
            $home_url = preg_replace("/[^a-zA-ZА-Яа-я0-9\s]/", "_", str_replace('http://', '', str_replace('https://', '', site_url())));
            $xml_file = 'wpms-sitemap_' . $home_url . '.xml';
        } else {
            $xml_file = $this->wpms_sitemap_name;
        }
        $xml_path = ABSPATH . $xml_file;
        return array('path' => $xml_path, 'name' => $xml_file);
    }

    function metaseo_create_sitemap($sitemap_xml_name) {
        global $wpdb;
        $taxonomies = array();
        $list_links = array();
        foreach ($this->settings_sitemap['wpms_sitemap_taxonomies'] as $val) {
            $taxonomies[] = $val;
        }

        $xml = new DomDocument('1.0', 'utf-8');
        $home_url = site_url('/');
        $xml_stylesheet_path = ( defined('WP_CONTENT_DIR') ) ? $home_url . basename(WP_CONTENT_DIR) : $home_url . 'wp-content';
        $xml_stylesheet_path .= ( defined('WP_PLUGIN_DIR') ) ? '/' . basename(WP_PLUGIN_DIR) . '/wp-meta-seo/wpms-sitemap.xsl' : '/plugins/wp-meta-seo/sitemap.xsl';

        $xslt = $xml->createProcessingInstruction('xml-stylesheet', "type=\"text/xsl\" href=\"$xml_stylesheet_path\"");
        $xml->appendChild($xslt);
        $gglstmp_urlset = $xml->appendChild($xml->createElementNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'urlset'));

        /* add home page */
        $list_links[] = home_url('/');
        $url = $gglstmp_urlset->appendChild($xml->createElement('url'));
        $loc = $url->appendChild($xml->createElement('loc'));
        $loc->appendChild($xml->createTextNode(home_url('/')));
        $lastmod = $url->appendChild($xml->createElement('lastmod'));
        $lastmod->appendChild($xml->createTextNode(date('Y-m-d\TH:i:sP', time())));
        $changefreq = $url->appendChild($xml->createElement('changefreq'));
        $changefreq->appendChild($xml->createTextNode('monthly'));
        $priority = $url->appendChild($xml->createElement('priority'));
        $priority->appendChild($xml->createTextNode(1.0));
        
        // menus post custom
        $menus = $this->wpms_get_menus_all();
        $res = $menus['posts_custom'];
        if (!empty($res)) {
            foreach ($res as $val) {
                $sql = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_menu_item_object_id' AND meta_value=".$val->ID;
                $menu_object = $wpdb->get_results($sql);
                if(!empty($menu_object)){
                    foreach ($menu_object as $menu){
                        $menu_id = $menu->post_id;
                        $type = get_post_meta($menu_id, '_menu_item_type', true);
                        $check_type = get_post_meta($menu_id, '_menu_item_object',true);
                        $permalink = $this->wpms_get_permalink_sitemap($check_type,$val->ID);
                        if($permalink != '#'){
                            if(!in_array($permalink, $list_links)){
                                $list_links[] = $permalink;
                                if($type != 'taxonomy'){
                                    $gglstmp_url = $gglstmp_urlset->appendChild($xml->createElement('url'));
                                    $loc = $gglstmp_url->appendChild($xml->createElement('loc'));


                                    $loc->appendChild($xml->createTextNode($permalink));
                                    $lastmod = $gglstmp_url->appendChild($xml->createElement('lastmod'));
                                    $now = $val->post_modified;
                                    $date = date('Y-m-d\TH:i:sP', strtotime($now));
                                    $lastmod->appendChild($xml->createTextNode($date));
                                    $changefreq = $gglstmp_url->appendChild($xml->createElement('changefreq'));
                                    if(empty($this->settings_sitemap['wpms_check_firstsave'])){
                                        $changefreq->appendChild($xml->createTextNode('monthly'));
                                    }else{
                                        $changefreq->appendChild($xml->createTextNode(@$this->settings_sitemap['wpms_sitemap_menus']->{$menu_id}->frequency));
                                    }

                                    $priority = $gglstmp_url->appendChild($xml->createElement('priority'));
                                    if(empty($this->settings_sitemap['wpms_check_firstsave'])){
                                        $priority->appendChild($xml->createTextNode('1.0'));
                                    }else{
                                        $priority->appendChild($xml->createTextNode(@$this->settings_sitemap['wpms_sitemap_menus']->{$menu_id}->priority));
                                    }
                                }
                            }
                        }
                        
                        
                    }
                }
            }
        }
        
        // menus category
        $res = $menus['categories'];
        if (!empty($res)) {
            foreach ($res as $k => $val) {
                $sql = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_menu_item_object_id' AND meta_value=".$val;
                $menu_object = $wpdb->get_results($sql);
                if(!empty($menu_object)){
                    foreach ($menu_object as $menu){
                        $menu_id = $menu->post_id;
                        $type = get_post_meta($menu_id, '_menu_item_type', true);
                        $check_type = get_post_meta($menu_id, '_menu_item_object',true);
                        $permalink = get_term_link((int)$val,$check_type);
                        if(empty($permalink))  $permalink = get_permalink($menu_id);
                        if(!in_array($permalink, $list_links)){
                            $list_links[] = $permalink;
                            if($type == 'taxonomy'){
                                $gglstmp_url = $gglstmp_urlset->appendChild($xml->createElement('url'));
                                $loc = $gglstmp_url->appendChild($xml->createElement('loc'));
                                $loc->appendChild($xml->createTextNode($permalink));
                                $lastmod = $gglstmp_url->appendChild($xml->createElement('lastmod'));
                                $ps = get_post($menu_id);
                                
                                if(!empty($ps)){
                                    $now = $ps->post_modified;
                                    $date = date('Y-m-d\TH:i:sP', strtotime($now));
                                    $lastmod->appendChild($xml->createTextNode($date));
                                }

                                $changefreq = $gglstmp_url->appendChild($xml->createElement('changefreq'));
                                if(empty($this->settings_sitemap['wpms_check_firstsave'])){
                                    $changefreq->appendChild($xml->createTextNode('monthly'));
                                }else{
                                    $changefreq->appendChild($xml->createTextNode(@$this->settings_sitemap['wpms_sitemap_menus']->{$menu_id}->frequency));
                                }

                                $priority = $gglstmp_url->appendChild($xml->createElement('priority'));
                                if(empty($this->settings_sitemap['wpms_check_firstsave'])){
                                    $priority->appendChild($xml->createTextNode('1.0'));
                                }else{
                                    $priority->appendChild($xml->createTextNode(@$this->settings_sitemap['wpms_sitemap_menus']->{$menu_id}->priority));
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // posts 
        $res = $this->wpms_get_posts_sitemap();
        if (!empty($res)) {
            foreach ($res as $val) {
                $permalink = get_permalink($val->ID);
                if(!in_array($permalink, $list_links)){
                    $list_links[] = $permalink;
                    $gglstmp_url = $gglstmp_urlset->appendChild($xml->createElement('url'));
                    $loc = $gglstmp_url->appendChild($xml->createElement('loc'));
                    $loc->appendChild($xml->createTextNode($permalink));
                    $lastmod = $gglstmp_url->appendChild($xml->createElement('lastmod'));
                    $now = $val->post_modified;
                    $date = date('Y-m-d\TH:i:sP', strtotime($now));
                    $lastmod->appendChild($xml->createTextNode($date));
                    $changefreq = $gglstmp_url->appendChild($xml->createElement('changefreq'));
                    if(empty($this->settings_sitemap['wpms_check_firstsave'])){
                        $changefreq->appendChild($xml->createTextNode('monthly'));
                    }else{
                        $changefreq->appendChild($xml->createTextNode(@$this->settings_sitemap['wpms_sitemap_posts']->{$val->ID}->frequency));
                    }

                    $priority = $gglstmp_url->appendChild($xml->createElement('priority'));
                    if(empty($this->settings_sitemap['wpms_check_firstsave'])){
                        $priority->appendChild($xml->createTextNode('1.0'));
                    }else{
                        $priority->appendChild($xml->createTextNode(@$this->settings_sitemap['wpms_sitemap_posts']->{$val->ID}->priority));
                    }
                }
            }
        }

        // pages 
        $res = $this->wpms_get_pages_sitemap();
        if (!empty($res)) {
            $page_on_front = get_option( 'page_on_front' );
            foreach ($res as $val) {
                $permalink = get_permalink($val->ID);
                if(!in_array($permalink, $list_links)){
                    $list_links[] = $permalink;
                    $gglstmp_url = $gglstmp_urlset->appendChild($xml->createElement('url'));
                    $loc = $gglstmp_url->appendChild($xml->createElement('loc'));

                    $loc->appendChild($xml->createTextNode($permalink));
                    $lastmod = $gglstmp_url->appendChild($xml->createElement('lastmod'));
                    $now = $val->post_modified;
                    $date = date('Y-m-d\TH:i:sP', strtotime($now));
                    $lastmod->appendChild($xml->createTextNode($date));
                    $changefreq = $gglstmp_url->appendChild($xml->createElement('changefreq'));
                    if(empty($this->settings_sitemap['wpms_check_firstsave'])){
                        $changefreq->appendChild($xml->createTextNode('monthly'));
                    }else{
                        $changefreq->appendChild($xml->createTextNode(@$this->settings_sitemap['wpms_sitemap_pages']->{$val->ID}->frequency));
                    }
                    $priority = $gglstmp_url->appendChild($xml->createElement('priority'));
                    if(empty($this->settings_sitemap['wpms_check_firstsave'])){
                        $priority->appendChild($xml->createTextNode('1.0'));
                    }else{
                        $priority->appendChild($xml->createTextNode(@$this->settings_sitemap['wpms_sitemap_pages']->{$val->ID}->priority));
                    }
                }
            }
        }

        // ====
        if (!empty($taxonomies)) {
            foreach ($taxonomies as $value) {
                $terms = get_terms($value, 'hide_empty=1');
                if (!empty($terms) && !is_wp_error($terms)) {
                    foreach ($terms as $term_value) {
                        $permalink = get_term_link((int) $term_value->term_id, $value);
                        if(!in_array($permalink, $list_links)){
                            $list_links[] = $permalink;
                            $gglstmp_url = $gglstmp_urlset->appendChild($xml->createElement('url'));
                            $loc = $gglstmp_url->appendChild($xml->createElement('loc'));

                            $loc->appendChild($xml->createTextNode($permalink));
                            $lastmod = $gglstmp_url->appendChild($xml->createElement('lastmod'));

                            $now = $wpdb->get_var("SELECT `post_modified` FROM $wpdb->posts, $wpdb->term_relationships WHERE `post_status` = 'publish' AND `term_taxonomy_id` = " . $term_value->term_taxonomy_id . " AND $wpdb->posts.ID= $wpdb->term_relationships.object_id ORDER BY `post_modified` DESC");
                            $date = date('Y-m-d\TH:i:sP', strtotime($now));
                            $lastmod->appendChild($xml->createTextNode($date));
                            $changefreq = $gglstmp_url->appendChild($xml->createElement('changefreq'));
                            $changefreq->appendChild($xml->createTextNode('monthly'));
                            $priority = $gglstmp_url->appendChild($xml->createElement('priority'));
                            $priority->appendChild($xml->createTextNode(1.0));
                        }
                    }
                }
            }
        }

        $xml->formatOutput = true;

        if (!is_writable(ABSPATH))
            @chmod(ABSPATH, 0755);

        if (is_multisite()) {
            $home_url = preg_replace("/[^a-zA-ZА-Яа-я0-9\s]/", "_", str_replace('http://', '', str_replace('https://', '', site_url())));
            $xml->save(ABSPATH . 'sitemap_' . $home_url . '.xml');
        } else {
            $xml->save(ABSPATH . $sitemap_xml_name);
        }
        $this->wpms_sitemap_info();
    }
    
    public function wpms_get_permalink_sitemap($type,$id){
        if(isset($type) && $type == 'custom'){
            $permalink = get_post_meta($id, '_menu_item_url',true);
        }elseif($type == 'taxonomy'){
            $permalink = get_category_link($id);
        }else{
            $permalink = get_permalink($id);
        }
        return $permalink;
    }

    function wpms_sitemap_info() {
        $info_file = $this->wpms_get_path_filename_sitemap();
        $xml_url = site_url('/') . $info_file['name'];
        if (file_exists($info_file['path'])) {
            $this->settings_sitemap['sitemap'] = array(
                'file' => $info_file['name'],
                'path' => $info_file['path'],
                'loc' => $xml_url,
                'lastmod' => date('Y-m-d\TH:i:sP', filemtime($info_file['path']))
            );
            update_option('_metaseo_settings_sitemap', $this->settings_sitemap);
        }
    }
    
    public function wpms_display_column_posts() {
        $html = '';
        //if(!empty($this->settings_sitemap['wpms_sitemap_posts'])){
            $postsTest = get_posts();
            $postTitle = get_post_type_object('post');
            $postTitle = $postTitle->label;
            
            if (get_option('show_on_front') == 'page') {
                $postsURL = get_permalink(get_option('page_for_posts'));
                $postTitle = get_the_title(get_option('page_for_posts'));
            } else {
                $postsURL = get_bloginfo('url');
            }
            
            if(!empty($this->settings_sitemap['wpms_public_name_posts'])){
                $postTitle = $this->settings_sitemap['wpms_public_name_posts'];
            }
            $html .= '<div id="sitemap_posts" class="wpms_sitemap_posts"><h4>';
            if ($postsURL !== '' && $postsURL !== get_permalink(@$this->settings_sitemap['wpms_html_sitemap_page'])) {
                $html .= '<a href="' . $postsURL . '">' . $postTitle . '</a>';
            } else {
                $html .= $postTitle;
            }
            $html .= '</h4><ul>';
        

            //Categories
            $ids = array(0);
            if(!empty($this->settings_sitemap['wpms_sitemap_posts'])){
                foreach ((array)$this->settings_sitemap['wpms_sitemap_posts'] as $k => $v){
                    if(!empty($v->post_id)){
                        $ids[] = $k;
                    }
                }
            }
            
            $posts = array();
            $cats = get_categories();
            foreach ($cats as $cat) {
                if(in_array($cat->cat_ID, $this->settings_sitemap['wpms_category_link'])){
                    $cat_link = "<a href='" . esc_url(get_term_link($cat)) . "'>" . $cat->cat_name . "</a>";
                }else{
                    $cat_link = $cat->cat_name;
                }
                $html .= "<li class='wpms_li_cate'><div class='cat_name'>$cat_link</div>";
                if(!empty($this->settings_sitemap['wpms_sitemap_posts'])){
                    $html .= "<ul>";
                    query_posts(array('post__in' => $ids,'posts_per_page' => -1 , 'cat' => $cat->cat_ID ));
                    while (have_posts()) {
                        the_post();
                        if (( get_post_meta(get_the_ID(), '_yoast_wpseo_meta-robots-noindex', true) === '1' && get_post_meta(get_the_ID(), '_yoast_wpseo_sitemap-include', true) !== 'always' ) || ( get_post_meta(get_the_ID(), '_yoast_wpseo_sitemap-include', true) === 'never' ) || ( get_post_meta(get_the_ID(), '_yoast_wpms_redirect', true) !== '' )) {
                            continue;
                        }

                        $category = get_the_category();
                        // Only display a post link once, even if it's in multiple categories
                        if ($category[0]->cat_ID == $cat->cat_ID) {
                            $html .= '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
                        }
                    }
                    wp_reset_query();
                    $html .= "</ul>";
                }
                $html .= "</li>";
            }
            $html .= '</ul></div>';
        
        
            // ==============================================================================
            // Custom Post Types
            foreach (get_post_types(array('public' => true)) as $post_type) {
                //$postsTest = get_posts('post_type=' . $post_type);
                $args = array(
                    'posts_per_page' => -1,
                    'post_type' => $post_type,
                    'post__in' => $ids,
                    'post_status' => 'publish'
                );
                $query = new WP_Query($args);
                $postsTest = $query->get_posts();
                if (!empty($postsTest)) {
                    $checkSitemap = 'post_types-' . $post_type . '-not_in_sitemap';
                    if (( in_array($post_type, array('post', 'page', 'attachment')))) {
                        continue;
                    }
                    $postType = get_post_type_object($post_type);
                    $postTypeLink = get_post_type_archive_link($postType->name);
                    $html .= '<div id="sitemap_' . str_replace(' ', '', strtolower($postType->labels->name)) . '">';
                    if (!empty($postTypeLink)) {
                        $html .= '<h3><a href="' . $postTypeLink . '">' . $postType->labels->name . '</a></h3>';
                    } else {
                        $html .= '<h3>' . $postType->labels->name . '</h3>';
                    }
                    $html .= '<ul>';
                    foreach ($postsTest as $post){
                        if (( get_post_meta(get_the_ID(), '_yoast_wpseo_meta-robots-noindex', true) === '1' && get_post_meta(get_the_ID(), '_yoast_wpseo_sitemap-include', true) !== 'always' ) || ( get_post_meta(get_the_ID(), '_yoast_wpseo_sitemap-include', true) === 'never' ) || ( get_post_meta(get_the_ID(), '_yoast_wpms_redirect', true) !== '' )) {
                            continue;
                        }
                        $html .= '<li><a href="' . get_permalink($post->ID) . '">' . $post->post_title . '</a></li>';
                    }
                    $html .= '</ul></div>';
                }
            }
            $html .= '<div class="holder holder_sitemaps_posts"></div>';    
        //}
        
        return $html;
    }
    
    public function wpms_display_column_pages() {
        $html = '';
        if(!empty($this->settings_sitemap['wpms_sitemap_pages'])){
            $pageCheck = get_pages(array('exclude' => @$this->settings_sitemap['wpms_html_sitemap_page']));
            $pageTitle = get_post_type_object('page');
            $pageTitle = $pageTitle->label;
            if(!empty($this->settings_sitemap['wpms_public_name_pages'])){
                $pageTitle = $this->settings_sitemap['wpms_public_name_pages'];
            }
            $html .= '<div id="sitemap_pages" class="wpms_sitemap_pages"><h4>' . $pageTitle . '</h4>
                <ul>';
            $pageInc = '';
            $getPages = $this->wpms_get_pages_sitemap();
            foreach ($getPages as $page) {
                if ($page->ID !== @$this->settings_sitemap['wpms_html_sitemap_page']) {
                    if (( get_post_meta($page->ID, '_yoast_wpseo_meta-robots-noindex', true) === '1' && get_post_meta($page->ID, '_yoast_wpseo_sitemap-include', true) !== 'always' ) || ( get_post_meta($page->ID, '_yoast_wpseo_sitemap-include', true) === 'never' ) || ( get_post_meta($page->ID, '_yoast_wpms_redirect', true) !== '' )) {
                        continue;
                    }
                    if ($pageInc == '') {
                        $pageInc = $page->ID;
                        continue;
                    }
                    $pageInc .= ', ' . $page->ID;
                }
            }

            if($pageInc != ''){
                $html .= wp_list_pages(array('include' => $pageInc, 'title_li' => '', 'sort_column' => 'post_title', 'sort_order' => 'ASC', 'echo' => false));
            }

            $html .= '</ul></div>';
            $html .= '<div class="holder holder_sitemaps_pages"></div>';    
        }
        return $html;
    }

    public function wpms_sitemap_shortcode() {
        $html = '';
        // include style
        echo '<link rel="stylesheet" type="text/css" href="' . plugin_dir_url(dirname(__FILE__)) . 'css/html_sitemap.css" media="screen" />';
        $html .= '<div id="wpms_sitemap" class="columns_' . $this->settings_sitemap['wpms_html_sitemap_column'] . '">';
        
        
        $arrs = array("wpms_display_column_posts" , "wpms_display_column_pages");
        $checkColumn = array();
        for($i = 1 ; $i <= $this->settings_sitemap['wpms_html_sitemap_column'] ; $i++){
            $html .= '<div class="wpms_column wpms_column_'.$i.'" style="width:33%;float:left;">';
            if($i == 1){
                // Authors
                if($this->settings_sitemap['wpms_sitemap_author'] == 1){
                    $html .= '<div id="sitemap_authors"><h3>' . __('Authors') . '</h3>
                        <ul>';

                    $authEx = implode(", ", get_users('orderby=nicename&meta_key=wpms_excludeauthorsitemap&meta_value=on'));
                    $html .= wp_list_authors(array('exclude_admin' => false, 'exclude' => $authEx, 'echo' => false));
                    $html .= '</ul></div>';
                }
            }

            foreach ($arrs as $arr){
                if(empty($this->settings_sitemap[$arr])) $this->settings_sitemap[$arr] = 1;
                if(!in_array($arr, $checkColumn)){
                    if($i == (int)$this->settings_sitemap[$arr]){
                        $checkColumn[] = $arr;
                        $output = $this->{$arr}();
                        $html .= $output;
                    }
                }
            }
            
            
            
            $ids_menu = array(0);
            $check_menu = array();
            $terms = get_terms(array('taxonomy' => 'nav_menu' , 'hide_empty' => true));
            foreach ($terms as $term){
                $list_submenu_id = get_objects_in_term($term->term_id, 'nav_menu');
                $ids_menu = array_merge($ids_menu , $list_submenu_id);
            }
            
            if(empty($this->settings_sitemap['wpms_check_firstsave'])){
                $this->settings_sitemap['wpms_sitemap_menus'] = $ids_menu;
            }

            if(!empty($this->settings_sitemap['wpms_sitemap_menus'])){
                $terms = get_terms(array('taxonomy' => 'nav_menu' , 'hide_empty' => true));
                if(!empty($terms)){
                    
                    
                    foreach ($terms as $term){
                        if(!in_array('sitemap_menus_'.$term->term_id, $check_menu)){
                            if(empty($this->settings_sitemap['wpms_display_column_menus']->{$term->term_id})) $this->settings_sitemap['wpms_display_column_menus']->{$term->term_id} = 1;
                            if($i == (int)$this->settings_sitemap['wpms_display_column_menus']->{$term->term_id}){
                                
                                $check_menu[] = 'sitemap_menus_'.$term->term_id;
                                $html .= '<div id="sitemap_menus_'.$term->term_id.'" class="wpms_sitemap_menus">';
                                $viewmenu = $this->wpms_view_menus_frontend($term , $ids_menu);
                                $html .= $viewmenu;

                                $html .= '</div>';
                                $html .= '<div class="holder holder_sitemaps_menus_'.$term->term_id.'"></div>';    
                            }
                        }
                    }
                }
            }
            
            
            
            $html .= '</div>';
        }

        // ==============================================================================
       
        $html .= '</div>';
        $html .= '<div class="wpms_clearRow"></div>';
        
        return $html;
    }

    public function wpms_html_sitemap_content($content) {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "posts WHERE post_title = %s AND post_excerpt = %s AND post_type = %s" , array("WPMS HTML Sitemap","metaseo_html_sitemap","page"));
        $sitemap_page = $wpdb->get_row($sql);

        if(empty($this->settings_sitemap['wpms_html_sitemap_page']) && !empty($sitemap_page)) $this->settings_sitemap['wpms_html_sitemap_page'] = $sitemap_page->ID;
        if (!empty($this->settings_sitemap['wpms_html_sitemap_page']) && is_page($this->settings_sitemap['wpms_html_sitemap_page'])) {
            $sitemap = '[wpms_html_sitemap]';
            switch ($this->settings_sitemap['wpms_html_sitemap_position']) {
                case 'after':
                    $content .= $sitemap;
                    break;
                case 'before':
                    $content = $sitemap . $content;
                    break;
                case 'replace':
                    $content = $sitemap;
                    break;
                default :
                    $content .= $sitemap;
            }
        }
        return $content;
    }
    
    public function wpms_get_menus_all() {
        $settings_sitemap = get_option('_metaseo_settings_sitemap');
        $post_types = get_post_types( '', 'names' );
        unset( $gglstmp_result['revision'] );
        unset( $gglstmp_result['attachment'] );
        $ids_posts_custom = array(0);
        $ids_categories = array();
        
        if(empty($settings_sitemap['wpms_check_firstsave'])){
            $args = array(
                'posts_per_page' => -1,
                'post_type' => 'nav_menu_item',
                'post_status' => 'publish'
            );
            $query = new WP_Query($args);
            $posts_menu = $query->get_posts();
            foreach ($posts_menu as $k => $v){
                $type = get_post_meta($v->ID, '_menu_item_type', true);
                $type_menu = get_post_meta($v->ID, '_menu_item_object', true);
                $post_meta_object_id = get_post_meta($v->ID, '_menu_item_object_id',true);
                if($type != 'taxonomy'){
                    $ids_posts_custom[] = $post_meta_object_id;
                }else{
                    $ids_categories[] = $post_meta_object_id;
                }
            }
        }else{
            if(!empty($settings_sitemap['wpms_sitemap_menus'])){
                foreach ($settings_sitemap['wpms_sitemap_menus'] as $k => $v){
                    if(!empty($v->menu_id)){
                        $type = get_post_meta($k, '_menu_item_type', true);
                        $type_menu = get_post_meta($k, '_menu_item_object', true);
                        $post_meta_object_id = get_post_meta($k, '_menu_item_object_id',true);
                        if($type != 'taxonomy'){
                            $ids_posts_custom[] = $post_meta_object_id;
                        }else{
                            $ids_categories[] = $post_meta_object_id;
                        }
                    }
                }
            }
        }
       
        $args = array(
            'posts_per_page' => -1,
            'post_type' => $post_types,
            'post__in' => $ids_posts_custom,
            'post_status' => 'publish'
        );
        $query = new WP_Query($args);
        $menus_post_custom = $query->get_posts();
        return array('posts_custom' => $menus_post_custom , 'categories' => $ids_categories);
    }

    public function wpms_get_posts_sitemap() {
        $post_types = $this->wpms_get_post_type();
        $ids = array(0);
        $settings_sitemap = get_option('_metaseo_settings_sitemap');
        if(!empty($settings_sitemap['wpms_sitemap_posts'])){
            foreach ((array)$settings_sitemap['wpms_sitemap_posts'] as $k => $v){
                if(!empty($v->post_id)){
                    $ids[] = $k;
                }
            }
        }
        
        $args = array(
            'posts_per_page' => -1,
            'post_type' => $post_types,
            'post__in' => $ids,
            'post_status' => 'publish'
        );
        $query = new WP_Query($args);
        $posts = $query->get_posts();
        return $posts;
    }
    
    public function wpms_get_post_type(){
        $post_types = get_post_types(array('public' => true, 'exclude_from_search' => false));
        unset($post_types['attachment']);
        unset($post_types['page']);
        return $post_types;
    }

    public function wpms_get_pages_sitemap() {
        $ids = array(0);
        $settings_sitemap = get_option('_metaseo_settings_sitemap');
        if(!empty($settings_sitemap['wpms_sitemap_pages'])){
            foreach ($settings_sitemap['wpms_sitemap_pages'] as $k => $v){
                if(!empty($v->post_id)){
                    $ids[] = $k;
                }
            }
        }
        
        $args = array(
            'posts_per_page' => -1,
            'post_type' => 'page',
            'post__in' => $ids,
            'post_status' => 'publish'
        );
        $query = new WP_Query($args);
        $pages = $query->get_posts();
        return $pages;
    }

    public function wpms_get_posts() {
       // $taxonomies = get_taxonomies();
//        if(!empty($taxonomies['nav_menu'])){
//            unset($taxonomies['nav_menu']);
//        }
        $posts = array();
        $taxo = 'category';
        //foreach ($taxonomies as $taxo){
            $categorys = get_categories(array('hide_empty'=>false,'taxonomy'=>$taxo));
            
            foreach ($categorys as $cat) {
                $args = array(
                        'posts_per_page' => -1,
                        'tax_query' => array(
                                array(
                                        'taxonomy' => $taxo,
                                        'field'    => 'slug',
                                        'terms'    => $cat->slug,
                                ),
                        ),
                );
                $query = new WP_Query( $args );
                $results = $query->get_posts();
                if(!empty($results)){
                    $posts[$cat->cat_name.'||'.$cat->cat_ID.'||'.$taxo.'||'.$cat->slug] = $results;
                }
            }
        //}
        
        return $posts;
    }
    
    public function wpms_view_menus_frontend($term , $ids_menu) {
        $html = '';
        if(empty($this->settings_sitemap['wpms_check_firstsave'])){
            $list_menus = $ids_menu;
        }else{
            if(!empty($this->settings_sitemap['wpms_sitemap_menus'])){
                foreach ($this->settings_sitemap['wpms_sitemap_menus'] as $k => $v){
                    $list_menus[] = $k;
                }
            }
        }
        
        $args = array(
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'posts_per_page' => -1,
            'post_type' => 'nav_menu_item',
            'post_status' => 'any',
            'post__in' => $list_menus,
            'tax_query' => array(
                    array(
                            'taxonomy' => 'nav_menu',
                            'field'    => 'slug',
                            'terms'    => $term->slug,
                    ),
            ),
        );
        
        $query = new WP_Query($args);
        $submenus = $query->get_posts();
        $settings_sitemap = get_option('_metaseo_settings_sitemap');
        if(!empty($submenus)){
            $html .= '<h4>' . $term->name . '</h4>';
            $html .= '<ul class="wpms_frontend_menus_sitemap">';
            foreach ($submenus as $menu) {
                $type = get_post_meta($menu->ID, '_menu_item_type', true);
                $type_menu = get_post_meta($menu->ID, '_menu_item_object', true);
                $id_menu = get_post_meta($menu->ID, '_menu_item_object_id', true);
                $this->level[$menu->ID] = 0;
                $level = $this->wpms_count_parent($menu->ID);
                if($type == 'taxonomy'){
                    $post_submenu = get_post($menu->ID);
                    $title = $post_submenu->post_title;
                    if(empty($title)) {
                        $term = get_term($id_menu,$type_menu);
                        $title = $term->name;
                    }
                }else{
                    $post = get_post($menu->ID);
                    $title = $post->post_title;
                    if(empty($title)) {
                        $post_submenu = get_post($id_menu);
                        $title = $post_submenu->post_title;
                    }
                }
                $type = get_post_meta($menu->ID, '_menu_item_type', true);
                $check_type = get_post_meta($menu->ID, '_menu_item_object',true);
                $permalink = $this->wpms_get_permalink_sitemap($type,$id_menu);
                $margin = $level * 10;
                $style = '';
                if($level != 0)
                    $style = 'style="margin-left:'.$margin.'px"';
                $html .= '<li class="wpms_menu_level_'.$level.'" '.$style.'>';
                $html .= '<a href="'.$permalink.'">'.$title.'</a>';
                $html .= '</li>';
            }
        
            $html .= '</ul>';
        }
        return $html;
    }
    
    public function wpms_count_parent($menuID) {
        $parent = get_post_meta($menuID, '_menu_item_menu_item_parent', true);
        $parent_1 = get_post_meta($parent, '_menu_item_menu_item_parent', true);
        if(!empty($this->settings_sitemap['wpms_sitemap_menus']->{$parent}) && !empty($parent)){
            $this->level[$menuID]+=1;
            $this->loop_parent($parent,$this->level[$menuID],$menuID);
        }else{
            $this->loop_parent($parent,$this->level[$menuID],$menuID);
        }
        
        return (int)$this->level[$menuID];
    }
    
    public function loop_parent($menuID,$level,$menuIDroot) {
        $parent = get_post_meta($menuID, '_menu_item_menu_item_parent', true);
        $parent_1 = get_post_meta($parent, '_menu_item_menu_item_parent', true);
        if((!empty($this->settings_sitemap['wpms_sitemap_menus']->{$parent}) && !empty($parent)) || (!empty($parent_1) && !empty($parent)) ){
            $this->level[$menuIDroot]+=1;
            $this->loop_parent($parent,$this->level[$menuIDroot],$menuIDroot);
        }
    }
    
    public function wpms_view_menus($term) {
        $list_submenu_id = get_objects_in_term($term->term_id, 'nav_menu');
        $args = array(
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'posts_per_page' => -1,
            'post_type' => 'nav_menu_item',
            'post_status' => 'any',
            'post__in' => $list_submenu_id,
            'meta_key' => '_menu_item_menu_item_parent',
            'meta_value' => 0
        );
        $query = new WP_Query($args);
        $submenus = $query->get_posts();
        $settings_sitemap = get_option('_metaseo_settings_sitemap');
        
        $this->html .= '<h3><input type="checkbox" class="sitemap_check_all_posts_categories" data-category="nav_menu'.$term->slug.'">' . $term->name . '</h3>';
        
        $this->html .= __('Display in column' , 'wp-meta-seo');
        $this->html .= '<select class="wpms_display_column wpms_display_column_menus" data-menu_id="'.$term->term_id.'">';
        for($i = 1 ; $i <= $this->settings_sitemap['wpms_html_sitemap_column'] ; $i++){
            if(@$this->settings_sitemap['wpms_display_column_menus']->{$term->term_id} == $i){
                $this->html .= '<option selected value="'.$i.'">'.$this->columns[$i].'</option>';
            }else{
                $this->html .= '<option value="'.$i.'">'.$this->columns[$i].'</option>';
            }
            
        } 
        $this->html .= '</select>';
        foreach ($submenus as $menu) {
            $select_priority = $this->wpms_view_select_priority('priority_menu_'.$menu->ID,'_metaseo_settings_sitemap[wpms_sitemap_menus]['.$menu->ID.'][priority]' , @$this->settings_sitemap['wpms_sitemap_menus']->{$menu->ID}->priority);
            $select_frequency = $this->wpms_view_select_frequency('frequency_menu_'.$menu->ID,'_metaseo_settings_sitemap[wpms_sitemap_menus]['.$menu->ID.'][frequency]' , @$this->settings_sitemap['wpms_sitemap_menus']->{$menu->ID}->frequency);
            
            $type = get_post_meta($menu->ID, '_menu_item_type', true);
            $type_menu = get_post_meta($menu->ID, '_menu_item_object', true);
            $id_menu = get_post_meta($menu->ID, '_menu_item_object_id', true);
            if($type == 'taxonomy'){
                $post_submenu = get_post($menu->ID);
                $title = $post_submenu->post_title;
                if(empty($title)) {
                    $term = get_term($id_menu,$type_menu);
                    $title = $term->name;
                }
            }else{
                $post = get_post($menu->ID);
                $title = $post->post_title;
                if(empty($title)) {
                    $post_submenu = get_post($id_menu);
                    $title = $post_submenu->post_title;
                }
            }
            $level = 1;
            $this->html .= '<div class="wpms_row">';
            $this->html .= '<div style="float:left;line-height:30px">';
            if(empty($this->settings_sitemap['wpms_check_firstsave'])){
                $checkbox = '<input class="cb_sitemaps_menu wpms_xmap_menu nav_menu'.$term->slug.'" checked name="_metaseo_settings_sitemap[wpms_sitemap_menus]['.$menu->ID.'][menu_id]" type="checkbox" value="' . $menu->ID . '">';
            }else{
                if(isset($this->settings_sitemap['wpms_sitemap_menus']->{$menu->ID}->menu_id) && $this->settings_sitemap['wpms_sitemap_menus']->{$menu->ID}->menu_id == $menu->ID){
                    $checkbox = '<input class="cb_sitemaps_menu wpms_xmap_menu nav_menu'.$term->slug.'" checked name="_metaseo_settings_sitemap[wpms_sitemap_menus]['.$menu->ID.'][menu_id]" type="checkbox" value="' . $menu->ID . '">';
                }else{
                    $checkbox = '<input class="cb_sitemaps_menu wpms_xmap_menu nav_menu'.$term->slug.'" name="_metaseo_settings_sitemap[wpms_sitemap_menus]['.$menu->ID.'][menu_id]" type="checkbox" value="' . $menu->ID . '">';
                }
            }
            
            $this->html .= $checkbox . $title;
            $this->html .= '</div>';
            $this->html .= '<div style="margin-left:200px">'.$select_priority.$select_frequency.'</div>';
            $this->html .= '</div>';
            $this->html .= $this->wpms_loop($menu->ID, $level + 1,$this->settings_sitemap,$term);
        }
        
        return $this->html;
    }

    public function wpms_loop($menuID, $level,$settings_sitemap,$term) {
        global $wpdb;
        $args = array(
            'post_type' => 'nav_menu_item',
            'posts_per_page' => -1,
            'meta_key'   => '_menu_item_menu_item_parent',
            'meta_value' => $menuID,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        );
        $query = new WP_Query( $args );
        $submenus = $query->get_posts();
        if (!empty($submenus)) {
            foreach ($submenus as $submenu){
                $type = get_post_meta($submenu->ID, '_menu_item_type', true);
                $type_menu = get_post_meta($submenu->ID, '_menu_item_object', true);
                $post_subid = get_post_meta($submenu->ID, '_menu_item_object_id', true);
                if($type == 'taxonomy'){
                    $post_submenu = get_post($submenu->ID);
                    $title = $post_submenu->post_title;
                    if(empty($title)) {
                        $term = get_term($post_subid,$type_menu);
                        $title = $term->name;
                    }
                }else{
                    $post_submenu = get_post($submenu->ID);
                    $title = $post_submenu->post_title;
                    if(empty($title)) {
                        $post_submenu = get_post($post_subid);
                        $title = $post_submenu->post_title;
                    }
                }
                
                $space = '';
                for ($i = 1; $i <= $level * 3; $i++) {
                    $space .= '&nbsp;';
                }
                $select_priority = $this->wpms_view_select_priority('priority_menu_'.$submenu->ID,'_metaseo_settings_sitemap[wpms_sitemap_menus]['.$submenu->post_id.'][priority]' , @$settings_sitemap['wpms_sitemap_menus']->{$submenu->ID}->priority);
                $select_frequency = $this->wpms_view_select_frequency('frequency_menu_'.$submenu->ID,'_metaseo_settings_sitemap[wpms_sitemap_menus]['.$submenu->post_id.'][frequency]' , @$settings_sitemap['wpms_sitemap_menus']->{$submenu->ID}->frequency);

                if(empty($settings_sitemap['wpms_check_firstsave'])){
                    $checkbox = $space . '<input class="cb_sitemaps_menu wpms_xmap_menu nav_menu'.$term->slug.'" checked name="_metaseo_settings_sitemap[wpms_sitemap_menus]['.$submenu->ID.'][menu_id]" type="checkbox" value="' . $submenu->ID . '">';
                }else{
                    if(isset($settings_sitemap['wpms_sitemap_menus']->{$submenu->ID}->menu_id) && $settings_sitemap['wpms_sitemap_menus']->{$submenu->ID}->menu_id == $submenu->ID){
                        $checkbox = $space . '<input class="cb_sitemaps_menu wpms_xmap_menu nav_menu'.$term->slug.'" checked name="_metaseo_settings_sitemap[wpms_sitemap_menus]['.$submenu->ID.'][menu_id]" type="checkbox" value="' . $submenu->ID . '">';
                    }else{
                        $checkbox = $space . '<input class="cb_sitemaps_menu wpms_xmap_menu nav_menu'.$term->slug.'" name="_metaseo_settings_sitemap[wpms_sitemap_menus]['.$submenu->ID.'][menu_id]" type="checkbox" value="' . $submenu->ID . '">';
                    }
                }

                $this->html .= '<div class="wpms_row">';
                $this->html .= '<div style="float:left;line-height:30px">';
                $this->html .= $checkbox . $title;
                $this->html .= '</div>';
                $this->html .= '<div style="margin-left:200px">'.$select_priority.$select_frequency.'</div>';
                $this->html .= '</div>';
                $this->wpms_loop($submenu->ID, $level + 1,$settings_sitemap,$term);
            }
        }
    }
    
    public function wpms_regenerate_sitemaps(){
        $info_file = $this->wpms_get_path_filename_sitemap();
        $wpms_url_robot = ABSPATH . "robots.txt";
        $wpms_url_home = site_url('/');
        $xml_url = site_url('/') . $info_file['name'];
        $this->metaseo_create_sitemap($this->wpms_sitemap_name);
        if($this->settings_sitemap['wpms_sitemap_root'] == 1){
            $this->metaseo_create_sitemap($this->wpms_sitemap_default_name);
        }

        if (file_exists($wpms_url_robot) && !is_multisite()) {
            if (!is_writable($wpms_url_robot))
                @chmod($wpms_url_robot, 0755);
            if (is_writable($wpms_url_robot)) {
                $file_content = file_get_contents($wpms_url_robot);
                if (isset($this->settings_sitemap['wpms_sitemap_add']) && $this->settings_sitemap['wpms_sitemap_add'] == 1 && !preg_match('|Sitemap: ' . $wpms_url_home . $this->wpms_sitemap_name.'|', $file_content)) {
                    file_put_contents($wpms_url_robot, $file_content . "\nSitemap: " . $wpms_url_home . $this->wpms_sitemap_name);
                } elseif (preg_match("|Sitemap: " . $wpms_url_home . $this->wpms_sitemap_name."|", $file_content) && !isset($_POST['gglstmp_checkbox'])) {
                    $file_content = preg_replace("|\nSitemap: " . $wpms_url_home . $this->wpms_sitemap_name."|", '', $file_content);
                    file_put_contents($wpms_url_robot, $file_content);
                }
            } else {
                $error = __('Cannot edit "robots.txt". Check your permissions', 'wp-meta-seo');
                wp_send_json(array('status' => false , 'message' => $error));
            }
        }
        wp_send_json(array('status' => true , 'message' => 'success'));
    }
    
    public function wpms_view_select_priority($id,$name,$selected) {
        $values = array('1' => '100%','0.9' => '90%','0.8' => '80%','0.7' => '70%','0.6' => '60%','0.5' => '50%');
        $select = '<select id="'.$id.'" name="'.$name.'">';
        $select .= '<option value="1">'.__('Priority','wp-meta-seo').'</option>';
        foreach ($values as $k => $v){
            if($k == $selected){
                $select .= '<option selected value="'.$k.'">'.$v.'</option>';
            }else{
                $select .= '<option value="'.$k.'">'.$v.'</option>';
            }
        }
        $select .= '</select>';
        return $select;
    }
    
    public function wpms_view_select_frequency($id,$name,$selected) {
        $values = array('always' => 'Always','hourly' => 'Hourly','daily' => 'Daily','weekly' => 'Weekly','monthly' => 'Monthly','yearly' => 'Yearly','never' => 'Never');
        $select = '<select id="'.$id.'" name="'.$name.'">';
        $select .= '<option value="monthly">'.__('Frequency','wp-meta-seo').'</option>';
        foreach ($values as $k => $v){
            if($k == $selected){
                $select .= '<option selected value="'.$k.'">'.$v.'</option>';
            }else{
                $select .= '<option value="'.$k.'">'.$v.'</option>';
            }
        }
        $select .= '</select>';
        return $select;
    }
    
    public function wpms_save_sitemap_settings(){
        $settings_sitemap = get_option('_metaseo_settings_sitemap');
        $lists = array(
            "wpms_sitemap_add" => 0,
            "wpms_sitemap_root" => 0,
            "wpms_sitemap_author" => 0,
            "wpms_html_sitemap_page" => 0,
            "wpms_html_sitemap_column" => 1,
            "wpms_html_sitemap_position" => "after",
            "wpms_sitemap_taxonomies" => array(),
            "wpms_check_firstsave" => 0,
            "wpms_display_column_posts" => 1,
            "wpms_display_column_pages" => 1,
            "wpms_category_link" => array()
        );
        
        $wpms_display_column_menus = json_decode(stripslashes($_POST['wpms_display_column_menus']));
        if(!empty($wpms_display_column_menus)){
            $settings_sitemap['wpms_display_column_menus'] = $wpms_display_column_menus;
        }
        
        foreach ($lists as $k => $v){
            if(isset($_POST[$k])){
                $settings_sitemap[$k] = $_POST[$k];
            }else{
                $settings_sitemap[$k] = $lists[$k];
            }
        }
        
        $lists_selected = array(
            "wpms_sitemap_posts" => array(),
            "wpms_sitemap_pages" => array(),
            "wpms_sitemap_menus" => array()
        );
        
        foreach ($lists_selected as $k => $v){
            if(isset($_POST[$k]) && $_POST[$k] != '{}'){
                $values = json_decode(stripslashes($_POST[$k]));
                $settings_sitemap[$k] = $values;
            }else{
                $settings_sitemap[$k] = array();
            }
        }
        
        if(isset($_POST['wpms_public_name_posts'])) $settings_sitemap['wpms_public_name_posts'] = $_POST['wpms_public_name_posts'];
        if(isset($_POST['wpms_public_name_pages'])) $settings_sitemap['wpms_public_name_pages'] = $_POST['wpms_public_name_pages'];
        
        update_option('_metaseo_settings_sitemap', $settings_sitemap);
        
        wp_send_json(true);
    }
}
