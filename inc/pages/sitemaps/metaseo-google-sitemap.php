<div class="wrap wpms_wrap">
    <?php
        require_once( WPMETASEO_PLUGIN_DIR . 'inc/pages/sitemaps/sitemap_menus.php' );
    ?>
    <form method="post" id="wpms_xmap_form" action="">
        <input type="hidden" name="action" value="wpms_save_sitemap_settings">
    <?php
    //settings_fields( 'MetaSEO Sitemap' );
    echo '<div class="wpms_source wpms_source_sitemaps">';
    do_settings_sections('metaseo_settings_sitemap');
    echo '</div>';
    require_once( WPMETASEO_PLUGIN_DIR . 'inc/pages/sitemaps/metaseo-source_menu.php' );
    require_once( WPMETASEO_PLUGIN_DIR . 'inc/pages/sitemaps/metaseo-source_posts.php' );
    require_once( WPMETASEO_PLUGIN_DIR . 'inc/pages/sitemaps/metaseo-source_pages.php' );
    echo '<div class="div_wpms_save_sitemaps"><input type="button" class="button button-primary wpms_save_create_sitemaps" value="'.__('Regenerate and save sitemaps','wp-meta-seo').'"><span class="spinner spinner_save_sitemaps"></span></div>';
    //submit_button();
    ?>
    </form>
</div>