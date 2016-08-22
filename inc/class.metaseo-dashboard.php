<?php

/*
 * Comments to come later
 *
 *
 */

class MetaSeo_Dashboard {
    public static $meta_title_length = 69;
    public static $meta_desc_length = 156;
    
    public static $metatitle_filled = 0;
    public static $metadesc_filled = 0;
    public static $imageresizing_filled = 0;
    public static $imagemeta_filled = 0;
    public static $image_in_post = 0;
    public static $mpostname_inurl = 0;
    public static $mcategory_inurl = 0;
    public static $mpermalink = 50;
    public static $mlink_complete = 0;
    public static $mcount_link = 0;
    
    
    public static function moptimizationChecking() {
        global $wpdb;
        $imgs = 0;
        #$imgs_metas = 0;
        $imgs_metas = array('alt' => 0, 'title' => 0);
        $imgs_are_good = 0;
        $imgs_metas_are_good = array();
        $meta_keys = array('alt', 'title');
        $response = array(
            'imgs_statis' => array(0, 0),
            'imgs_metas_statis' => array(0, 0),
        );
        foreach ($meta_keys as $meta_key) {
            $imgs_metas_are_good[$meta_key] = 0;
            $imgs_metas_are_not_good[$meta_key] = 0;
        }

        $post_types = MetaSeo_Content_List_Table::get_post_types();
        $query = "SELECT `ID`, `post_title`, `post_content`, `post_type`, `post_date`
					FROM $wpdb->posts
					WHERE `post_type` IN ($post_types)
					AND `post_content` <> ''
					AND `post_content` LIKE '%<img%>%' 
					ORDER BY ID";

        $posts = $wpdb->get_results($query);
        if (count($posts) > 0) {
            $doc = new DOMDocument();
            libxml_use_internal_errors( true );
            $upload_dir = wp_upload_dir();

            foreach ($posts as $post) {
                $meta_analysis = get_post_meta($post->ID, 'wpms_validate_analysis',true);
                if(empty($meta_analysis)) $meta_analysis = array();
                $dom = $doc->loadHTML($post->post_content);
                $tags = $doc->getElementsByTagName('img');
                foreach ($tags as $tag) {
                    $img_src = $tag->getAttribute('src');

                    if (!preg_match('/\.(jpg|png|gif)$/i', $img_src, $matches)) {
                        continue;
                    }

                    $img_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $img_src);
                    if (!file_exists($img_path)) {
                        continue;
                    }

                    $width = $tag->getAttribute('width');
                    $height = $tag->getAttribute('height');
                    if (list($real_width, $real_height) = @getimagesize($img_path)) {
                        $ratio_origin = $real_width / $real_height;
                        //Check if img tag is missing with/height attribute value or not
                        if (!$width && !$height) {
                            $width = $real_width;
                            $height = $real_height;
                        } elseif ($width && !$height) {
                            $height = $width * (1 / $ratio_origin);
                        } elseif ($height && !$width) {
                            $width = $height * ($ratio_origin);
                        }

                        if (($real_width <= $width && $real_height <= $height) || (!empty($meta_analysis) && !empty($meta_analysis['imgresize']))) {
                            $imgs_are_good++;
                        }
                        foreach ($meta_keys as $meta_key) {

                            if (trim($tag->getAttribute($meta_key)) || (!empty($meta_analysis) && !empty($meta_analysis['imgalt']))) {
                                $imgs_metas_are_good[$meta_key] ++;
                            }
                        }
                    }

                    $imgs++;
                }
            }
            
            //Report analytic of images optimization
            $response['imgs_statis'][0] = $imgs_are_good;
            $response['imgs_statis'][1] = $imgs;
            $response['imgs_metas_statis'][0] = ceil(($imgs_metas_are_good['alt'] + $imgs_metas_are_good['title']) / 2);
            $response['imgs_metas_statis'][1] = $imgs;
        }

        return $response;
    }
    
    public function displayRank($url) {
        $rank = $this->getRank($url);
        if ($rank !== '') {
            echo $rank;
        } else {
            echo __('We can\'t get rank of this site from Alexa.com!', 'wp-meta-seo');
        }
    }

    public function getRank($url) {
        if (!function_exists('curl_version')) {
            if (!$content = @file_get_contents($url)) {
                return '';
            }
        } else {
            if (!is_array($url)) {
                $url = array($url);
            }
            $contents = $this->get_contents($url);
            $content = $contents[0];
        }
        
        $doc = new DOMDocument();
        libxml_use_internal_errors( true );
        @$doc->loadHTML($content);
        $doc->preserveWhiteSpace = false;

        $finder = new DOMXPath($doc);
        $classname = 'note-no-data';
        $nodes = $finder->query("//section[contains(@class, '$classname')]");
        if ($nodes->length < 1) {
            $classname = 'rank-row';
            $nodes = $finder->query("//div[contains(@class, '$classname')]");
        }

        $tmp_dom = new DOMDocument();
        foreach ($nodes as $key => $node) {
            $tmp_dom->appendChild($tmp_dom->importNode($node, true));
        }

        $html = trim($tmp_dom->saveHTML());
        $html = str_replace('We don\'t have', __('Alexa doesn\'t have','wp-meta-seo'), $html);
        $html = str_replace('Get Certified', '', $html);
        $html = str_replace('"/topsites/countries', '"http://www.alexa.com/topsites/countries', $html);
        return $html;
    }

    public function get_contents($urls) {
        $mh = curl_multi_init();
        $curl_array = array();
        $useragent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36';
        foreach ($urls as $i => $url) {
            $curl_array[$i] = curl_init($url);
            curl_setopt($curl_array[$i], CURLOPT_URL, $url);
            curl_setopt($curl_array[$i], CURLOPT_USERAGENT, $useragent); // set user agent
            curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, TRUE);
            //curl_setopt($curl_array[$i], CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($curl_array[$i], CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($curl_array[$i], CURLOPT_ENCODING, "UTF-8");
            curl_multi_add_handle($mh, $curl_array[$i]);
        }

        $running = NULL;
        do {
            usleep(10000);
            curl_multi_exec($mh, $running);
        } while ($running > 0);

        $contents = array();
        foreach ($urls as $i => $url) {
            $content = curl_multi_getcontent($curl_array[$i]);
            $contents[$i] = $content;
        }

        foreach ($urls as $i => $url) {
            curl_multi_remove_handle($mh, $curl_array[$i]);
        }
        curl_multi_close($mh);
        return $contents;
    }
     
    function evolutive_dashboard(){
        $post_types = get_post_types( array('public' => true, 'exclude_from_search' => false) ) ;
        if(!empty($post_types['attachment'])) unset($post_types['attachment']);
        $results = array('permalink_setting' => 50 , 'metatitle_filled' => array(0,array(0,0)) , 'metadesc_filled' => array(0,array(0,0)) , 'new_content' => array(0,array(0,0)) , 'link_meta' => array(0,array(0,0)));
        $args = array(
            'posts_per_page'   => -1,
            'post_type'        => $post_types,
            'suppress_filters' => true 
        );
        $mposts = get_posts( $args );
        if(empty($mposts)){
            return $results;
        }else{
            foreach ($mposts as $post){
                $meta_analysis = get_post_meta($post->ID, 'wpms_validate_analysis',true);
                if(empty($meta_analysis)) $meta_analysis = array();

                $meta_title = get_post_meta($post->ID, '_metaseo_metatitle', true);
                $meta_desc = get_post_meta($post->ID, '_metaseo_metadesc', true);
                if(($meta_title != '' && strlen($meta_title) <= self::$meta_title_length ) || (!empty($meta_analysis) && !empty($meta_analysis['metatitle']))){
                    self::$metatitle_filled++;
                }
                
                if(($meta_desc != '' && strlen($meta_desc) <= self::$meta_desc_length ) || (!empty($meta_analysis) && !empty($meta_analysis['metadesc'])) ){
                    self::$metadesc_filled++;
                }
                
                // get link meta
                
                $dom = new DOMDocument;
                libxml_use_internal_errors( true );
                if(isset($post->post_content) && $post->post_content != ''){
                    if ($dom->loadHTML($post->post_content)) {
                        $tags = $dom->getElementsByTagName('a');
                        foreach ($tags as $tag){
                            self::$mcount_link++;
                            $link_title = $tag->getAttribute('title');
                            if(isset($link_title) && $link_title != ''){
                                self::$mlink_complete++;
                            }
                        }
                        
                    }
                }
            }
            
            if(self::$mcount_link == 0){
                $link_percent = 100;
            }else{
                $link_percent = ceil(self::$mlink_complete / self::$mcount_link*100);
            }
            
            $results['metatitle_filled'] = array(ceil(self::$metatitle_filled/(count($mposts))*100) , array(self::$metatitle_filled , count($mposts)));
            $results['metadesc_filled'] = array(ceil(self::$metadesc_filled/(count($mposts))*100) , array(self::$metadesc_filled , count($mposts)));
            $results['link_meta'] = array($link_percent , array(self::$mlink_complete , self::$mcount_link));
        }
        
        $permalink_structure  = get_option('permalink_structure');
        if(strpos($permalink_structure, 'postname') == false && strpos($permalink_structure, 'category') == false){
            self::$mpermalink = 0;
        }else if(strpos($permalink_structure, 'postname') == true && strpos($permalink_structure, 'category') == true){
            self::$mpermalink = 100;
        }else if(strpos($permalink_structure, 'postname') == true || strpos($permalink_structure, 'category') == true){
            self::$mpermalink = 50;
        }
        
        $results['permalink_setting'] = self::$mpermalink;
        $newcontent_args = array(
            'date_query' => array(
                array(
                    'column' => 'post_modified_gmt',
//                    'before' => '1 month ago',
                    'after' => '30 days ago'
                )
            ),
            'posts_per_page' => -1,
            'post_type'        => array('post','page'),
        );
        
        $newcontent = new WP_Query( $newcontent_args );
        
        if(count($newcontent->get_posts()) >= 4){
            $count_new = 100;
        }else{
            $count_new = ceil(count($newcontent->get_posts()) / 4*100);
        }
        $results['new_content'] = array($count_new , array(count($newcontent->get_posts()) , count($mposts)));
        return $results;
    }
    
    function get_404_link(){
        global $wpdb;
        $sql = $wpdb->prepare( "SELECT COUNT(*) FROM ".$wpdb->prefix. "wpms_links WHERE (broken_internal=%d OR broken_indexed=%d) ",array(1, 1) );
        $count_404 = $wpdb->get_var($sql);
        
        $sql = $wpdb->prepare( "SELECT COUNT(*) FROM ".$wpdb->prefix. "wpms_links WHERE link_url_redirect != '' AND (broken_internal=%d OR broken_indexed=%d) ",array(1, 1) );
        $count_404_redirected = $wpdb->get_var($sql);
        if($count_404 != 0){
            $percent = ceil($count_404_redirected/$count_404*100);
        }else{
            $percent = 100;
        }
        return array('count_404' => $count_404 , 'count_404_redirected' => $count_404_redirected , 'percent' => $percent);
    }
    
    public function wpmf_getImages_optimizer(){
	global $wpdb;
        $query = 'SELECT distinct file FROM '.$wpdb->prefix.'wpio_images';
        $files = $wpdb->get_results($query);
        $image_optimize = 0;
        foreach ($files as $file){
            if(file_exists(str_replace('/', DIRECTORY_SEPARATOR, ABSPATH . $file->file))){
                $image_optimize++;
            }
        }
	return $image_optimize;
    }
    
    public function wpmf_getImages_count(){
        $wpio_settings = get_option('_wpio_settings');
        $include_folders = $wpio_settings['wpio_api_include'];
	$allowedPath = explode(',',$include_folders);
	$images = array();
        $image_optimize = $this->wpmf_getImages_optimizer();
        
        $allowed_ext = array('jpg','jpeg','jpe','gif','png','pdf');
        $min_size = (int)$wpio_settings['wpio_api_minfilesize'] *1024;   
        $max_size = (int)$wpio_settings['wpio_api_maxfilesize'] *1024; 
        if($max_size==0) $max_size = 5 * 1024 * 1024;
        $count_image = 0;
        $scan_dir = str_replace('/', DIRECTORY_SEPARATOR, ABSPATH) ; 
        foreach (new RecursiveIteratorIterator(new IgnorantRecursiveDirectoryIterator($scan_dir)) as $filename){
            if(!in_array(strtolower(pathinfo($filename,PATHINFO_EXTENSION)),$allowed_ext)){
                continue;
            }	

            $count_image++;
        }
        
        if($count_image == 0){
            $precent = 0;
        }else{
            $precent = ceil($image_optimize/$count_image*100);
        }
        return array('image_optimize' => $image_optimize, 'count_image' => $count_image , 'percent' => $precent);
    }
}
