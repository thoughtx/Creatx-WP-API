<div class="wpms_source wpms_source_posts">
    <div class="div_sitemap_check_all">
        <input type="checkbox" class="sitemap_check_all" data-type="posts"><?php _e('Check all posts', 'wp-meta-seo'); ?>
    </div>
    
    <div class="div_sitemap_check_all">
        <input type="checkbox" class="sitemap_check_all_posts_in_page" data-type="posts"><?php _e('Check all posts in current page', 'wp-meta-seo'); ?>
    </div>
    
    <div class="div_sitemap_check_all" style="font-weight: bold;">
        <?php _e('Public name' , 'wp-meta-seo'); ?>
        <input type="text" class="public_name_posts" value="<?php echo @$metaseo_sitemap->settings_sitemap['wpms_public_name_posts'] ?>">
    </div>
    
    <div class="div_sitemap_check_all" style="font-weight: bold;">
        <?php _e('Display in column' , 'wp-meta-seo'); ?>
        <select class="wpms_display_column wpms_display_column_posts">
            <?php 
                for($i = 1 ; $i <= $metaseo_sitemap->settings_sitemap['wpms_html_sitemap_column'] ; $i++){
                    echo '<option '.(selected($metaseo_sitemap->settings_sitemap['wpms_display_column_posts'], $i)).' value="'.$i.'">'.$metaseo_sitemap->columns[$i].'</option>';
                } 
            ?>
        </select>
    </div>
    <div id="wrap_sitemap_option_posts" class="wrap_sitemap_option">
        <?php
        $posts = $metaseo_sitemap->wpms_get_posts();
        $check = array();
        $desclink_category_add = __('Add link to category name' , 'wp-meta-seo');
        $desclink_category_remove = __('Remove link to category name' , 'wp-meta-seo');
        foreach ($posts as $key => $post) {
            $keys = explode('||', $key);
            if(!in_array($keys[2], $check)){
                $check[] = $keys[2];
                echo '<div class="wpms_row"><h1>' . $keys[2] . '</h1></div>';
            }
            
            if(in_array($keys[1], $metaseo_sitemap->settings_sitemap['wpms_category_link'])){
                echo '<div class="wpms_row"><h3><input for="'.$desclink_category_remove.'" type="checkbox" checked class="sitemap_addlink_categories" value="'.$keys[1].'">' . $keys[0] . '</h3></div>';
            }else{
                echo '<div class="wpms_row"><h3><input for="'.$desclink_category_add.'" type="checkbox" class="sitemap_addlink_categories" value="'.$keys[1].'">' . $keys[0] . '</h3></div>';
            }
            
            echo '<div class="wpms_row wpms_row_check_all_posts"><input type="checkbox" class="sitemap_check_all_posts_categories" data-category="'.$keys[2].$keys[3].'">'.__('Select all' , 'wp-meta-seo').'</div>';
            foreach ($post as $p) {
                $category = get_the_terms($p, $keys[2]);
                if($category[0]->term_id == $keys[1]){
                    $select_priority = $metaseo_sitemap->wpms_view_select_priority('priority_posts_'.$p->ID,'_metaseo_settings_sitemap[wpms_sitemap_posts][' . $p->ID . '][priority]', @$metaseo_sitemap->settings_sitemap['wpms_sitemap_posts']->{$p->ID}->priority);
                    $select_frequency = $metaseo_sitemap->wpms_view_select_frequency('frequency_posts_'.$p->ID,'_metaseo_settings_sitemap[wpms_sitemap_posts][' . $p->ID . '][frequency]', @$metaseo_sitemap->settings_sitemap['wpms_sitemap_posts']->{$p->ID}->frequency);
                    echo '<div class="wpms_row">';
                    echo '<div style="float:left;line-height:30px">';
                    //echo '<input class="wpms_xmap_posts" name="_metaseo_settings_sitemap[wpms_sitemap_posts][' . $p->ID . '][post_id]" type="hidden" value="0">';
                    
                    if (isset($metaseo_sitemap->settings_sitemap['wpms_sitemap_posts']->{$p->ID}->post_id) && $metaseo_sitemap->settings_sitemap['wpms_sitemap_posts']->{$p->ID}->post_id == $p->ID) {
                        echo '<input class="cb_sitemaps_posts wpms_xmap_posts '.$keys[2].$keys[3].'" name="_metaseo_settings_sitemap[wpms_sitemap_posts][' . $p->ID . '][post_id]" checked type="checkbox" value="' . $p->ID . '">';
                    } else {
                        echo '<input class="cb_sitemaps_posts wpms_xmap_posts '.$keys[2].$keys[3].'" name="_metaseo_settings_sitemap[wpms_sitemap_posts][' . $p->ID . '][post_id]" type="checkbox" value="' . $p->ID . '">';
                    }

                    echo $p->post_title;
                    echo '</div>';
                    echo '<div style="margin-left:200px">' . $select_priority . $select_frequency . '</div>';
                    echo '</div>';
                }
            }
        }
        ?>
    </div>
    <div class="holder holder_posts"></div>
</div>