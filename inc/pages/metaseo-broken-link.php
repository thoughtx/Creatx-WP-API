<?php
/*
  Meta image
 */

if (!class_exists('MetaSeo_Broken_Link_Table')) {
    require_once( WPMETASEO_PLUGIN_DIR . '/inc/class.metaseo-broken-link-table.php' );
}
wp_enqueue_style('metaseo-google-icon');
wp_enqueue_style('m-style-qtip');
wp_enqueue_script('jquery-qtip');

$metaseo_list_table = new MetaSeo_Broken_Link_Table();
$metaseo_list_table->process_action();
$metaseo_list_table->prepare_items();
$a = json_encode($metaseo_list_table->items);
if (!empty($_REQUEST['_wp_http_referer'])) {
    wp_redirect(remove_query_arg(array('_wp_http_referer', '_wpnonce'), stripslashes($_SERVER['REQUEST_URI'])));
    exit;
}
?>

<div class="wrap broken_link_table seo_extended_table_page">
    <div id="icon-edit-pages" class="icon32 icon32-posts-page"></div>

    <?php echo '<h1>' . __('404 & Redirects', 'wp-meta-seo') . '</h1>'; ?>
    <form id="wp-seo-meta-form" action="" method="post">
        <?php $metaseo_list_table->search_box1(); ?>

        <?php $metaseo_list_table->display(); ?>
    </form>

</div>
<script type="text/javascript">
    jQuery(document).ready(function ($) {
        var correctedURL;
        $('.wpms-link-url-field').on('keyup',function(){
            var url = $.trim( $(this).val() );
            if ( url && correctedURL !== url && ! /^(?:[a-z]+:|#|\?|\.|\/)/.test( url ) ) {
                    $(this).val( 'http://' + url );
                    correctedURL = url;
            }
        });
        
        $('.wpms_scan_link').on('click',function(){
            var $this = $(this);
            wpms_scan_link($this);
        });
        
        $('.wpms_flush_link').on('click',function(){
            var $this = $(this);
            var flush_val = $('#filter-by-flush').val();
            if(flush_val != 'none'){
                $('#wp-seo-meta-form .spinner').css('visibility','visible').show();
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        'action': 'wpms_flush_link',
                        'type' : $('#filter-by-flush').val()
                    },
                    success: function (res) {
                        $('#wp-seo-meta-form .spinner').hide();
                        window.location.assign(document.URL);
                    }
                });
            }
        });
        
        $('.wpms-edit-button').on('click', function () {
            $(this).closest('td').find('.wpms-inline-editor-content').show();
        });

        $('.wpms-cancel-button').on('click', function () {
            $(this).closest('td').find('.wpms-inline-editor-content').hide();
        });

        $('.wpms-update-link-button').on('click', function () {
            var $this = $(this);
            var link_id = $this.data('link_id');
            var new_link = $this.closest('td').find('.wpms-link-url-field').val();
            var new_text = $this.closest('td').find('.wpms-link-text-field').val();
            var link_redirect = $this.closest('td').find('.wpms-link-redirect-field').val();
            var data_type = $this.closest('td').find('.wpms-link-text-field').data('type');
            if (new_link == '') {
                alert('Error: Link URL must not be empty');
            } else {
                wpms_update_link($this, link_id, new_link,new_text,link_redirect,data_type);
            }
        });

        $('.wpms-unlink-button').on('click', function () {
            var $this = $(this);
            var link_id = $this.data('link_id');
            wpms_unlink($this, link_id);
        });
        
        $('.wpms-recheck-button').on('click', function () {
            var $this = $(this);
            var link_id = $this.data('link_id');
            wpms_recheck_link($this, link_id);
        });
        
        function wpms_recheck_link($this,link_id) {
            var oldColor = $this.closest('tr').css('background-color');
            $this.closest('tr').css({ 'background-color' : "rgba(0, 115, 170, 0.1)" });
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    'action': 'wpms_recheck_link',
                    'link_id': link_id,
                },
                success: function (res) {
                    if(res.status){
                        var status = res.status_text;
                        if(status.indexOf('404') != -1 || status == 'Server Not Found'){
                            $this.closest('tr').find('.col_status').html('<i class="material-icons wpms_warning metaseo_help_status" alt="404 error, not found">warning</i>');
                        }else if(status.indexOf('200') != -1){
                            $this.closest('tr').find('.col_status').html('<i class="material-icons wpms_ok metaseo_help_status" alt="Link is OK">done</i>');
                        }else if(status.indexOf('301') != -1){
                            $this.closest('tr').find('.col_status').html('<i class="material-icons wpms_ok metaseo_help_status" alt="Permanent redirect">done</i>');
                        }else if(status.indexOf('302') != -1){
                            $this.closest('tr').find('.col_status').html('<i class="material-icons wpms_ok metaseo_help_status" alt="Moved temporarily">done</i>');
                        }else{
                            $this.closest('tr').find('.col_status').html(res.status_text);
                        }
                        wpms_tooltip();
                        $this.closest('tr').css({ 'background-color' : oldColor });
                    }
                }
            });
        }
        
        
        function wpms_unlink($this,link_id) {
            $this.closest('tr').css({ 'background-color' : "rgba(0, 115, 170, 0.1)" });
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    'action': 'wpms_unlink',
                    'link_id': link_id,
                },
                success: function (res) {
                    if (res) {
                        $this.closest('tr').remove();
                    }
                }
            });
        }


        function wpms_update_link($this, link_id, new_link,new_text,link_redirect,data_type) {
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    'action': 'wpms_update_link',
                    'link_id': link_id,
                    'new_link': new_link,
                    'new_text': new_text,
                    'link_redirect' : link_redirect,
                    'data_type' : data_type
                },
                success: function (res) {
                    if (res.status) {
                        $this.closest('td').find('.wpms-inline-editor-content').hide();
                        //if(res.type != '404_automaticaly'){
                            $this.closest('td').find('.link_html').html(res.new_link).attr('href', res.new_link);
                            $this.closest('tr').find('.col_status').html(res.status_text);
                            
                            var status = res.status_text;
                            if(status.indexOf('404') != -1 || status == 'Server Not Found'){
                                $this.closest('tr').find('.col_status').html('<i class="material-icons wpms_warning metaseo_help_status" alt="404 error, not found">warning</i>');
                            }else if(status.indexOf('200') != -1){
                                $this.closest('tr').find('.col_status').html('<i class="material-icons wpms_ok metaseo_help_status" alt="Link is OK">done</i>');
                            }else if(status.indexOf('301') != -1){
                                $this.closest('tr').find('.col_status').html('<i class="material-icons wpms_ok metaseo_help_status" alt="Permanent redirect">done</i>');
                            }else if(status.indexOf('302') != -1){
                                $this.closest('tr').find('.col_status').html('<i class="material-icons wpms_ok metaseo_help_status" alt="Moved temporarily">done</i>');
                            }else{
                                $this.closest('tr').find('.col_status').html(res.status_text);
                            }
                            wpms_tooltip();
                        //}
                        
                        if(res.type == 'url'){
                            if(res.new_text != ''){
                                $this.closest('tr').find('.link_text').html(new_text);
                            }
                        }
                    }
                }
            });
        }
        
        function wpms_tooltip(){
            jQuery('.metaseo_help_status').qtip({
                content: {
                    attr: 'alt'
                },
                position: {
                    my: 'bottom left',
                    at: 'top center'
                },
                style: {
                    tip: {
                        corner: true
                    },
                    classes: 'metaseo-qtip qtip-rounded'
                },
                show: 'hover',
                hide: {
                    fixed: true,
                    delay: 500
                }

            });
        }
        wpms_tooltip();
    });

</script>