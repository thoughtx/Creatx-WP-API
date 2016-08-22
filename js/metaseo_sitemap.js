(function($){
    $(document).ready(function(){
        $(".holder_posts").jPages({
            containerID: "wrap_sitemap_option_posts",
            previous: "←",
            next: "→",
            perPage: 100,
            delay: 20
        });
        
        $(".holder_pages").jPages({
            containerID: "wrap_sitemap_option_pages",
            previous: "←",
            next: "→",
            perPage: 100,
            delay: 20
        });
        
        jQuery('.wpms_source_sitemaps tr label,.wpms_row h3 input').qtip({
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
        
        $('.wpms_save_create_sitemaps').on('click',function(){
            wpms_save_create_sitemaps();
        });

        wpms_save_create_sitemaps = function(){
            $('.spinner_save_sitemaps').css({'visibility':'visible'}).show();
            var posts = {} , pages = {} , menus = {} , taxonomies = [] , columns_menu = {} , wpms_category_link = [];
            $(".wpms_xmap_posts").each(function(i,v){
                if($(v).is(':checked')){
                    var id = $(v).val();
                    var priority = $('#priority_posts_'+id).val();
                    var frequency = $('#frequency_posts_'+id).val();
                    posts[id] = {'post_id':id , 'priority' : priority , 'frequency' : frequency};
                }
            });
            
            $(".wpms_xmap_pages").each(function(i,v){
                if($(v).is(':checked')){
                    var id = $(v).val();
                    var priority = $('#priority_pages_'+id).val();
                    var frequency = $('#frequency_pages_'+id).val();
                    pages[id] = {'post_id':id , 'priority' : priority , 'frequency' : frequency};
                }
            });
            
            $(".wpms_xmap_menu").each(function(i,v){
                if($(v).is(':checked')){
                    var id = $(v).val();
                    var priority = $('#priority_menu_'+id).val();
                    var frequency = $('#frequency_menu_'+id).val();
                    menus[id] = {'menu_id':id , 'priority' : priority , 'frequency' : frequency};
                }
            });
            
            $('.wpms_sitemap_taxonomies').each(function(i,v){
                if($(v).is(':checked')){
                    taxonomies.push($(v).val());
                }
            });
            
            $('.sitemap_addlink_categories').each(function(i,v){
                if($(v).is(':checked')){
                    wpms_category_link.push($(v).val());
                }
            });
            
            if($('#wpms_sitemap_author').is(':checked')){
                var wpms_sitemap_author = 1;
            }else{
                var wpms_sitemap_author = 0;
            }
            
            if($('#wpms_sitemap_root').is(':checked')){
                var wpms_sitemap_root = 1;
            }else{
                var wpms_sitemap_root = 0;
            }
            
            if($('#wpms_sitemap_add').is(':checked')){
                var wpms_sitemap_add = 1;
            }else{
                var wpms_sitemap_add = 0;
            }
            
            $('.wpms_display_column_menus').each(function(i,v){
                var menu_id = $(v).data('menu_id');
                columns_menu[menu_id] = $(v).val()
            });
            
            $.ajax({
                url : ajaxurl,
                method : 'POST',
                dataType : 'json',
                data : {
                    action : 'wpms_save_sitemap_settings',
                    wpms_sitemap_posts : JSON.stringify(posts),
                    wpms_sitemap_pages : JSON.stringify(pages),
                    wpms_sitemap_menus : JSON.stringify(menus),
                    wpms_html_sitemap_page : $('#wpms_html_sitemap_page').val(),
                    wpms_html_sitemap_column : $('#wpms_html_sitemap_column').val(),
                    wpms_html_sitemap_position : $('#wpms_html_sitemap_position').val(),
                    wpms_check_firstsave : $('#wpms_check_firstsave').val(),
                    wpms_sitemap_author : wpms_sitemap_author,
                    wpms_sitemap_root : wpms_sitemap_root,
                    wpms_sitemap_add : wpms_sitemap_add,
                    wpms_category_link : wpms_category_link,
                    wpms_sitemap_taxonomies : taxonomies,
                    wpms_public_name_posts : $('.public_name_posts').val(),
                    wpms_public_name_pages : $('.public_name_pages').val(),
                    wpms_display_column_menus : JSON.stringify(columns_menu),
                    wpms_display_column_posts : $('.wpms_display_column_posts').val(),
                    wpms_display_column_pages : $('.wpms_display_column_pages').val()
                },
                success : function (res){
                    wpms_regen_sitemaps();
                }
            });
        }
        
        wpms_regen_sitemaps = function(){
            $.ajax({
                url : ajaxurl,
                method : 'POST',
                dataType : 'json',
                data : {
                    action : 'wpms_regenerate_sitemaps'
                },
                success : function (res){
                    $('.spinner_save_sitemaps').hide();
                }
            });
        }
        
        var wpms_columns = ['Zezo' , 'One' , 'Two' , 'Three'];
        $('#wpms_html_sitemap_column').on('change',function(){
            $('.wpms_display_column').html(null);
            for (var i=1 ; i<= parseInt($(this).val()) ; i++){
                $('.wpms_display_column').append('<option value="'+ i +'">'+wpms_columns[i]+'</option>');
            }
        });
        
        $('.sitemap_check_all_posts_categories').on('click',function(){
            var category = $(this).data('category');
            if($(this).is(':checked')){
                $('.'+category).prop('checked',true);
            }else{
                $('.'+category).prop('checked',false);
            }
        });

        $('.sitemap_check_all').on('click',function(){
            var type = $(this).data('type');
            if($(this).is(':checked')){
                $('.cb_sitemaps_'+type).prop('checked',true);
            }else{
                $('.cb_sitemaps_'+type).prop('checked',false);
            }
        });
        
        $('.sitemap_check_all_posts_in_page').on('click',function(){
            var type = $(this).data('type');
            if($(this).is(':checked')){
                $('.wpms_row').not('.jp-hidden').find('.cb_sitemaps_'+type).prop('checked',true);
            }else{
                $('.wpms_row').not('.jp-hidden').find('.cb_sitemaps_'+type).prop('checked',false);
            }
        });
        
        $('.nav-tab').on('click',function(){
            var tab = $(this).data('tab');
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            $('.wpms_source').hide();
            $('.wpms_source_'+tab).show();
        });
        
    });
}(jQuery));