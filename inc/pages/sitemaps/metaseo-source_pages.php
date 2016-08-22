<div class="wpms_source wpms_source_pages">
    <div class="div_sitemap_check_all">
        <input type="checkbox" class="sitemap_check_all" data-type="pages"><?php _e('Check all pages', 'wp-meta-seo'); ?>
    </div>
    
    <div class="div_sitemap_check_all">
        <input type="checkbox" class="sitemap_check_all_posts_in_page" data-type="pages"><?php _e('Check all pages in current page', 'wp-meta-seo'); ?>
    </div>
    
    <div class="div_sitemap_check_all" style="font-weight: bold;">
        <?php _e('Public name' , 'wp-meta-seo'); ?>
        <input type="text" class="public_name_pages" value="<?php echo @$metaseo_sitemap->settings_sitemap['wpms_public_name_pages'] ?>">
    </div>
    
    <div class="div_sitemap_check_all" style="font-weight: bold;">
        <?php _e('Display in column' , 'wp-meta-seo'); ?>
        <select class="wpms_display_column wpms_display_column_pages">
            <?php 
                for($i = 1 ; $i <= $metaseo_sitemap->settings_sitemap['wpms_html_sitemap_column'] ; $i++){
                    echo '<option '.(selected($metaseo_sitemap->settings_sitemap['wpms_display_column_pages'], $i)).' value="'.$i.'">'.$metaseo_sitemap->columns[$i].'</option>';
                } 
            ?>
        </select>
    </div>
    <div id="wrap_sitemap_option_pages" class="wrap_sitemap_option">
        <h3><?php _e('Pages', 'wp-meta-seo') ?></h3>
        <?php
        $pages = get_pages();
        foreach ($pages as $page) {
            $select_priority = $metaseo_sitemap->wpms_view_select_priority('priority_pages_'.$page->ID,'_metaseo_settings_sitemap[wpms_sitemap_pages][' . $page->ID . '][priority]', @$metaseo_sitemap->settings_sitemap['wpms_sitemap_pages']->{$page->ID}->priority);
            $select_frequency = $metaseo_sitemap->wpms_view_select_frequency('frequency_pages_'.$page->ID,'_metaseo_settings_sitemap[wpms_sitemap_pages][' . $page->ID . '][frequency]', @$metaseo_sitemap->settings_sitemap['wpms_sitemap_pages']->{$page->ID}->frequency);
            echo '<div class="wpms_row">';
            echo '<div style="float:left;line-height:30px">';
            //echo '<input class="wpms_xmap_pages" name="_metaseo_settings_sitemap[wpms_sitemap_pages][' . $page->ID . '][post_id]" type="hidden" value="0">';
            if (isset($metaseo_sitemap->settings_sitemap['wpms_sitemap_pages']->{$page->ID}->post_id) && $metaseo_sitemap->settings_sitemap['wpms_sitemap_pages']->{$page->ID}->post_id == $page->ID) {
                echo '<input class="cb_sitemaps_pages wpms_xmap_pages" name="_metaseo_settings_sitemap[wpms_sitemap_pages][' . $page->ID . '][post_id]" checked type="checkbox" value="' . $page->ID . '">';
            } else {
                echo '<input class="cb_sitemaps_pages wpms_xmap_pages" name="_metaseo_settings_sitemap[wpms_sitemap_pages][' . $page->ID . '][post_id]" type="checkbox" value="' . $page->ID . '">';
            }

            echo $page->post_title;
            echo '</div>';
            echo '<div style="margin-left:200px">' . $select_priority . $select_frequency . '</div>';
            echo '</div>';
        }
        ?>
    </div>
    <div class="holder holder_pages"></div>
</div>