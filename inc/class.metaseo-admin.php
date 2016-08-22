<?php
//Main plugin functions here
class MetaSeo_Admin {
    
    private $options;
    private $pagenow;
    public static $desc_length = 156;
    public static $title_length = 69;
    
    function __construct() {
        $this->settings = array(
            "metaseo_title_home"=>"",
            "metaseo_desc_home"=>"",
	    "metaseo_showfacebook"=>"",
            "metaseo_showtwitter"=>"",
            "metaseo_twitter_card"=>"summary",
            "metaseo_showkeywords"=>0,
            "metaseo_showtmetablock"=>1,
            "metaseo_showsocial" => 1,
            "metaseo_seovalidate" => 0,
            "metaseo_linkfield" => 1,
            "metaseo_metatitle_tab" => 0,
            "metaseo_follow" => 0,
            "metaseo_index" => 0
	);
	$settings = get_option( '_metaseo_settings' );
        
	if(is_array($settings)){
	    $this->settings = array_merge($this->settings, $settings);
	}
        
        $this->pagenow = $GLOBALS['pagenow'];
        if((isset($this->settings['metaseo_showtmetablock']) && $this->settings['metaseo_showtmetablock'] == 1)){
            $this->load_meta_boxes();
        }
        
        add_action('admin_menu', array($this, 'register_menu_page'));

        /** Load admin js * */
        add_action('admin_enqueue_scripts', array($this, 'loadAdminScripts'));

        /** Load admin css  * */
        add_action('admin_init', array($this, 'addAdminStylesheets'));        
        $this->ajaxHandle();
        
        //register ajax update meta handler...
        add_action( 'wp_ajax_updateContentMeta', array($this, 'updateContentMeta_callback') );
        add_action('admin_init', array($this,'metaseo_field_settings'));
        
        add_action('admin_init', array($this,'metaseo_create_db'));
        
        add_action('init', array($this,'wpms_load_langguage'));
        
        if(!get_option('wpms_set_ignore', false)){
                add_option('wpms_set_ignore', 1, '', 'yes' );
        }
        
        add_action('wp_ajax_wpms_set_ignore',array($this,'wpms_set_ignore'));
        if ( '0' == get_option( 'blog_public' )) {
                add_action( 'admin_notices', array( $this, 'wpms_public_warning' ) );
        }
        add_action( 'wp_enqueue_editor', array( $this,'wpms_link_title_field'), 20 );
        add_action( 'post_updated', array('MetaSeo_Broken_Link_Table','wpms_update_post'), 10, 3 );
        add_action( 'delete_post', array('MetaSeo_Broken_Link_Table','wpms_delete_post' ));
        add_action( 'edit_comment', array('MetaSeo_Broken_Link_Table','wpms_update_comment') );
        add_action( 'deleted_comment', array('MetaSeo_Broken_Link_Table','wpms_deleted_comment') );
    }
     
    function metaseo_create_db(){
        global $wpdb;
        $option_v = 'metaseo_db_version2.2.0';
        $db_installed = get_option( $option_v, false);
        
        if(!$db_installed){
            // create table wpms_links
            $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wpms_links`(
                    `id` int(20) unsigned NOT NULL AUTO_INCREMENT,
                    `link_url` text CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
                    `link_final_url` text CHARACTER SET latin1 COLLATE latin1_general_cs,
                    `link_url_redirect` text CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
                    `link_text` text NOT NULL DEFAULT '',
                    `source_id` int(20) DEFAULT '0',
                    `type` varchar(100) DEFAULT '',
                    `status_code` varchar(100) DEFAULT '',
                    `status_text` varchar(250) DEFAULT '',
                    `hit` int(20) NOT NULL DEFAULT '1',
                    `redirect` tinyint(1) NOT NULL DEFAULT '0',
                    `broken_indexed` tinyint(1) unsigned NOT NULL DEFAULT '0',
                    `broken_internal` tinyint(1) unsigned NOT NULL DEFAULT '0',
                    `warning` tinyint(1) unsigned NOT NULL DEFAULT '0',
                    `dismissed` tinyint(1) NOT NULL DEFAULT '0',
                    PRIMARY KEY  (id))";


            require_once(ABSPATH.'wp-admin/includes/upgrade.php');
            dbDelta($sql);



                    $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '".$wpdb->prefix."wpms_links' AND column_name = 'follow'  AND TABLE_SCHEMA = '". $wpdb->dbname. "'" );

            if(empty($row)){
               $wpdb->query("ALTER TABLE ".$wpdb->prefix."wpms_links ADD follow tinyint(1) DEFAULT 1");
            }

            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '".$wpdb->prefix."wpms_links' AND column_name = 'meta_title' AND TABLE_SCHEMA = '". $wpdb->dbname. "'" );

            if(empty($row)){
               $wpdb->query("ALTER TABLE ".$wpdb->prefix."wpms_links ADD meta_title varchar(250) DEFAULT ''");
            }

            $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '".$wpdb->prefix."wpms_links' AND column_name = 'internal' AND TABLE_SCHEMA = '". $wpdb->dbname."'" );		        

            if(empty($row)){
               $wpdb->query("ALTER TABLE ".$wpdb->prefix."wpms_links ADD internal tinyint(1) DEFAULT 1");
            }
            
            // create page 404
            $sql = $wpdb->prepare("SELECT COUNT(*) FROM " . $wpdb->prefix . "posts WHERE post_title = %s AND post_excerpt = %s AND post_type = %s" , array("WP Meta SEO 404 Page","metaseo_404_page","page"));
            $post_if = $wpdb->get_var($sql);
            if($post_if < 1){
                $content = '<div class="wall" style="background-color: #F2F3F7; border: 30px solid #fff; width: 90%; height: 90%; margin: 0 auto;">

        <h1 style="text-align: center; font-family:\'open-sans\', arial; color: #444; font-size: 60px; padding: 50px;">ERROR 404 <br />-<br />NOT FOUND</h1>
    <p style="text-align: center; font-family:\'open-sans\', arial; color: #444; font-size: 40px; padding: 20px; line-height: 55px;">
    // You may have mis-typed the URL,<br />
    // Or the page has been removed,<br />
    // Actually, there is nothing to see here...</p>
        <p style="text-align: center;"><a style=" font-family:\'open-sans\', arial; color: #444; font-size: 20px; padding: 20px; line-height: 30px; text-decoration: none;" href="'.  get_home_url().'"><< Go back to home page >></a></p>
    </div>';
                $_page404 = array(
                    'post_title'    => 'WP Meta SEO 404 Page',
                    'post_content'  => $content,
                    'post_status'   => 'publish',
                    'post_excerpt'  => 'metaseo_404_page',
                    'post_type'   => 'page',
                );
                wp_insert_post( $_page404 );
            }

            $sql = $wpdb->prepare("SELECT COUNT(*) FROM " . $wpdb->prefix . "posts WHERE post_title = %s AND post_excerpt = %s AND post_type = %s" , array("WPMS HTML Sitemap","metaseo_html_sitemap","page"));
            $post_if = $wpdb->get_var($sql);
            if($post_if < 1){
                $_sitemap_page = array(
                    'post_title'    => 'WPMS HTML Sitemap',
                    'post_content'  => '',
                    'post_status'   => 'publish',
                    'post_excerpt'  => 'metaseo_html_sitemap',
                    'post_type'   => 'page',
                );
                wp_insert_post( $_sitemap_page );
            }
            
            update_option($option_v, true);
        }
    }
    
    function wpms_link_title_field() {
        if(isset($this->settings['metaseo_linkfield']) && $this->settings['metaseo_linkfield'] == 1){
            wp_enqueue_script( 'wpmslinkTitle', plugins_url('js/wpms-link-title-field.js', dirname(__FILE__)), array( 'wplink' ), WPMSEO_VERSION, true );
            wp_localize_script( 'wpmslinkTitle', 'wpmsLinkTitleL10n', array(
                    'titleLabel' => __( 'Title','wp-meta-seo' ),
            ) );
        }
    }
    
    function wpms_set_ignore(){
        update_option('wpms_set_ignore', 0);
        wp_send_json(true);
    }
    
    function wpms_public_warning() {
            if ( ( function_exists( 'is_network_admin' ) && is_network_admin() ) ) {
                    return;
            }
            
            if(get_option('wpms_set_ignore') == 0){
                return;
            }
            
            echo '<script type="text/javascript">'.PHP_EOL
		. 'function wpmsSetIgnore(option, hide, nonce){'.PHP_EOL
                    .'jQuery.post( ajaxurl, {'.PHP_EOL
                    .'action: "wpms_set_ignore"'.PHP_EOL
                    .'}, function( data ) {'.PHP_EOL
                            .'if ( data ) {'.PHP_EOL
                                .'jQuery( "#" + hide ).hide();'.PHP_EOL
                            . '}'.PHP_EOL
                    . '}'.PHP_EOL
                    . ');'.PHP_EOL
		. '}'.PHP_EOL
	    . '</script>';
            
            printf( '
                    <div id="robotsmessage" class="error">
                            <p>
                                    <strong>%1$s</strong>
                                    %2$s
                                    <a href="javascript:wpmsSetIgnore(\'wpms_public_warning\',\'robotsmessage\',\'%3$s\');" class="button">%4$s</a>
                            </p>
                    </div>',
                    __( 'Your website is not indexed by search engine because of your WordPress settings.', 'wp-meta-seo' ),
                    sprintf( __( '%sFix it now%s', 'wp-meta-seo' ), sprintf( '<a href="%s">', esc_url( admin_url( 'options-reading.php' ) ) ), '</a>' ),
                    esc_js( wp_create_nonce( 'wpseo-ignore' ) ),
                    __( 'OK I know that.', 'wp-meta-seo' )
            );
    }
    
    function wpms_load_langguage(){
        load_plugin_textdomain( 'wp-meta-seo', false, dirname( plugin_basename( WPMSEO_FILE ) ) . '/languages/' );
    }
    
    function metaseo_create_field($data_title,$alt,$dashicon,$label,$value_hidden){
        $output = '<div class="metaseo_analysis metaseo_tool" data-title="'.$data_title.'" alt="'.$alt.'"><i class="metaseo-dashicons material-icons dashicons-before" style="'.($dashicon == 'done'?'color:#46B450':'color:#FFB900').'">'.$dashicon.'</i>'.$label.'</div>';
        $output .= '<input type="hidden" class="wpms_analysis_hidden" name="wpms['.$data_title.']" value="'.$value_hidden.'">';
        return $output;
    }
    
    function metaseo_reload_analysis() {
        $tooltip_page = array();
        $tooltip_page['title_in_heading'] = __('Check if a word of this content title is also in a title heading (h1, h2...)','wp-meta-seo');
        $tooltip_page['title_in_content'] = __('Check if a word of this content title is also in the text','wp-meta-seo');
        $tooltip_page['page_url'] = __('Does the page title match with the permalink (URL structure)','wp-meta-seo');
        $tooltip_page['meta_title'] = __('Is the meta title of this page filled?','wp-meta-seo');
        $tooltip_page['meta_desc'] = __('Is the meta description of this page filled?','wp-meta-seo');
        $tooltip_page['image_resize'] = __('Check for image HTML resizing in content (usually image resized using handles)','wp-meta-seo');
        $tooltip_page['image_alt'] = __('Check for image Alt text and title','wp-meta-seo');
        if(empty($_POST['datas'])){
            wp_send_json(false);
        }
        
        if(isset($_POST['datas']['post_id'])){
            update_post_meta($_POST['datas']['post_id'], 'wpms_validate_analysis', '');
        }
        
        $check = 0;
        $output = '';
        
        // title heading
        $words_post_title = preg_split('~[^\p{L}\p{N}\']+~u',strtolower($_POST['datas']['title']));
        if($_POST['datas']['content'] == '') {
            $output .= $this->metaseo_create_field('heading_title',$tooltip_page['title_in_heading'],'warning',__('Page title word in content heading','wp-meta-seo'),0);
        }else{
            $dom = new DOMDocument;
            libxml_use_internal_errors( true );
            if ($dom->loadHTML('<div>'.html_entity_decode(stripcslashes($_POST['datas']['content'])).'</div>')) {
                // Extracting the specified elements from the web page
                $tags_h1 = $dom->getElementsByTagName('h1');
                $tags_h2 = $dom->getElementsByTagName('h2');
                $tags_h3 = $dom->getElementsByTagName('h3');
                $tags_h4 = $dom->getElementsByTagName('h4');
                $tags_h5 = $dom->getElementsByTagName('h5');
                $tags_h6 = $dom->getElementsByTagName('h6');

                $test = false;
                if(count($tags_h1) == 0 && count($tags_h2) == 0 && count($tags_h3) == 0 && count($tags_h4) == 0 && count($tags_h5) == 0 && count($tags_h6) == 0){
                    $test = false;
                }else{
                    if(!empty($tags_h1)) {
                        foreach($tags_h1 as $order => $tagh1){
                            $words_tagh1 = preg_split('~[^\p{L}\p{N}\']+~u',utf8_decode(strtolower($tagh1->nodeValue)));
                            if(is_array($words_tagh1) && is_array($words_post_title)){
                                foreach ($words_tagh1 as $mh){
                                    if(in_array($mh, $words_post_title) && $mh!=''){
                                        $test = true;
                                    }
                                }
                            }
                        }
                    }

                    if(!empty($tags_h2)) {
                        foreach($tags_h2 as $order => $tagh2){
                            $words_tagh2 = preg_split('~[^\p{L}\p{N}\']+~u',utf8_decode(strtolower($tagh2->nodeValue)));
                            if(is_array($words_tagh2) && is_array($words_post_title)){
                                foreach ($words_tagh2 as $mh){
                                    if(in_array($mh, $words_post_title) && $mh!=''){
                                        $test = true;
                                    }
                                }
                            }
                        }
                    }

                    if(!empty($tags_h3)) {
                        foreach($tags_h3 as $order => $tagh3){
                            $words_tagh3 = preg_split('~[^\p{L}\p{N}\']+~u',utf8_decode(strtolower($tagh3->nodeValue)));
                            if(is_array($words_tagh3) && is_array($words_post_title)){
                                foreach ($words_tagh3 as $mh){
                                    if(in_array($mh, $words_post_title) && $mh!=''){
                                        $test = true;
                                    }
                                }
                            }
                        }
                    }

                    if(!empty($tags_h4)) {
                        foreach($tags_h4 as $order => $tagh4){
                            $words_tagh4 = preg_split('~[^\p{L}\p{N}\']+~u',utf8_decode(strtolower($tagh4->nodeValue)));
                            if(is_array($words_tagh4) && is_array($words_post_title)){
                                foreach ($words_tagh4 as $mh){
                                    if(in_array($mh, $words_post_title) && $mh!=''){
                                        $test = true;
                                    }
                                }
                            }
                        }
                    }

                    if(!empty($tags_h5)) {
                        foreach($tags_h5 as $order => $tagh5){
                            $words_tagh5 = preg_split('~[^\p{L}\p{N}\']+~u',utf8_decode(strtolower($tagh5->nodeValue)));
                            if(is_array($words_tagh5) && is_array($words_post_title)){
                                foreach ($words_tagh5 as $mh){
                                    if(in_array($mh, $words_post_title) && $mh!=''){
                                        $test = true;
                                    }
                                }
                            }
                        }
                    }

                    if(!empty($tags_h6)) {
                        foreach($tags_h6 as $order => $tagh6){
                            $words_tagh6 = preg_split('~[^\p{L}\p{N}\']+~u',utf8_decode(strtolower($tagh6->nodeValue)));
                            if(is_array($words_tagh6) && is_array($words_post_title)){
                                foreach ($words_tagh6 as $mh){
                                    if(in_array($mh, $words_post_title) && $mh!=''){
                                        $test = true;
                                    }
                                }
                            }
                        }
                    }
                }

                if($test || (!empty($meta_analysis) && !empty($meta_analysis['heading_title']))){
                    $output .= $this->metaseo_create_field('heading_title',$tooltip_page['title_in_heading'],'done',__('Page title word in content heading','wp-meta-seo'),1);
                    $check ++;
                }else{
                    $output .= $this->metaseo_create_field('heading_title',$tooltip_page['title_in_heading'],'warning',__('Page title word in content heading','wp-meta-seo'),0);
                }

            }else{
                $output .= $this->metaseo_create_field('heading_title',$tooltip_page['title_in_heading'],'warning',__('Page title word in content heading','wp-meta-seo'),0);
            }
        }
        
        // title content
        $words_title = preg_split('~[^\p{L}\p{N}\']+~u',strtolower($_POST['datas']['title']));
        $words_post_content = preg_split('~[^\p{L}\p{N}\']+~u',strtolower($_POST['datas']['content'])); 
        
        $test1 = false;
        if(is_array($words_title) && is_array($words_post_content)){
            foreach ($words_title as $mtitle){
                if(in_array($mtitle, $words_post_content) && $mtitle != ''){
                    $test1 = true;
                    break;
                }
            }
        }else{
            $test1 = false;
        }
        
        if($test1){
            $output .= $this->metaseo_create_field('content_title',$tooltip_page['title_in_content'],'done',__('Page title word in content','wp-meta-seo'),1);
            $check ++;
        }else{
            $output .= $this->metaseo_create_field('content_title',$tooltip_page['title_in_content'],'warning',__('Page title word in content','wp-meta-seo'),0);
        }
        
        // page url matches page title
        $mtitle = $_POST['datas']['title'];
        if($_POST['datas']['mpageurl'] == sanitize_title($mtitle)){
            $output .= $this->metaseo_create_field('pageurl',$tooltip_page['page_url'],'done',__('Page url matches with page title','wp-meta-seo'),1);
            $check ++;
        }else{
            $output .= $this->metaseo_create_field('pageurl',$tooltip_page['page_url'],'warning',__('Page url matches with page title','wp-meta-seo'),0);
        }
        
        // meta title filled
        if(($_POST['datas']['meta_title'] != '' && strlen($_POST['datas']['meta_title']) <= self::$title_length)){
            $output .= $this->metaseo_create_field('metatitle',$tooltip_page['meta_title'],'done',__('Meta title filled','wp-meta-seo'),1);
            $check++;
        }else{
            $output .= $this->metaseo_create_field('metatitle',$tooltip_page['meta_title'],'warning',__('Meta title filled','wp-meta-seo'),0);
        }
        
        // desc filled
        if(($_POST['datas']['meta_desc'] != '' && strlen($_POST['datas']['meta_desc']) <= self::$desc_length)){
            $output .= $this->metaseo_create_field('metadesc',$tooltip_page['meta_desc'],'done',__('Meta description filled','wp-meta-seo'),1);
            $check++;
        }else{
            $output .= $this->metaseo_create_field('metadesc',$tooltip_page['meta_desc'],'warning',__('Meta description filled','wp-meta-seo'),0);
        }
        
        // image resize
        if($_POST['datas']['content'] == '') {
            $output .= $this->metaseo_create_field('imgresize',$tooltip_page['image_resize'],'done',__('Wrong image resize','wp-meta-seo'),1);
            $output .= $this->metaseo_create_field('imgalt',$tooltip_page['image_alt'],'done',__('Image have meta title or alt','wp-meta-seo'),1);
            $check += 2;
        }else{
            $dom = new DOMDocument;
            libxml_use_internal_errors( true );
            if ($dom->loadHTML('<div>'.html_entity_decode(stripcslashes($_POST['datas']['content'])).'</div>')) {
                // Extracting the specified elements from the web page
                $tags = $dom->getElementsByTagName('img');
                $img_wrong = false;
                $img_wrong_alt = false;
                foreach($tags as $order => $tag){
                    $src = $tag->getAttribute('src');
                    $b = strrpos($src, '-');
                    $e = strrpos($src, '.');
                    $string_wh = substr($src, $b+1 , $e-$b-1);
                    $array_wh = explode('x', $string_wh);
                    if(!empty($array_wh[0]) && !empty($array_wh[1])){
                        if(((int)$array_wh[0] != (int)$tag->getAttribute('width')) || ((int)$array_wh[1] != (int)$tag->getAttribute('height'))){
                            $img_wrong = true;
                        }
                    }
                    
                    $image_title = $tag->getAttribute('title');
                    $image_alt = $tag->getAttribute('alt');
                    if($image_title == '' || $image_alt == ''){
                        $img_wrong_alt = true;
                    }
                }
                
                if($img_wrong == false){
                    $output .= $this->metaseo_create_field('imgresize',$tooltip_page['image_resize'],'done',__('Wrong image resize','wp-meta-seo'),1);
                    $check++;
                }else{
                    $output .= $this->metaseo_create_field('imgresize',$tooltip_page['image_resize'],'warning',__('Wrong image resize','wp-meta-seo'),0);
                }
                
                if($img_wrong_alt == false){
                    $output .= $this->metaseo_create_field('imgalt',$tooltip_page['image_alt'],'done',__('Image have meta title or alt','wp-meta-seo'),1);
                    $check++;
                }else{
                    $output .= $this->metaseo_create_field('imgalt',$tooltip_page['image_alt'],'warning',__('Image have meta title or alt','wp-meta-seo'),0);
                }
            }else{
                $output .= $this->metaseo_create_field('imgresize',$tooltip_page['image_resize'],'warning',__('Wrong image resize','wp-meta-seo'),0);
                $output .= $this->metaseo_create_field('imgalt',$tooltip_page['image_alt'],'warning',__('Image have meta title or alt','wp-meta-seo'),0);
            }
        }
        
        $circliful = ceil(100*($check)/7);
        wp_send_json(array('circliful' => $circliful,'output' => $output ,'check' => $check));
        
    }
    
    function metaseo_validate_analysis(){
        $post_id = $_POST['post_id'];
        $key = 'wpms_validate_analysis';
        $analysis = get_post_meta($post_id, $key,true);
        if(empty($analysis) ) {
            $analysis = array();
        }
        
        $analysis[$_POST['field']] = 1;
        update_post_meta($post_id, $key, $analysis);
        wp_send_json(true);
    }
    
    function metaseo_update_link(){
        if (isset($_POST['link_id'])) {
            global $wpdb;
            $sql = $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "wpms_links WHERE id=%d", array($_POST['link_id']));
            $link_detail = $wpdb->get_row($sql);
            if (empty($link_detail))
                wp_send_json(false);

            $value = array('meta_title' => $_POST['meta_title']);
            $wpdb->update(
                    $wpdb->prefix . 'wpms_links', $value, array('id' => $_POST['link_id'])
            );


            $post = get_post($link_detail->source_id);
            if(!empty($post)){
                $old_value = $post->post_content;
                $edit_result = $this->wpms_edit_linkhtml($old_value,$link_detail->link_url,$link_detail->link_url,$_POST['meta_title']);
                $my_post = array(
                    'ID' => $link_detail->source_id,
                    'post_content' => $edit_result['content']
                );
                remove_action('post_updated', array('MetaSeo_Broken_Link_Table', 'wpms_update_post'));
                wp_update_post($my_post);
                wp_send_json(array('status' => true));
            }

        }
        wp_send_json(false);
    }    
    
    function metaseo_update_pageindex(){
        if(isset($_POST['page_id']) && isset($_POST['index'])){
            update_post_meta($_POST['page_id'], '_metaseo_metaindex', $_POST['index']);
            wp_send_json(array('status' => true));
        }
        wp_send_json(array('status' => false));
    }
    
    function metaseo_update_pagefollow(){
        if(isset($_POST['page_id']) && isset($_POST['follow'])){
            update_post_meta($_POST['page_id'], '_metaseo_metafollow', $_POST['follow']);
            wp_send_json(array('status' => true));
        }
        wp_send_json(array('status' => false));
    }
    
    function metaseo_update_follow(){
        if (isset($_POST['link_id'])) {
            $this->update_follow($_POST['link_id'],$_POST['follow']);
            wp_send_json(array('status' => true));
        }
        wp_send_json(array('status' => false));
    }
    
    function metaseo_update_multiplefollow(){
        global $wpdb;
        $follow_value = $_POST['follow_value'];
        $limit = 20;
        
        switch ($follow_value){
            case 'follow_selected':
                if (empty($_POST['linkids'])) wp_send_json(array('status' => true));
                $follow = 1;
                foreach ($_POST['linkids'] as $linkId){
                    $this->update_follow($linkId,$follow);
                }
                break;

            case 'follow_all':
                $follow = 1;
                $i = 0;
                $query = "SELECT * FROM " . $wpdb->prefix . "wpms_links WHERE follow=0 AND type='url'";
                $links = $wpdb->get_results($query);
                foreach ($links as $link){
                    if($i > $limit){
                        wp_send_json(array('status' => false,'message' => 'limit'));
                    }else{
                        $this->update_follow($link->id,$follow);
                        $i++;
                    }
                }
                
                break;

            case 'nofollow_selected':
                $follow = 0;
                if (empty($_POST['linkids'])) wp_send_json(array('status' => true));
                foreach ($_POST['linkids'] as $linkId){
                    $this->update_follow($linkId,$follow);
                }
                break;

            case 'nofollow_all':
                $follow = 0;
                $i = 0;
                $query = "SELECT * FROM " . $wpdb->prefix . "wpms_links WHERE follow=1 AND type='url'";
                $links = $wpdb->get_results($query);
                foreach ($links as $link){
                    if($i>$limit){
                        wp_send_json(array('status' => false,'message' => 'limit'));
                    }else{
                        $this->update_follow($link->id,$follow);
                        $i++;
                    }
                }
                break;
        }
        wp_send_json(array('status' => true));
    }
    
    function update_follow($linkId,$follow){
        global $wpdb;
        $sql = $wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "wpms_links WHERE id=%d", array($linkId));
        $link_detail = $wpdb->get_row($sql);
        if (empty($link_detail))
            wp_send_json(array('status' => false));

        $value = array('follow' => $follow);
        $wpdb->update(
                $wpdb->prefix . 'wpms_links', $value, array('id' => $linkId)
        );

        $post = get_post($link_detail->source_id);
        if(!empty($post)){
            $old_value = $post->post_content;
            $edit_result = $this->wpms_edit_linkhtml($old_value,$link_detail->link_url,$link_detail->link_url,$link_detail->meta_title,$follow);
            $my_post = array(
                'ID' => $link_detail->source_id,
                'post_content' => $edit_result['content']
            );
            remove_action('post_updated', array('MetaSeo_Broken_Link_Table', 'wpms_update_post'));
            wp_update_post($my_post);
        }
    }
    
    public function wpms_edit_linkhtml($content, $new_url, $old_url,$meta_title,$follow, $new_text = null) {
        //Save the old & new URLs for use in the edit callback.
        $args = array(
            'old_url' => $old_url,
            'new_url' => $new_url,
            'new_text' => $new_text,
            'meta_title' => $meta_title,
            'follow' => $follow
        );

        //Find all links and replace those that match $old_url.
        $content = MetaSeo_Broken_Link_Table::wpms_multi_edit($content, array('MetaSeo_Broken_Link_Table', 'wpms_edithtml_callback'), $args);

        $result = array(
            'content' => $content,
            'raw_url' => $new_url,
        );
        if (isset($new_text)) {
            $result['link_text'] = $new_text;
        }
        return $result;
    }
    
    function wpms_save_settings404(){
        if(isset($_POST['wpms_redirect'])){
            update_option('wpms_settings_404', $_POST['wpms_redirect']);
            wp_send_json(true);
        }
    }
            
    function metaseo_field_settings(){
        register_setting('Wp Meta SEO','_metaseo_settings');
        add_settings_section('metaseo_dashboard','',array( $this, 'showSettings' ),'metaseo_settings');  
        add_settings_field('metaseo_title_home', __('Homepage meta title', 'wp-meta-seo'), array( $this, 'metaseo_title_home' ), 'metaseo_settings', 'metaseo_dashboard' , array( 'label_for' => __('You can define your home page meta title in the content itself (a page, a post category…), if for some reason it’s not possible, use this setting' , 'wp-meta-seo') ));   
        add_settings_field('metaseo_desc_home', __('Homepage meta description', 'wp-meta-seo'), array( $this, 'metaseo_desc_home' ), 'metaseo_settings', 'metaseo_dashboard' , array( 'label_for' => __('You can define your home page meta description in the content itself (a page, a post category…), if for some reason it’s not possible, use this setting' , 'wp-meta-seo') ));   
        add_settings_field('metaseo_showfacebook', __('Facebook profile URL', 'wp-meta-seo'), array( $this, 'showfacebook' ), 'metaseo_settings', 'metaseo_dashboard' , array( 'label_for' => __('Used as profile in case of social sharing content on Facebook' , 'wp-meta-seo') ));   
        add_settings_field('metaseo_showtwitter', __('Twitter Username', 'wp-meta-seo'), array( $this, 'showtwitter' ), 'metaseo_settings', 'metaseo_dashboard' , array( 'label_for' => __('Used as profile in case of social sharing content on Twitter' , 'wp-meta-seo') ));   
        add_settings_field('metaseo_twitter_card', __('The default card type to use', 'wp-meta-seo'), array( $this, 'showtwittercard' ), 'metaseo_settings', 'metaseo_dashboard' , array( 'label_for' => __('Choose the Twitter card size generated when sharing a content' , 'wp-meta-seo') ));   
        add_settings_field('metaseo_metatitle_tab', __('Meta title as page title', 'wp-meta-seo'), array( $this, 'showmetatitletab' ), 'metaseo_settings', 'metaseo_dashboard' , array( 'label_for' => __('Usually not recommended as meta information is for search engines and content title for readers, but in some case... :)' , 'wp-meta-seo') ));   
        add_settings_field('metaseo_showkeywords', __('Meta keywords', 'wp-meta-seo'), array( $this, 'showkeywords' ), 'metaseo_settings', 'metaseo_dashboard' , array( 'label_for' => __('Not used directly by search engine to index content, but in some case it can be helpful (multilingual is an example)' , 'wp-meta-seo') ));   
        add_settings_field('metaseo_showtmetablock', __('Meta block edition', 'wp-meta-seo'), array( $this, 'showtmetablock' ), 'metaseo_settings', 'metaseo_dashboard' , array( 'label_for' => __('Load the onpage meta edition and analysis block' , 'wp-meta-seo') ));   
        add_settings_field('metaseo_showsocial', __('Social sharing block', 'wp-meta-seo'), array( $this, 'showsocial' ), 'metaseo_settings', 'metaseo_dashboard' , array( 'label_for' => __('Activate the custom social sharing tool, above the meta block' , 'wp-meta-seo') ));   
        add_settings_field('metaseo_seovalidate', __('Force SEO validation', 'wp-meta-seo'), array( $this, 'showseovalidate' ), 'metaseo_settings', 'metaseo_dashboard' , array( 'label_for' => __('Possibility to force a criteria validation in the content analysis tool' , 'wp-meta-seo') ));   
        add_settings_field('metaseo_linkfield', __('Link text field', 'wp-meta-seo'), array( $this, 'showlinkfield' ), 'metaseo_settings', 'metaseo_dashboard' , array( 'label_for' => __('Add the link title field in the text editor and in the bulk link edition view' , 'wp-meta-seo') ));   
        add_settings_field('metaseo_follow', __('Post/Page follow', 'wp-meta-seo'), array( $this, 'showfollow' ), 'metaseo_settings', 'metaseo_dashboard' , array( 'label_for' => __('Add an option to setup Follow/Nofollow instruction for each content' , 'wp-meta-seo') ));   
        add_settings_field('metaseo_index', __('Post/Page index', 'wp-meta-seo'), array( $this, 'showindex' ), 'metaseo_settings', 'metaseo_dashboard' , array( 'label_for' => __('Add an option to say to search engine: hey! Do not index this content' , 'wp-meta-seo') ));   
    }
    
    public function showmetatitletab(){
        echo '<input name="_metaseo_settings[metaseo_metatitle_tab]" type="hidden" value="0"/>';
        ?>
        <label><input name="_metaseo_settings[metaseo_metatitle_tab]" type="checkbox" id="metaseo_metatitle_tab" value="1" <?php checked( 1, $this->settings['metaseo_metatitle_tab']); ?> />
	<?php _e( 'When meta title is filled use it as page title instead of the content title','wp-meta-seo' ); ?></label>
        <?php
    }


    public function showSettings(){
	
    }
  
    public function metaseo_title_home(){
	$home_title = isset( $this->settings['metaseo_title_home'] ) ? $this->settings['metaseo_title_home'] : '';
	echo '<input id="metaseo_title_home" name="_metaseo_settings[metaseo_title_home]" type="text" value="'.esc_attr( $home_title ).'" size="50"/>';
    }
    
    public function metaseo_desc_home(){
	$home_desc = isset( $this->settings['metaseo_desc_home'] ) ? $this->settings['metaseo_desc_home'] : '';
	echo '<input id="metaseo_desc_home" name="_metaseo_settings[metaseo_desc_home]" type="text" value="'.esc_attr( $home_desc ).'" size="50"/>';
    }
    
    public function showkeywords(){
        echo '<input name="_metaseo_settings[metaseo_showkeywords]" type="hidden" value="0"/>';
        ?>
        <label><input name="_metaseo_settings[metaseo_showkeywords]" type="checkbox" id="metaseo_showkeywords" value="1" <?php checked( 1, $this->settings['metaseo_showkeywords']); ?> />
	<?php _e( 'Active meta keywords','wp-meta-seo'); ?></label>
        <?php
    }
    
    public function showtmetablock(){
	echo '<input name="_metaseo_settings[metaseo_showtmetablock]" type="hidden" value="0"/>';
        ?>
        <label><input name="_metaseo_settings[metaseo_showtmetablock]" type="checkbox" id="metaseo_showtmetablock" value="1" <?php checked( 1, $this->settings['metaseo_showtmetablock']); ?> />
	<?php _e( 'Activate meta block edition below content','wp-meta-seo' ); ?></label>
        <?php
    }
    
    public function showsocial(){
        echo '<input name="_metaseo_settings[metaseo_showsocial]" type="hidden" value="0"/>';
        ?>
        <label><input name="_metaseo_settings[metaseo_showsocial]" type="checkbox" id="metaseo_showsocial" value="1" <?php checked( 1, $this->settings['metaseo_showsocial']); ?> />
	<?php _e( 'Activate social edition','wp-meta-seo' ); ?></label>
        <?php
    }
    
    public function showseovalidate(){
        echo '<input name="_metaseo_settings[metaseo_seovalidate]" type="hidden" value="0"/>';
        ?>
        <label><input name="_metaseo_settings[metaseo_seovalidate]" type="checkbox" id="metaseo_seovalidate" value="1" <?php checked( 1, $this->settings['metaseo_seovalidate']); ?> />
	<?php _e( 'Allow user to force on page SEO criteria validation by clicking on the icon','wp-meta-seo' ); ?></label>
        <?php
    }
    
    public function showlinkfield(){
        echo '<input name="_metaseo_settings[metaseo_linkfield]" type="hidden" value="0"/>';
        ?>
        <label><input name="_metaseo_settings[metaseo_linkfield]" type="checkbox" id="metaseo_linkfield" value="1" <?php checked( 1, $this->settings['metaseo_linkfield']); ?> />
	<?php _e( "Adds back the missing 'title' field in the Insert/Edit URL box","wp-meta-seo" ); ?></label>
        <?php
    }
    
    public function showfollow(){
        echo '<input name="_metaseo_settings[metaseo_follow]" type="hidden" value="0"/>';
        ?>
        <label><input name="_metaseo_settings[metaseo_follow]" type="checkbox" id="metaseo_follow" value="1" <?php checked( 1, $this->settings['metaseo_follow']); ?> />
	<?php _e( "Provides a way for webmasters to tell search engines don't follow links on the page.","wp-meta-seo" ); ?></label>
        <?php
    }
    
    public function showindex(){
        echo '<input name="_metaseo_settings[metaseo_index]" type="hidden" value="0"/>';
        ?>
        <label><input name="_metaseo_settings[metaseo_index]" type="checkbox" id="metaseo_index" value="1" <?php checked( 1, $this->settings['metaseo_index']); ?> />
	<?php _e( "Provides show or do not show this page in search results in search results.","wp-meta-seo" ); ?></label>
        <?php
    }
        
    public function showfacebook(){
	$face = isset( $this->settings['metaseo_showfacebook'] ) ? $this->settings['metaseo_showfacebook'] : '';
	echo '<input id="metaseo_showfacebook" name="_metaseo_settings[metaseo_showfacebook]" type="text" value="'.esc_attr( $face ).'" size="50"/>';
    }
    
    public function showtwitter(){
	$twitter = isset( $this->settings['metaseo_showtwitter'] ) ? $this->settings['metaseo_showtwitter'] : '';
	echo '<input id="metaseo_showtwitter" name="_metaseo_settings[metaseo_showtwitter]" type="text" value="'.esc_attr( $twitter ).'" size="50"/>';
    }
    
    public function showtwittercard(){
	$twitter_card = isset( $this->settings['metaseo_twitter_card'] ) ? $this->settings['metaseo_twitter_card'] : 'summary';
        ?>
        <select class="select" name="_metaseo_settings[metaseo_twitter_card]" id="metaseo_twitter_card">
            <option <?php if($twitter_card == 'summary') echo 'selected' ?> value="summary"><?php _e( 'Summary', 'wp-meta-seo' ); ?></option>
            <option <?php if($twitter_card == 'summary_large_image') echo 'selected' ?> value="summary_large_image"><?php _e( 'Summary with large image', 'wp-meta-seo' ); ?></option>
        </select>
        <?php
    }
    
    private function load_meta_boxes() {
            if ( in_array( $this->pagenow, array(
                            'edit.php',
                            'post.php',
                            'post-new.php',
                    ) ) || apply_filters( 'wpmseo_always_register_metaboxes_on_admin', false )
            ) {
                require_once( WPMETASEO_PLUGIN_DIR . 'inc/class.metaseo-metabox.php' );
                $GLOBALS['wpmseo_metabox'] = new WPMSEO_Metabox;
            }
    }
    
    function stop_heartbeat() {
         global $pagenow;
         if ( 'post.php' != $pagenow && 'post-new.php' != $pagenow )        
            wp_deregister_script('heartbeat');
    }

    function updateContentMeta_callback() {
         global $wpdb;
         $_POST = stripslashes_deep( $_POST );
		 $response = new stdClass();
		 
		 if( !empty( $_POST['metakey'] ) && !empty( $_POST['postid'] ) && !empty( $_POST['value'] ) );
         $metakey = strtolower(trim($_POST['metakey']));
         $postID =  intval($_POST['postid']);
		 $value = trim($_POST['value']);
         
//		 if(preg_match('/[<>\/\'\"]+/', $value)){
//			$response->updated = false;
//			$response->msg = 'Meta content should not contains html tag or special char';
//			
//			echo json_encode($response);
//			wp_die();
//		}
         
        $response->msg = __('Modification was saved', 'wp-meta-seo') ;
        if($metakey == 'metatitle') {           
            if(!update_post_meta($postID, '_metaseo_metatitle', $value)) {
                $response->updated = false;
                $response->msg = __('Meta title was not saved', 'wp-meta-seo') ;
            }
                        else{
                               $response->updated = true;
               $response->msg = __('Meta title was saved', 'wp-meta-seo') ;
                        }          
                }

        if($metakey =='metadesc') {
            if(!update_post_meta($postID, '_metaseo_metadesc', $value)) {
                $response->updated = false;
                $response->msg = __('Meta description was not saved', 'wp-meta-seo') ;
            }else{
                $response->updated = true;
                $response->msg = __('Meta description was saved', 'wp-meta-seo') ;
            }        
        }
                
        if ($metakey == 'metakeywords') {
            if (!update_post_meta($postID, '_metaseo_metakeywords', $value)) {
                $response->updated = false;
                $response->msg = __('Meta keywords was not saved', 'wp-meta-seo');
            } else {
                $response->updated = true;
                $response->msg = __('Meta keywords was saved', 'wp-meta-seo');
            }
        }

        echo json_encode($response);
        wp_die();
    }
      
    /**
     * Loads js/ajax scripts
     * 
     */
    public function loadAdminScripts($hook) {
        global $current_screen;
        wp_enqueue_script('jquery');

        wp_enqueue_script(
                'wpmetaseoAdmin', plugins_url('js/metaseo_admin.js', dirname(__FILE__)), array('jquery'), WPMSEO_VERSION, true
        );
        
        if($current_screen->base == 'wp-meta-seo_page_metaseo_image_meta' || $current_screen->base == 'wp-meta-seo_page_metaseo_content_meta'){
            wp_enqueue_script('wpms-bulk', plugins_url('js/wpms-bulk-action.js', dirname(__FILE__)), array('jquery'), time(), true);
            wp_localize_script('wpms-bulk', 'wpmseobulkL10n', $this->meta_seo_localize_script());
        }
        
        if($current_screen->base == 'toplevel_page_metaseo_dashboard'){
            wp_enqueue_script('Chart', plugins_url('js/Chart.js', dirname(__FILE__)), array('jquery'), WPMSEO_VERSION, true);
            wp_enqueue_script('jquery-knob', plugins_url('js/jquery.knob.js', dirname(__FILE__)), array('jquery'), WPMSEO_VERSION, true);
            wp_enqueue_script('metaseo-dashboard', plugins_url('js/dashboard.js', dirname(__FILE__)), array('jquery'), WPMSEO_VERSION, true);
            wp_enqueue_style('chart', plugins_url('/css/chart.css', dirname(__FILE__) ) );
            wp_enqueue_style('metaseo-quirk', plugins_url('/css/metaseo-quirk.css', dirname(__FILE__) ) );
            wp_enqueue_style('metaseo-quirk', plugins_url('/css/metaseo-quirk.css', dirname(__FILE__) ) );
        }
        
        wp_register_style('m-style-qtip', plugins_url('css/jquery.qtip.css', dirname(__FILE__)), array(), WPMSEO_VERSION);
        wp_register_script('jquery-qtip', plugins_url('js/jquery.qtip.min.js', dirname(__FILE__)), array('jquery'), '2.2.1', true);
        
        wp_register_style('metaseo-google-icon', 'https://fonts.googleapis.com/icon?family=Material+Icons');
        if($current_screen->base == 'wp-meta-seo_page_metaseo_image_meta'){
            wp_enqueue_script('mautosize', plugins_url('js/autosize.js', dirname(__FILE__)), array('jquery'), '0.1', true);
        }
        // in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
	wp_localize_script( 'wpmetaseoAdmin', 'myAjax',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
    }

    public function meta_seo_localize_script() {
        return array(
            'metaseo_message_false_copy' => __( 'Warning, you\'re about to replace existing image alt or tile content, are you sire about that?', 'wp-meta-seo' ),
        );
    }
    
    /**
     * Load additional admin stylesheets
     * of jquery-ui
     *
     */
    
    function addAdminStylesheets() {   
        wp_enqueue_style('wpmetaseoAdmin', plugins_url('css/metaseo_admin.css', dirname(__FILE__)),array(), WPMSEO_VERSION);        
        wp_enqueue_style('tooltip-metaimage', plugins_url('/css/tooltip-metaimage.css',dirname(__FILE__)),array(), WPMSEO_VERSION);
        wp_enqueue_style('style', plugins_url('/css/style.css', dirname(__FILE__) ),array(), WPMSEO_VERSION);
    }

    function register_menu_page() {

        // Add main page
        $admin_page = add_menu_page(__('WP Meta SEO:', 'wp-meta-seo') . ' ' . __('Dashboard', 'wp-meta-seo'), __('WP Meta SEO', 'wp-meta-seo'), 'manage_options', 'metaseo_dashboard', array(
            $this,'load_page',) , 'dashicons-chart-area' );

        /**
         * Filter: 'metaseo_manage_options_capability' - Allow changing the capability users need to view the settings pages
         *
         * @api string unsigned The capability
         */
        $manage_options_cap = apply_filters('metaseo_manage_options_capability', 'manage_options');

        // Sub menu pages
        $submenu_pages = array(
            array(
                'metaseo_dashboard',
                '',
                __('Content meta', 'wp-meta-seo'),
                $manage_options_cap,
                'metaseo_content_meta',
                array($this, 'load_page'),
                null,
            ),
            array(
                'metaseo_dashboard',
                '',
                __('Sitemap', 'wp-meta-seo'),
                $manage_options_cap,
                'metaseo_google_sitemap',
                array($this, 'load_page'),
                null,
            ),
            array(
                'metaseo_dashboard',
                '',
                __('Image information', 'wp-meta-seo'),
                $manage_options_cap,
                'metaseo_image_meta',
                array($this, 'load_page'),
                null,
            ),
            array(
                'metaseo_dashboard',
                '',
                __('Image compression', 'wp-meta-seo'),
                $manage_options_cap,
                'metaseo_image_compression',
                array($this, 'load_page'),
                null,
            ),
            array(
                'metaseo_dashboard',
                '',
                __('Link editor', 'wp-meta-seo'),
                $manage_options_cap,
                'metaseo_link_meta',
                array($this, 'load_page'),
                null,
            ),
            array(
                'metaseo_dashboard',
                '',
                __('404 & Redirects', 'wp-meta-seo'),
                $manage_options_cap,
                'metaseo_broken_link',
                array($this, 'load_page'),
                null,
            ),
            array(
                'metaseo_dashboard',
                '',
                __('Settings', 'wp-meta-seo'),
                $manage_options_cap,
                'metaseo_settings',
                array($this, 'load_page'),
                null,
            ),
        );



        // Allow submenu pages manipulation
        $submenu_pages = apply_filters('metaseo_submenu_pages', $submenu_pages);

        // Loop through submenu pages and add them
        if (count($submenu_pages)) {
            foreach ($submenu_pages as $submenu_page) {

                // Add submenu page
                $admin_page = add_submenu_page($submenu_page[0], $submenu_page[2] . ' - ' . __('WP Meta SEO:', 'wp-meta-seo'), $submenu_page[2], $submenu_page[3], $submenu_page[4], $submenu_page[5]);

                // Check if we need to hook
                if (isset($submenu_page[6]) && null != $submenu_page[6] && is_array($submenu_page[6]) && count($submenu_page[6]) > 0) {
                    foreach ($submenu_page[6] as $submenu_page_action) {
                        add_action('load-' . $admin_page, $submenu_page_action);
                    }
                }
            }
        }

        global $submenu;
        if (isset($submenu['metaseo_dashboard']) && current_user_can($manage_options_cap)) {
            $submenu['metaseo_dashboard'][0][0] = __('Dashboard', 'wp-meta-seo');
        }
    }

    /**
     * Load the form for a WPSEO admin page
     */
    function load_page() {
        if (isset($_GET['page'])) {
            switch ($_GET['page']) {
                case 'metaseo_google_sitemap':
                    if (!class_exists('MetaSeo_Content_List_Table')) {
                        require_once( WPMETASEO_PLUGIN_DIR . '/inc/class.metaseo-sitemap.php' );
                    }
                
                    $metaseo_sitemap = new MetaSeo_Sitemap();
                    require_once( WPMETASEO_PLUGIN_DIR . 'inc/pages/sitemaps/metaseo-google-sitemap.php' );
                    break;
                case 'metaseo_image_compression':
                    require_once( WPMETASEO_PLUGIN_DIR . 'inc/pages/metaseo-image-compression.php' );
                    break;
                case 'metaseo_broken_link':
                    require_once( WPMETASEO_PLUGIN_DIR . 'inc/pages/metaseo-broken-link.php' );
                    break;
                case 'metaseo_settings':
                    require_once( WPMETASEO_PLUGIN_DIR . 'inc/pages/settings.php' );
                    break;
                
                case 'metaseo_content_meta':
                    require_once( WPMETASEO_PLUGIN_DIR . 'inc/pages/content-meta.php' );
                    break;


                case 'metaseo_image_meta':
                    require_once( WPMETASEO_PLUGIN_DIR . 'inc/pages/image-meta.php' );
                    break;
                
                case 'metaseo_link_meta':
                    require_once( WPMETASEO_PLUGIN_DIR . 'inc/pages/link-meta.php' );
                    break;
                
                case 'metaseo_image_optimize':
                    require_once( WPMETASEO_PLUGIN_DIR . 'inc/pages/image-optimize.php' );
                    break;

                case 'metaseo_dashboard':
                default:
                    require_once( WPMETASEO_PLUGIN_DIR . 'inc/pages/dashboard.php' );
                    break;
            }
        }
    }
    
    function wpms_ajax_check_exist(){
        if(isset($_POST['type'])){
            if($_POST['type'] == 'alt'){
                $margs = array(
                    'posts_per_page' => -1,'post_type' => 'attachment','post_status' => 'any',
                    'meta_query' => array(
                        'relation' => 'OR',

                        array(
                            'key' => '_wp_attachment_image_alt',
                            'value' => '',
                            'compare' => '!='
                        ),
                    )
                ); 

                $m_newquery = new WP_Query( $margs );
                $mposts_empty_alt = $m_newquery->get_posts();
                if(!empty($mposts_empty_alt)){
                    wp_send_json(true);
                }else{
                    wp_send_json(false);
                }
            }else{
                global $wpdb;
                $check_title = $wpdb->get_var("SELECT COUNT(posts.ID) as total FROM ".$wpdb->prefix."posts as posts WHERE posts.post_type = 'attachment' AND post_title != ''");
                if($check_title > 0){
                    wp_send_json(true);
                }else{
                    wp_send_json(false);
                }
            }
        }
        
    }
    
    function wpms_bulk_image_copy(){
        global $wpdb;
        if(empty($_POST['mtype'])) wp_send_json (false);
        if(isset($_POST['sl_bulk']) && $_POST['sl_bulk'] == 'all'){
            // select all
            $limit = 500;
            // check image alt and title empty
            $margs = array(
                'posts_per_page' => -1,'post_type' => 'attachment','post_status' => 'any'
            );  
                
            $m_newquery = new WP_Query( $margs );
            $mposts_empty_alt = $m_newquery->get_posts();
            $check_title = $wpdb->get_var("SELECT COUNT(posts.ID) as total FROM ".$wpdb->prefix."posts as posts WHERE posts.post_type = 'attachment' AND post_title = ''");
            
            // query attachment and update meta
            $total = $wpdb->get_var("SELECT COUNT(posts.ID) as total FROM ".$wpdb->prefix."posts as posts WHERE posts.post_type = 'attachment'");
            
            $j = ceil((int)$total/$limit);
            for($i = 0;$i<=$j; $i++){
                $ofset = $i*$limit;
                $attachments = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."posts as posts
                       WHERE posts.post_type = 'attachment' LIMIT $limit OFFSET $ofset");
                
                foreach ($attachments as $attachment){
                    $i_info_url = pathinfo($attachment->guid);
                    switch ($_POST['mtype']){
                        case 'image_alt':
                            update_post_meta($attachment->ID , '_wp_attachment_image_alt',$i_info_url['filename']);
                        break;
                            
                        case 'image_title':
                            wp_update_post(array('ID' => $attachment->ID,'post_title' => $i_info_url['filename']));
                        break;
                    }
                }
            }
            
            wp_send_json(true);
            
        }else{
            // selected
            if(isset($_POST['ids'])){
                $ids = $_POST['ids'];
                $margs = array(
                    'posts_per_page' => -1,
                    'post_type'        => 'attachment',
                    'post_status' => 'any',
                    'post__in' => $ids
                );  
                
                $query = "SELECT *
                        FROM $wpdb->posts
                        WHERE `post_type` = 'attachment'
                        AND `post_mime_type` LIKE '%image%' AND `ID` IN (".  implode(',', $ids).") 
                        ";
                
                $m_newquery = new WP_Query( $margs );
                $mposts_empty_alt = $m_newquery->get_posts();
                $mposts_empty_title = $wpdb->get_results($query);
                
                switch ($_POST['mtype']){
                    case 'image_alt':
                        if(!empty($mposts_empty_alt)){
                            foreach ($mposts_empty_alt as $post){
                                $i_info_url = pathinfo($post->guid);
                                update_post_meta($post->ID , '_wp_attachment_image_alt',$i_info_url['filename']);
                            }
                        }else{
                            wp_send_json(false);
                        }
                    break;

                    case 'image_title':
                        if(!empty($mposts_empty_title)){
                            foreach ($mposts_empty_title as $post){
                                $i_info_url = pathinfo($post->guid);
                                wp_update_post(array('ID' => $post->ID,'post_title' => $i_info_url['filename']));
                            }
                        }else{
                            wp_send_json(false);
                        }
                    break;
                }
                wp_send_json(true);
                
            }else{
                wp_send_json(false);
            }
        }
        
        
    }
    
    function wpms_bulk_post_copy_title(){
        $post_types = get_post_types( array('public' => true, 'exclude_from_search' => false) ) ;
        unset($post_types['attachment']);
        if(isset($_POST['sl_bulk']) && $_POST['sl_bulk'] == 'all'){
            $margs = array(
                'posts_per_page' => -1,
                'post_type'        => $post_types,
                'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash') ,
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => '_metaseo_metatitle',
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key' => '_metaseo_metatitle',
                        'value' => false,
                        'type' => 'BOOLEAN'
                    ),
            ));   
        }else{
            if(isset($_POST['ids'])){
                $ids = $_POST['ids'];
                $margs = array(
                    'posts_per_page' => -1,
                    'post_type'        => $post_types,
                    'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash') ,
                    'post__in' => $ids,
                    'meta_query' => array(
                        'relation' => 'OR',
                        array(
                            'key' => '_metaseo_metatitle',
                            'compare' => 'NOT EXISTS',
                        ),
                        array(
                            'key' => '_metaseo_metatitle',
                            'value' => false,
                            'type' => 'BOOLEAN'
                        ),
                ));  
            }else{
                wp_send_json(false);
            }
                
        }
        
        $m_newquery = new WP_Query( $margs );
        $mposts = $m_newquery->get_posts();
        if(!empty($mposts)){
            foreach ($mposts as $post){
                update_post_meta($post->ID , '_metaseo_metatitle',$post->post_title);
            }
            wp_send_json(true);
        }else{
                
            wp_send_json(false);
        }

        
    }
	
	private function ajaxHandle(){
            //
            add_action( 'wp_ajax_scanPosts', array('MetaSeo_Image_List_Table', 'scan_posts_callback') );
            add_action( 'wp_ajax_load_posts', array('MetaSeo_Image_List_Table', 'load_posts_callback') );
            add_action( 'wp_ajax_optimize_imgs', array('MetaSeo_Image_List_Table', 'optimizeImages') );
            add_action( 'wp_ajax_updateMeta', array('MetaSeo_Image_List_Table', 'updateMeta_callback') );
            add_action( 'wp_ajax_import_meta_data', array('MetaSeo_Content_List_Table', 'importMetaData') );
            add_action( 'wp_ajax_dismiss_import_meta', array('MetaSeo_Content_List_Table', 'dismissImport') );
            add_action('wp_ajax_wpms_bulk_post_copy',array($this,'wpms_bulk_post_copy_title'));
            add_action('wp_ajax_wpms_bulk_image_copy',array($this,'wpms_bulk_image_copy'));
            add_action('wp_ajax_wpms_ajax_check_exist',array($this,'wpms_ajax_check_exist'));
            add_action( 'added_post_meta' , array( 'MetaSeo_Content_List_Table', 'updateMetaSync' ), 99, 4);
            add_action( 'updated_post_meta', array( 'MetaSeo_Content_List_Table', 'updateMetaSync' ), 99, 4);
            add_action( 'deleted_post_meta', array( 'MetaSeo_Content_List_Table', 'deleteMetaSync' ), 99, 4);
            add_action('wp_ajax_metaseo_reload_analysis',array($this,'metaseo_reload_analysis'));
            add_action('wp_ajax_metaseo_validate_analysis',array($this,'metaseo_validate_analysis'));
            add_action('wp_ajax_metaseo_update_link',array($this,'metaseo_update_link'));
            add_action('wp_ajax_wpms_save_settings404',array($this,'wpms_save_settings404'));
            add_action('wp_ajax_wpms_update_link',array('MetaSeo_Broken_Link_Table','wpms_update_link'));
            add_action('wp_ajax_wpms_unlink',array('MetaSeo_Broken_Link_Table','wpms_unlink'));
            add_action('wp_ajax_wpms_recheck_link',array('MetaSeo_Broken_Link_Table','wpms_recheck_link'));
            add_action('wp_ajax_wpms_scan_link',array('MetaSeo_Broken_Link_Table','wpms_scan_link'));
            add_action('wp_ajax_wpms_flush_link',array('MetaSeo_Broken_Link_Table','wpms_flush_link'));
            add_action('wp_ajax_metaseo_update_follow',array($this,'metaseo_update_follow'));
            add_action('wp_ajax_metaseo_update_multiplefollow',array($this,'metaseo_update_multiplefollow'));
            add_action('wp_ajax_metaseo_update_pagefollow',array($this,'metaseo_update_pagefollow'));
            add_action('wp_ajax_metaseo_update_pageindex',array($this,'metaseo_update_pageindex'));
	}

}
