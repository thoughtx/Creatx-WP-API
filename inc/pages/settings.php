<?php
    wp_enqueue_style('m-style-qtip');
    wp_enqueue_script('jquery-qtip');
    $posts = get_posts(array('post_type' => 'page','posts_per_page'=>-1,'numberposts' => -1));
    $types_404 = array('none' => 'None','wp-meta-seo-page' => __('WP Meta SEO page','wp-meta-seo'), 'custom_page' => __('Custom page','wp-meta-seo'));
    
    $defaul_settings_404 = array('wpms_redirect_homepage' => 0, 'wpms_type_404' => 'none' , 'wpms_page_redirected' => 'none');
    $wpms_settings_404 = get_option('wpms_settings_404');
    if(is_array($wpms_settings_404)){
        $defaul_settings_404 = array_merge($defaul_settings_404, $wpms_settings_404);
    }
?>
<div class="wrap wrap_wpms_settings">
    <h1><?php _e('WP Meta SEO global settings','wp-meta-seo') ?></h1>
    <div class="tab-header">
        <div class="wpms-tabs">
            <div class="wpms-tab-header active" data-label="wpms-global"><?php _e('Global','wp-meta-seo') ?></div>
            <div class="wpms-tab-header" data-label="wpms-redirection"><?php _e('Redirections and 404','wp-meta-seo') ?></div>
        </div>
    </div>
    <div class="wpms_content_settings">
        <div class="content-box content-wpms-global">
            <form method="post" action="options.php">
                <?php
                settings_fields( 'Wp Meta SEO' );
                do_settings_sections('metaseo_settings');
                submit_button();
                ?>
            </form>
        </div>

        <div class="content-box content-wpms-redirection">
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php _e('Global home redirect','wp-meta-seo') ?></th>
                        <td><label>
                                <input <?php checked($defaul_settings_404['wpms_redirect_homepage'], 1) ?> data-label="wpms_redirect_homepage" type="checkbox" class="cb_option" id="wpms_redirect_homepage">
                                <?php _e('Redirect all 404 errors to home page','wp-meta-seo') ?>
                                <input type="hidden" class="wpms_redirect_homepage" name="wpms_redirect[wpms_redirect_homepage]" value="<?php echo $defaul_settings_404['wpms_redirect_homepage'] ?>">
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Custom 404 page','wp-meta-seo') ?></th>
                        <td>
                            <select name="wpms_redirect[wpms_type_404]" class="wpms_type_404" <?php echo ($defaul_settings_404['wpms_redirect_homepage']==1)?"disabled":"" ?>>
                                <?php foreach ($types_404 as $k => $type_404): ?>
                                <option <?php selected($defaul_settings_404['wpms_type_404'], $k) ?> value="<?php echo $k ?>"><?php echo $type_404 ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="wpms_redirect[wpms_page_redirected]" class="wpms_page_redirected" <?php echo (($defaul_settings_404['wpms_redirect_homepage']==1) || $defaul_settings_404['wpms_type_404'] != 'custom_page')?"disabled":"" ?>>
                                <option value="none"><?php _e('— Select —','wp-meta-seo') ?></option>
                                <?php foreach ($posts as $post): ?>
                                <option <?php selected($defaul_settings_404['wpms_page_redirected'], $post->ID) ?> value="<?php echo $post->ID ?>"><?php echo $post->post_title ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="button wpms_save_settings404"><?php _e('Save','wp-meta-seo') ?></div>
            <span class="message_saved"><?php _e('Saved','wp-meta-seo') ?></span>
        </div>

    </div>
</div>
<script type="text/javascript">
    jQuery(document).ready(function ($) {
        jQuery('.wrap_wpms_settings tr label').qtip({
            content: {
                attr: 'for'
            },
            position: {
                my: 'bottom left',
                at: 'top center'
            },
            style: {
                tip: {
                    corner: true,
                },
                classes: 'metaseo-qtip qtip-rounded'
            },
            show: 'hover',
            hide: {
                fixed: true,
                delay: 10
            }

        });
        
        $('.wpms-tab-header').on('click',function(){
            var $this = $(this);
            var label = $this.data('label');
            $('.wpms-tab-header').removeClass('active');
            $this.addClass('active');
            $('.content-box').addClass('content-noactive').removeClass('content-active').hide();
            $('.content-'+ label +'').addClass('content-active').removeClass('content-noactive').slideDown();
        });
        
        $('.wpms_save_settings404').on('click',function(){
            var home_redirected = $('.wpms_redirect_homepage').val();
            var type_404 = $('.wpms_type_404').val();
            var page_redirected = $('.wpms_page_redirected').val();
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    'action': 'wpms_save_settings404',
                    'wpms_redirect[wpms_redirect_homepage]': home_redirected,
                    'wpms_redirect[wpms_type_404]' : type_404,
                    'wpms_redirect[wpms_page_redirected]': page_redirected
                },
                success: function (res) {
                    if(res == true){
                        $('.message_saved').fadeIn(10).delay(2000).fadeOut(2000);
                    }else{
                        alert('Save errors !')
                    }
                }
            });
        });
        
        $('.wpms_type_404').on('change',function(){
            var type_404 = $(this).val();
            if(type_404 == 'wp-meta-seo-page' || type_404 == 'none'){
                $('.wpms_page_redirected').prop('disabled',true);
            }else if(type_404 == 'custom_page'){
                $('.wpms_page_redirected').prop('disabled',false);
            }
        });
        
        $('.cb_option').unbind('click').bind('click', function() {
            var check = $(this).attr('checked');
            var type = $(this).attr('type');
            var value;
            var $this = $(this);
            if (type == 'checkbox') {
                if (check == 'checked') {
                    value = 1;
                } else {
                    value = 0;
                }
                $('input[name="wpms_redirect['+ $(this).data('label') +']"]').val(value);
                
                if($(this).data('label') == 'wpms_redirect_homepage'){
                    if (check == 'checked') {
                        $('.wpms_type_404,.wpms_page_redirected').prop('disabled',true);
                    } else {
                        $('.wpms_type_404,.wpms_page_redirected').prop('disabled',false);
                    }
                }
            }
        });
    });

</script>