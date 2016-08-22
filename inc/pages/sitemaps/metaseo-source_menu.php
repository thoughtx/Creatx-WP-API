<div class="wpms_source wpms_source_menu">
<?php
    $terms = get_terms(array('taxonomy' => 'nav_menu' , 'hide_empty' => true , 'orderby' => 'term_id' , 'order' => 'ASC'));
    if(!empty($terms)){
        echo '<div class="div_sitemap_check_all">';
        echo '<input type="checkbox" class="sitemap_check_all" data-type="menu">';
        echo __('Check all menus','wp-meta-seo');
        echo '</div>';

        foreach ($terms as $term){
            $viewmenu = $metaseo_sitemap->wpms_view_menus($term);
        }
        echo '<div class="wrap_sitemap_option">';
        echo '<input name="_metaseo_settings_sitemap[wpms_check_firstsave]" type="hidden" value="1">';
        echo $viewmenu;
        echo '</div>';
    }
?>
</div>