<?php 
if (!class_exists('MetaSeo_Dashboard')) {
    require_once( WPMETASEO_PLUGIN_DIR . '/inc/class.metaseo-dashboard.php' );
}

wp_enqueue_style('m-style-qtip');
wp_enqueue_script('jquery-qtip');

$site_name = preg_replace('/(^(http|https):\/\/[w]*\.*)/', '', get_site_url());
//$site_name = 'testdev-united.com';
$url = 'http://www.alexa.com/siteinfo/' . $site_name;
$dashboard = new MetaSeo_Dashboard();
$results = $dashboard->evolutive_dashboard();
$link_errors = $dashboard->get_404_link();
$results_image = $dashboard->moptimizationChecking();
if(!empty($results_image['imgs_statis'][1])){
    $percent_iresizing = ceil($results_image['imgs_statis'][0]/$results_image['imgs_statis'][1]*100);
}else{
    $percent_iresizing = 100;
}

if(!empty($results_image['imgs_metas_statis'][1])){
    $percent_imeta = ceil($results_image['imgs_metas_statis'][0]/$results_image['imgs_metas_statis'][1]*100);
}else{
    $percent_imeta = 100;
}

$plugin_imgRecycle_file = 'imagerecycle-pdf-image-compression/wp-image-recycle.php';

?>
<h1 style="text-align: center;"><?php _e('WP Meta SEO dashboard', 'wp-meta-seo') ?></h1>
<div class="dashboard">
    <div class="col-md-9">
        <div class="row panel-statistics">
            <div class="col-sm-6 metaseo_tool" alt="<?php _e('It’s better using a permalink structure that is adding in your URL the category name and content title. This parameter can be changed in Settings > Permalinks WordPress menu. Tag recommended is %category%/%postname%','wp-meta-seo') ?>">
                <div class="panel panel-updates">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-7 col-lg-8">
                                <h4 class="panel-title text-success"><?php _e('Permalinks settings','wp-meta-seo') ?></h4>
                                <h3><?php echo $results['permalink_setting'].'%' ?></h3>
                                <div class="progress">
                                    <div style="width: <?php echo $results['permalink_setting'].'%' ?>" aria-valuemax="100" aria-valuemin="0" aria-valuenow="<?php echo $results['permalink_setting'] ?>" role="progressbar" class="progress-bar progress-bar-success">
                                        <span class="sr-only"><?php echo $results['permalink_setting'].'%' ?> Complete (success)</span>
                                    </div>
                                </div>
                                <p><?php _e('Optimized at','wp-meta-seo') ?>: <?php echo $results['permalink_setting'].'%' ?></p>
                            </div>
                            <div class="col-xs-5 col-lg-4 text-right">
                                <input type="text" value="<?php echo $results['permalink_setting'] ?>" class="dial-success">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 metaseo_tool-000" alt="<?php _e('Meta titles are displayed in search engine results as a page title. It’s a good thing for SEO to have some custom and attractive ones. Be sure to fill at least the met information on your most popular pages','wp-meta-seo') ?>">
                <div class="panel panel-danger-full panel-updates">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-7 col-lg-8">
                                <h4 class="panel-title text-warning"><?php _e('Meta Title','wp-meta-seo') ?></h4>
                                <h3><?php echo $results['metatitle_filled'][0].'%' ?></h3>
                                <div class="progress">
                                    <div style="width: <?php echo $results['metatitle_filled'][0].'%' ?>" aria-valuemax="100" aria-valuemin="0" aria-valuenow="<?php echo $results['metatitle_filled'][0] ?>" role="progressbar" class="progress-bar progress-bar-warning">
                                        <span class="sr-only"><?php echo $results['metatitle_filled'][0].'%' ?> Complete (success)</span>
                                    </div>
                                </div>
                                <p><?php _e('Meta title filled','wp-meta-seo') ?>: <?php echo $results['metatitle_filled'][1][0].'/'.$results['metatitle_filled'][1][1] ?></p>
                            </div>
                            <div class="col-xs-5 col-lg-4 text-right">
                                <input type="text" value="<?php echo $results['metatitle_filled'][0] ?>" class="dial-warning">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 metaseo_tool-000" alt="<?php _e('Meta descriptions are displayed in search engine results as a page description. It’s a good thing for SEO to have some custom and attractive ones. Be sure to fill at least the meta information on your most popular pages.','wp-meta-seo') ?>">
                <div class="panel panel-success-full panel-updates">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-7 col-lg-8">
                                <h4 class="panel-title text-success"><?php _e('Meta Description','wp-meta-seo') ?></h4>
                                <h3><?php echo $results['metadesc_filled'][0].'%' ?></h3>
                                <div class="progress">
                                    <div style="width: <?php echo $results['metadesc_filled'][0].'%' ?>" aria-valuemax="100" aria-valuemin="0" aria-valuenow="<?php echo $results['metadesc_filled'][0] ?>" role="progressbar" class="progress-bar progress-bar-info">
                                        <span class="sr-only"><?php echo $results['metadesc_filled'][0].'%' ?> Complete (success)</span>
                                    </div>
                                </div>
                                <p><?php _e('Meta description filled','wp-meta-seo') ?>: <?php echo $results['metadesc_filled'][1][0].'/'.$results['metadesc_filled'][1][1] ?></p>
                            </div>
                            <div class="col-xs-5 col-lg-4 text-right">
                                <input type="text" value="<?php echo $results['metadesc_filled'][0] ?>" class="dial-info">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 metaseo_tool" alt="<?php _e('Display image at its natural size, do not use HTML resize. It happens usually when you use handles to resize an image. You have a bulk edition tool to fix that.','wp-meta-seo') ?>">
                <div class="panel panel-updates">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-7 col-lg-8">
                                <h4 class="panel-title text-danger"><?php _e('HTML image resizing','wp-meta-seo') ?></h4>
                                <h3><?php echo $percent_iresizing.'%' ?></h3>
                                <div class="progress">
                                    <div style="width: <?php echo $percent_iresizing.'%' ?>" aria-valuemax="100" aria-valuemin="0" aria-valuenow="<?php echo $percent_iresizing ?>" role="progressbar" class="progress-bar progress-bar-danger">
                                        <span class="sr-only"><?php echo $percent_iresizing.'%' ?> Complete (success)</span>
                                    </div>
                                </div>
                                <p><?php _e('Wrong resized images','wp-meta-seo') ?>: <?php echo $results_image['imgs_statis'][0].'/'.$results_image['imgs_statis'][1] ?></p>
                            </div>
                            <div class="col-xs-5 col-lg-4 text-right">
                                <input type="text" value="<?php echo $percent_iresizing ?>" class="dial-danger">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-sm-6 metaseo_tool" alt="<?php _e('We recommend to use both alt text and image title. The main advantage is that it helps search engines discover your images and display them in image search results. Plus, these tags improve the accessibility of your site and give more information about your images. Use our bulk image tool to quickly check and fix that.','wp-meta-seo') ?>">
                <div class="panel panel-updates">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-7 col-lg-8">
                                <h4 class="panel-title text-success"><?php _e('Image title/alt','wp-meta-seo') ?></h4>
                                <h3><?php echo $percent_imeta.'%' ?></h3>
                                <div class="progress">
                                    <div style="width: <?php echo $percent_imeta.'%' ?>" aria-valuemax="100" aria-valuemin="0" aria-valuenow="<?php echo $percent_imeta ?>" role="progressbar" class="progress-bar progress-bar-success">
                                        <span class="sr-only"><?php echo $percent_imeta.'%' ?> Complete (success)</span>
                                    </div>
                                </div>
                                <p><?php _e('Image data filled (in content)','wp-meta-seo') ?>: <?php echo $results_image['imgs_metas_statis'][0].'/'.$results_image['imgs_metas_statis'][1] ?></p>
                            </div>
                            <div class="col-xs-5 col-lg-4 text-right">
                                <input type="text" value="<?php echo $percent_imeta ?>" class="dial-success">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-sm-6 metaseo_tool-000" alt="<?php _e('It is highly recommended to update or add new content on your website quite frequently. At least 3 updated or new content per month would be great :)','wp-meta-seo') ?>">
                <div class="panel panel-danger-full panel-updates">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-7 col-lg-8">
                                <h4 class="panel-title text-warning"><?php _e('New or updated content','wp-meta-seo') ?></h4>
                                <h3><?php echo $results['new_content'][0].'%' ?></h3>
                                <div class="progress">
                                    <div style="width: <?php echo $results['new_content'][0].'%' ?>" aria-valuemax="100" aria-valuemin="0" aria-valuenow="<?php echo $results['new_content'][0] ?>" role="progressbar" class="progress-bar progress-bar-warning">
                                        <span class="sr-only"><?php echo $results['new_content'][0].'%' ?> Complete (success)</span>
                                    </div>
                                </div>
                                <p><?php _e('Latest month new or updated content','wp-meta-seo') ?>: <?php echo $results['new_content'][1][0]  ?></p>
                            </div>
                            <div class="col-xs-5 col-lg-4 text-right">
                                <input type="text" value="<?php echo $results['new_content'][0] ?>" class="dial-warning">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-sm-6 metaseo_tool-000" alt="<?php _e('The link title attribute does not have any SEO value for links. BUT links titles can influence click behavior for users, which may indirectly affect your SEO performance','wp-meta-seo') ?>">
                <div class="panel panel-danger-full panel-updates">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-7 col-lg-8">
                                <h4 class="panel-title text-warning"><?php _e('Link titles','wp-meta-seo') ?></h4>
                                <h3><?php echo $results['link_meta'][0].'%' ?></h3>
                                <div class="progress">
                                    <div style="width: <?php echo $results['link_meta'][0].'%' ?>" aria-valuemax="100" aria-valuemin="0" aria-valuenow="<?php echo $results['link_meta'][0] ?>" role="progressbar" class="progress-bar progress-bar-warning">
                                        <span class="sr-only"><?php echo $results['link_meta'][0].'%' ?> Complete (success)</span>
                                    </div>
                                </div>
                                <p><?php _e('Links title completed','wp-meta-seo') ?>: <?php echo $results['link_meta'][1][0].'/'.$results['link_meta'][1][1];  ?></p>
                            </div>
                            <div class="col-xs-5 col-lg-4 text-right">
                                <input type="text" value="<?php echo $results['link_meta'][0] ?>" class="dial-warning">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-sm-6 metaseo_tool" alt="<?php _e('A website with a bunch of 404 errors doesn’t provide a good user experience, which is significantly important in content marketing and SEO. We recommend to use our internal broken link checker and redirect tool to fix all the 404 error you can periodically.','wp-meta-seo') ?>">
                <div class="panel panel-updates">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-7 col-lg-8">
                                <h4 class="panel-title text-success"><?php _e('404 ERRORS','wp-meta-seo') ?></h4>
                                <h3><?php echo $link_errors['percent'].'%' ?></h3>
                                <div class="progress">
                                    <div style="width: <?php echo $link_errors['percent'].'%' ?>" aria-valuemax="100" aria-valuemin="0" aria-valuenow="<?php echo $link_errors['percent'] ?>" role="progressbar" class="progress-bar progress-bar-success">
                                        <span class="sr-only"><?php echo $link_errors['percent'].'%' ?> Complete (success)</span>
                                    </div>
                                </div>
                                <p><?php _e('Redirected 404 errors','wp-meta-seo') ?>: <?php echo $link_errors['count_404_redirected'].'/'.$link_errors['count_404'] ?></p>
                            </div>
                            <div class="col-xs-5 col-lg-4 text-right">
                                <input type="text" value="<?php echo $link_errors['percent'] ?>" class="dial-success">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (file_exists(WP_PLUGIN_DIR . '/imagerecycle-pdf-image-compression')) : ?>
            <?php
                if (!is_plugin_active($plugin_imgRecycle_file)) :
            ?>
            
            <div class="col-sm-6 metaseo_tool" alt="<?php _e('Images represent around 60% of a web page weight. An image compression reduce the image size by up to 70% while preserving the same visual quality. Small loading time is great for SEO!','wp-meta-seo') ?>">
                <div class="panel panel-updates">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-7 col-lg-8">
                                <h4 class="panel-title text-success"><?php _e('Image compression','wp-meta-seo') ?></h4>
                                <h3>0%</h3>
                                <div class="progress">
                                    <div style="width:0%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="0" role="progressbar" class="progress-bar progress-bar-success">
                                        <span class="sr-only">0% Complete (success)</span>
                                    </div>
                                </div>
                                <p><?php _e('Use ImageRecycle image compression plugin to activate this feature','wp-meta-seo') ?>: 0%</p>
                            </div>
                            <div class="col-xs-5 col-lg-4 text-right">
                                <input type="text" value="0" class="dial-success">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php else: ?>
            <?php $optimizer_results = $dashboard->wpmf_getImages_count(); ?>
            <div class="col-sm-6 metaseo_tool" alt="<?php _e('Images represent around 60% of a web page weight. An image compression reduce the image size by up to 70% while preserving the same visual quality. Small loading time is great for SEO!','wp-meta-seo') ?>">
                <div class="panel panel-updates">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-xs-7 col-lg-8">
                                <h4 class="panel-title text-success"><?php _e('Image compression','wp-meta-seo') ?></h4>
                                <h3><?php echo $optimizer_results['percent'].'%' ?></h3>
                                <div class="progress">
                                    <div style="width: <?php echo $optimizer_results['percent'].'%' ?>" aria-valuemax="100" aria-valuemin="0" aria-valuenow="<?php echo $optimizer_results['percent'] ?>" role="progressbar" class="progress-bar progress-bar-success">
                                        <span class="sr-only"><?php echo $optimizer_results['percent'].'%' ?> Complete (success)</span>
                                    </div>
                                </div>
                                <p><?php _e('Compressed images','wp-meta-seo') ?>: <?php echo $optimizer_results['image_optimize'].'/'.$optimizer_results['count_image'] ?></p>
                            </div>
                            <div class="col-xs-5 col-lg-4 text-right">
                                <input type="text" value="<?php echo $optimizer_results['percent'] ?>" class="dial-success">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            
        </div>
    </div>
    
    <div style="width:75%;margin: 0px auto;min-height: 200px;padding: 0px 10px 0px 10px;">
        <div class="left">
            <div class="dashboard-left" id='dashboard-left'>
                <div id="alexa-ranking">
                    <?php $dashboard->displayRank($url) ?>
                </div>
            </div>
        </div>

        <div class="right">
            <div class="dashboard-right">
                <div style="display: none"><?php _e("We can't get rank of this site from Alexa.com!","wp-meta-seo") ?></div>
                <div style="clear:left"></div>
                <div id="wpmetaseo-update-version">
                    <h4><?php echo __('Latest WP Meta SEO News', 'wp-meta-seo') ?></h4>
                    <ul>
                        <li><a target="_blank" href="https://www.joomunited.com/wordpress-products/wp-meta-seo"><?php _e('More information about WP Meta SEO','wp-meta-seo'); ?></a></li>
                        <li><a target="_blank" href="https://www.joomunited.com/"><?php _e('Other plugins from JoomUnited','wp-meta-seo'); ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

    <script type="text/javascript">
        jQuery(document).ready(function() {
            replace_url_img();
        });
        
        function replace_url_img(){
            var url = '<?php echo WPMETASEO_PLUGIN_URL; ?>';
            var icon_tip = url + 'img/icon_tip.png';
            var globe_sm = url + 'img/globe-sm.jpg';
            jQuery('.img-inline').attr('src',globe_sm);
            jQuery('#alexa-ranking .tt img').attr('src',icon_tip);
        }
                   
    </script>