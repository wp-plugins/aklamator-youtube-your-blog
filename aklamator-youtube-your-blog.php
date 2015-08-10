<?php
/*
Plugin Name: Aklamator - Youtube Your Blog
Plugin URI: http://www.aklamator.com/wordpress
Description: Show youtube channel on your blog, just paste one youtube and we will show all your channel videos. Drag and drop widget and show youtube gallery. Additionally Aklamator service enables you to add your media releases, sell PR announcements, cross promote web sites using RSS feed and provide new services to your clients in digital advertising.
Version: 1.4
Author: Aklamator
Author URI: http://www.aklamator.com/
License: GPL2

Copyright 2015 Aklamator.com (email : info@aklamator.com)

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

/*
 * Add setting link on plugin page
 */

if( !function_exists("aklamatorYT_plugin_settings_link")){
    // Add settings link on plugin page
    function aklamatorYT_plugin_settings_link($links) {
        $settings_link = '<a href="admin.php?page=aklamator-youtube-your-blog">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}
add_filter("plugin_action_links_".plugin_basename(__FILE__), 'aklamatorYT_plugin_settings_link' );

/*
 * Add rate and review link in plugin section
 */
if( !function_exists("aklamatorYT_plugin_meta_links")) {
    function aklamatorYT_plugin_meta_links($links, $file)
    {
        $plugin = plugin_basename(__FILE__);
        // create link
        if ($file == $plugin) {
            return array_merge(
                $links,
                array('<a href="https://wordpress.org/support/view/plugin-reviews/aklamator-youtube-your-blog" target=_blank>Please rate and review</a>')
            );
        }
        return $links;
    }
}
add_filter( 'plugin_row_meta', 'aklamatorYT_plugin_meta_links', 10, 2);

/*
 * Activation Hook
 */

register_activation_hook( __FILE__, 'set_up_options_aklamator_YT' );

function set_up_options_aklamatorYT(){
    add_option('aklamatorYTChannelURL', '');
    add_option('aklamatorYTApplicationID', '');
    add_option('aklamatorYTPoweredBy', '');
    add_option('aklamatorYTSingleWidgetID', '');
    add_option('aklamatorYTPageWidgetID', '');
    add_option('aklamatorYTSingleWidgetTitle', '');
}

/*
 * Uninstall Hook
 */
register_uninstall_hook(__FILE__, 'aklamatorYT_uninstall');

function aklamatorYT_uninstall()
{
    delete_option('aklamatorYTChannelURL');
    delete_option('aklamatorYTApplicationID');
    delete_option('aklamatorYTPoweredBy');
    delete_option('aklamatorYTSingleWidgetID');
    delete_option('aklamatorYTPageWidgetID');
    delete_option('aklamatorYTSingleWidgetTitle');
}


if( !function_exists("bottom_of_every_post_yt")){
    function bottom_of_every_post_yt($content){

        /*  we want to change `the_content` of posts, not pages
            and the text file must exist for this to work */

        if (is_single()){
            $widget_id = get_option('aklamatorYTSingleWidgetID');
        }elseif (is_page()) {
            $widget_id = get_option('aklamatorYTPageWidgetID');
        }else{

            /*  if `the_content` belongs to a page or our file is missing
                the result of this filter is no change to `the_content` */

            return $content;
        }

        $title = "";
            if(get_option('aklamatorYTSingleWidgetTitle') !== ''){
                $title .= "<h2>". get_option('aklamatorYTSingleWidgetTitle'). "</h2>";
            }

            /*  append the text file contents to the end of `the_content` */
            return $content . $title ."<!-- created 2014-11-25 16:22:10 -->
            <div id=\"akla$widget_id\"></div>
            <script>(function(d, s, id) {
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) return;
            js = d.createElement(s); js.id = id;
            js.src = \"http://aklamator.com/widget/$widget_id\";
            fjs.parentNode.insertBefore(js, fjs);
         }(document, 'script', 'aklamator-$widget_id'));</script>
        <!-- end -->" . "<br>";
    }

}

class AklamatorYoutubeWidget
{

    public $aklamator_url;
    public $api_data;

    public $popular_channels = array(
        array(
            'name' => 'YouTube Spotlight',
            'url' => 'https://www.youtube.com/user/youtube'
        ),
        array(
            'name' => 'PewDiePie',
            'url' => 'https://www.youtube.com/user/PewDiePie/'
        ),
        array(
            'name' => 'EmiMusic',
            'url' => 'https://www.youtube.com/user/emimusic'
        ),
        array(
            'name' => 'FunToyzCollector',
            'url' => 'https://www.youtube.com/user/disneycollectorbr'
        )

    );


    public function __construct()
    {

        $this->aklamator_url = "http://aklamator.com/";


        if (is_admin()) {
            add_action("admin_menu", array(
                &$this,
                "adminMenu"
            ));

            add_action('admin_init', array(
                &$this,
                "setOptions"
            ));

            if (get_option('aklamatorYTApplicationID') !== '') {
                $this->api_data =  $this->addNewWebsiteApi();
            }
        }
        if (get_option('aklamatorYTSingleWidgetID') !== 'none') {

            if (get_option('aklamatorYTSingleWidgetID') == '') {
                if ($this->api_data->data[0]) {
                    update_option('aklamatorYTSingleWidgetID', $this->api_data->data[0]->uniq_name);
                }

            }
            add_filter('the_content', 'bottom_of_every_post_yt');
        }

        if (get_option('aklamatorYTPageWidgetID') !== 'none') {

            if (get_option('aklamatorYTPageWidgetID') == '') {
                if ($this->api_data->data[0]) {
                    update_option('aklamatorYTPageWidgetID', $this->api_data->data[0]->uniq_name);
                }

            }
            add_filter('the_content', 'bottom_of_every_post_yt');
        }

    }

    function setOptions()
    {
        register_setting('aklamatorYT-options', 'aklamatorYTChannelURL');
        register_setting('aklamatorYT-options', 'aklamatorYTApplicationID');
        register_setting('aklamatorYT-options', 'aklamatorYTPoweredBy');
        register_setting('aklamatorYT-options', 'aklamatorYTSingleWidgetID');
        register_setting('aklamatorYT-options', 'aklamatorYTPageWidgetID');
        register_setting('aklamatorYT-options', 'aklamatorYTSingleWidgetTitle');

    }

    public function adminMenu()
    {
        add_menu_page('Aklamator - Youtube Your Blog', 'Aklamator YT', 'manage_options', 'aklamator-youtube-your-blog', array(
            $this,
            'createAdminPage'
        ), content_url() . '/plugins/aklamator-youtube-your-blog/images/aklamator-icon.png');

    }


    public function getSignupUrl()
    {
        
        return $this->aklamator_url . 'registration/publisher?utm_source=wordpress&utm_medium=admin&e=' . urlencode(get_option('admin_email')) . '&pub=' .  preg_replace('/^www\./','',$_SERVER['SERVER_NAME']).
        '&un=' . urlencode(wp_get_current_user()->display_name).'&domain='.site_url();

    }

    private function addNewWebsiteApi()
    {

        if (!is_callable('curl_init')) {
            return;
        }

        $service     = $this->aklamator_url . "wp-authenticate/user";
        $p['ip']     = $_SERVER['REMOTE_ADDR'];
        $p['domain'] = site_url();
        $p['source'] = "wordpress";
        $p['AklamatorApplicationID'] = get_option('aklamatorYTApplicationID');
        $p['AklamatorYTChannelURL'] = get_option('aklamatorYTChannelURL');

        $client = curl_init();

        curl_setopt($client, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($client, CURLOPT_HEADER, 0);
        curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($client, CURLOPT_URL, $service);

        if (!empty($p)) {
            curl_setopt($client, CURLOPT_POST, count($p));
            curl_setopt($client, CURLOPT_POSTFIELDS, http_build_query($p));
        }

        $data = curl_exec($client);
        if (curl_error($client)!= "") {
            $this->curlfailovao=1;
        } else {
            $this->curlfailovao=0;
        }

        curl_close($client);

        $data = json_decode($data);
        //var_dump($data);die;
        return $data;

    }

    public function createAdminPage()
    {
        $code = get_option('aklamatorYTApplicationID');
        $channel_url = get_option('aklamatorYTChannelURL');
        $ak_home_url = 'http://aklamator.com';
        $ak_dashboard_url = 'http://aklamator.com/dashboard';

        ?>
        <style>
            #adminmenuback{ z-index: 0}
            #aklamatorYT-options ul { margin-left: 10px; }
            #aklamatorYT-options ul li { margin-left: 15px; list-style-type: disc;}
            #aklamatorYT-options h1 {margin-top: 5px; margin-bottom:10px; color: #00557f}
            .fz-span { margin-left: 23px;}


            .aklamatorYT-signup-button {
                float: left;
                vertical-align: top;
                width: auto;
                height: 30px;
                line-height: 30px;
                padding: 10px;
                font-size: 22px;
                color: white;
                text-align: center;
                text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
                background: #c0392b;
                border-radius: 5px;
                border-bottom: 2px solid #b53224;
                cursor: pointer;
                -webkit-box-shadow: inset 0 -2px #b53224;
                box-shadow: inset 0 -2px #b53224;
                text-decoration: none;
                margin-top: 3px;
                margin-bottom: 10px;
                /*clear: both;*/
            }

            a.aklamatorYT-signup-button:hover {
                cursor: pointer;
                color: #f8f8f8;
            }

            .btn { border: 1px solid #fff; font-size: 13px; border-radius: 3px; background: transparent; text-transform: uppercase; font-weight: 700; padding: 4px 10px; min-width: 162px; max-width: 100%; text-decoration: none;}
            .btn:Hover, .btn.hovered { border: 1px solid #fff; }
            .btn:Active, .btn.pressed { opacity: 1; border: 1px solid #fff; border-top: 3px solid #17ade0; -webkit-box-shadow: 0 0 0 transparent; box-shadow: 0 0 0 transparent; }
            
            .btn-primary { background: #1ac6ff; border:1px solid #1ac6ff; color: #fff; text-decoration: none;}
            .btn-primary:hover, .btn-primary.hovered { background: #1ac6ff;  border:1px solid #1ac6ff; opacity:0.9; }
            .btn-primary:Active, .btn-primary.pressed { background: #1ac6ff; border:1px solid #1ac6ff; }

            .box{float: left; margin-left: 10px; width: 500px; background-color:#f8f8f8; padding: 10px; border-radius: 5px;}

        </style>
        <!-- Load css libraries -->

        <link href="//cdn.datatables.net/1.10.5/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css">

        <div id="aklamatorYT-options" style="width:880px;margin-top:10px;">

            <div style="float: left; width: 300px;">
                    
                <a target="_blank" href="<?php echo $ak_home_url; ?>?utm_source=wp-plugin">
                    <img style="border-radius:5px;border:0px;" src=" <?php echo plugins_url('images/logo.jpg', __FILE__);?>" /></a>
                <?php
                if ($code != '') : ?>
                    <a target="_blank" href="<?php echo $ak_dashboard_url; ?>?utm_source=wp-plugin">
                        <img style="border:0px;margin-top:5px;border-radius:5px;" src="<?php echo plugins_url('images/dashboard.jpg', __FILE__); ?>" /></a>

                <?php endif; ?>

                <a target="_blank" href="<?php echo $ak_home_url;?>/contact?utm_source=wp-plugin-contact">
                    <img style="border:0px;margin-top:5px; margin-bottom:5px;border-radius:5px;" src="<?php echo plugins_url('images/support.jpg', __FILE__); ?>" /></a>

                <a target="_blank" href="http://qr.rs/q/4649f">
                    <img style="border:0px;margin-top:5px; margin-bottom:5px;border-radius:5px;" src="<?php echo plugins_url('images/promo-300x200.png', __FILE__); ?>" /></a>

            </div>
            <div class="box">

                <h1 style="margin-bottom: 40px">Aklamator Youtube Your Blog</h1>

                <form method="post" action="options.php">
                    <?php
                    settings_fields('aklamatorYT-options');


                    if ($channel_url == '') : ?>
                    <h3>Step 1: Paste your Youtube video or channel URL</h3>
                    <?php else :?>
                        <h3>Your Youtube video or channel URL</h3>
                    <?php endif;?>
                    <p>
                        <input type="text" style="width: 400px" name="aklamatorYTChannelURL" id="aklamatorYTChannelURL" value="<?php
                        echo $channel_url; ?>" maxlength="999" />

                    </p>
                    <p>
                        <select id="aklamatorYTPopular" name="aklamatorYTPopular">
                            <?php
                            foreach ( $this->popular_channels as $item ): ?>
                                <option <?php echo (get_option('aklamatorYTChannelURL') == $item['url'])? 'selected="selected"' : '' ;?> value="<?php echo $item['url']; ?>"><?php echo $item['name']; ?></option>
                            <?php endforeach; ?>
                        </select> or choose from popular channel

                    </p>
                    <?php

                    if ($code == '') : ?>
                        <h3 style="float: left">Step 2:</h3>
                        <a style="float: right" class='aklamatorYT-signup-button' id="aklamatorYT-signup-button" >Click here to create your FREE account!</a>

                    <?php endif; ?>

                    <div style="clear: both"></div>
                    <?php if ($code == '') { ?>
                        <h3>Step 3: &nbsp;&nbsp;&nbsp;&nbsp; Paste your Aklamator Application ID</h3>
                    <?php }else{ ?>
                        <h3>Your Aklamator Application ID</h3>
                    <?php } ?>

                    <p>
                        <input type="text" style="width: 400px" name="aklamatorYTApplicationID" id="aklamatorYTApplicationID" value="<?php
                        echo (get_option("aklamatorYTApplicationID"));
                        ?>" maxlength="50" onchange="appIDChange(this.value)"/>

                    </p>
                    <p>
                        <input type="checkbox" id="aklamatorYTPoweredBy" name="aklamatorYTPoweredBy" <?php echo (get_option("aklamatorYTPoweredBy") == true ? 'checked="checked"' : ''); ?> Required="Required">
                        <strong>Required</strong> I acknowledge there is a 'powered by aklamator' link on the widget. <br />
                    </p>
                    <?php if($this->api_data->flag === false): ?>
                        <p><span style="color:red"><?php echo $this->api_data->error; ?></span></p>
                    <?php endif; ?>
           
                    <?php if(get_option('aklamatorYTApplicationID') !=='' && $this->api_data->flag): ?>
           
                    <p> 
                    <h1>Options</h1>
                    <h4>Select widget to be shown on bottom of the each:</h4>

                    <label for="aklamatorYTSingleWidgetTitle">Title Above widget (Optional): </label>
                    <input type="text" style="width: 300px; margin-bottom:10px" name="aklamatorYTSingleWidgetTitle" id="aklamatorYTSingleWidgetTitle" value="<?php echo (get_option("aklamatorYTSingleWidgetTitle")); ?>" maxlength="999" />

                    <?php 

                        $widgets = $this->api_data->data;

                        /* Add new item to the end of array */
                        $item_add = new stdClass();
                        $item_add->uniq_name = 'none';
                        $item_add->title = 'Do not show';
                        $widgets[] = $item_add;

                    ?>   
                    
                    <label for="aklamatorYTSingleWidgetID">Single post: </label>
                    <select id="aklamatorYTSingleWidgetID" name="aklamatorYTSingleWidgetID">
                        <?php    
                        foreach ( $widgets as $item ): ?>
                            <option <?php echo (get_option('aklamatorYTSingleWidgetID') == $item->uniq_name)? 'selected="selected"' : '' ;?> value="<?php echo $item->uniq_name; ?>"><?php echo $item->title; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input style="margin-left: 5px;" type="button" id="preview_single" class="button primary big submit" onclick="myFunction($('#aklamatorYTSingleWidgetID option[selected]').val())" value="Preview" <?php echo get_option('aklamatorYTSingleWidgetID')=="none"? "disabled" :"" ;?>>
                    </p>

                    <p>
                        <label for="aklamatorYTPageWidgetID">Single page: </label>
                        <select id="aklamatorYTPageWidgetID" name="aklamatorYTPageWidgetID">
                            <?php
                            foreach ( $widgets as $item ): ?>
                                <option <?php echo (get_option('aklamatorYTPageWidgetID') == $item->uniq_name)? 'selected="selected"' : '' ;?> value="<?php echo $item->uniq_name; ?>"><?php echo $item->title; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input style="margin-left: 5px;" type="button" id="preview_page" class="button primary big submit" onclick="myFunction($('#aklamatorYTPageWidgetID option[selected]').val())" value="Preview" <?php echo get_option('aklamatorYTPageWidgetID')=="none"? "disabled" :"" ;?>>

                    </p>

                
            <?php endif; ?>
             <input style ="margin-bottom:15px;" type="submit" value="<?php echo (_e("Save Changes")); ?>" />


            </form>
            </div>

        </div>

        <div style="clear:both"></div>
        <div style="margin-top: 20px; margin-left: 0px; width: 810px;" class="box">

            <?php if ($this->curlfailovao && get_option('aklamatorYTApplicationID') != ''): ?>
                <h2 style="color:red">Error communicating with Aklamator server, please refresh plugin page or try again later. </h2>
            <?php endif;?>
            <?php if(!$this->api_data->flag): ?>
                <a href="<?php echo $this->getSignupUrl(); ?>" target="_blank"><img style="border-radius:5px;border:0px;" src=" <?php echo plugins_url('images/teaser-810x262.png', __FILE__);?>" /></a>
            <?php else : ?>
            <!-- Start of dataTables -->
            <div id="aklamatorYTPro-options">
                <h1>Your Widgets</h1>
                <div>In order to add new widgets or change dimensions please <a href="http://aklamator.com/login" target="_blank">login to aklamator</a></div>
            </div>
            <br>
            <table cellpadding="0" cellspacing="0" border="0"
                   class="responsive dynamicTable display table table-bordered" width="100%">
                <thead>
                <tr>

                    <th>Name</th>
                    <th>Domain</th>
                    <th>Settings</th>
                    <th>Image size</th>
                    <th>Column/row</th>
                    <th>Created At</th>

                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->api_data->data as $item): ?>

                    <tr class="odd">
                        <td style="vertical-align: middle;" ><?php echo $item->title; ?></td>
                        <td style="vertical-align: middle;" >
                            <?php foreach($item->domain_ids as $domain): ?>
                                <a href="<?php echo $domain->url; ?>" target="_blank"><?php echo $domain->title; ?></a><br/>
                            <?php endforeach; ?>
                        </td>
                        <td style="vertical-align: middle"><div style="float: left; margin-right: 10px" class="button-group">
                                <input type="button" class="button primary big submit" onclick="myFunction('<?php echo $item->uniq_name; ?>')" value="Preview Widget">
                        </td>
                        <td style="vertical-align: middle;" ><?php echo "<a href = \"$this->aklamator_url"."widget/edit/$item->id\" target='_blank' title='Click & Login to change'>$item->img_size px</a>";  ?></td>
                        <td style="vertical-align: middle;" ><?php echo "<a href = \"$this->aklamator_url"."widget/edit/$item->id\" target='_blank' title='Click & Login to change'>".$item->column_number ." x ". $item->row_number."</a>"; ?></td>
                        <td style="vertical-align: middle;" ><?php echo $item->date_created; ?></td>
                    </tr>
                <?php endforeach; ?>

                </tbody>
                <tfoot>
                <tr>
                    <th>Name</th>
                    <th>Domain</th>
                    <th>Settings</th>
                    <th>Immg size</th>
                    <th>Column/row</th>
                    <th>Created At</th>
                </tr>
                </tfoot>
            </table>
        </div>

    <?php endif; ?>


        <!-- load js scripts -->

        <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
        <script type="text/javascript" src="<?php echo content_url(); ?>/plugins/aklamator-youtube-your-blog/assets/dataTables/jquery.dataTables.min.js"></script>


        <script type="text/javascript">

            function appIDChange(val) {

                $('#aklamatorYTSingleWidgetID option:first-child').val('');
                $('#aklamatorYTPageWidgetID option:first-child').val('');

            }

            function myFunction(widget_id) {

                    var myWindow = window.open('<?php echo $this->aklamator_url;?>show/widget/'+widget_id);
                    myWindow.focus();

            }


            $(document).ready(function(){


                $("#aklamatorYTSingleWidgetID").change(function(){

                    if($(this).val() == 'none'){
                        $('#preview_single').attr('disabled', true);
                    }else{
                        $('#preview_single').removeAttr('disabled');
                    }

                    $("#aklamatorYTSingleWidgetID option").each(function () {
//
                        if (this.selected) {
                           $(this).attr('selected', true);

                        }else{
                            $(this).removeAttr('selected');

                        }
                    });

                });


                $("#aklamatorYTPageWidgetID").change(function(){

                    if($(this).val() == 'none'){

                        $('#preview_page').attr('disabled', true);
                    }else{
                        $('#preview_page').removeAttr('disabled');
                    }

                    $("#aklamatorYTPageWidgetID option").each(function () {
//
                        if (this.selected) {
                            $(this).attr('selected', true);
                        }else{
                            $(this).removeAttr('selected');

                        }
                    });

                });


                $("#aklamatorYTPopular").change(function(){


                    $(this).find("option").each(function () {
//
                        if (this.selected) {
                            $('#aklamatorYTChannelURL').val(this.value);
                            $(this).attr('selected', true);

                        }else{
                            $(this).removeAttr('selected');

                        }
                    });

                });


                $('#aklamatorYT-signup-button').click(function(){
                    var yt_url = $('#aklamatorYTChannelURL');

                    if(yt_url.val() == ""){
                        alert('Youtube video or channel URL can\'t be empty');
                        yt_url.focus();

                    }else if(yt_url.val().indexOf("youtu") == -1){
                        alert('Please make sure that you entered valid Youtube URL');
                        yt_url.focus();
                    }else{
                        window.open('<?php echo $this->getSignupUrl(); ?>&channel='+yt_url.val(), '_blank');
                    }
                });

                if ($('table').hasClass('dynamicTable')) {
                    $('.dynamicTable').dataTable({
                        "iDisplayLength": 10,
                        "sPaginationType": "full_numbers",
                        "bJQueryUI": false,
                        "bAutoWidth": false

                    });
                }
            });

        </script>

    <?php
    }


}


new AklamatorYoutubeWidget();


// Widget section


add_action( 'after_setup_theme', 'vw_setup_vw_widgets_init_aklamatorYT' );
function vw_setup_vw_widgets_init_aklamatorYT() {
    add_action( 'widgets_init', 'vw_widgets_init_aklamatorYT' );
}

function vw_widgets_init_aklamatorYT() {
    register_widget( 'Aklamator_youtube_widget' );
}

class Aklamator_youtube_widget extends WP_Widget {

    private $default = array(
        'supertitle' => '',
        'title' => '',
        'content' => '',
    );



    public function __construct() {
        // widget actual processes
        parent::__construct(
            'Aklamator_youtube_widget', // Base ID
            'Aklamator Youtube Videos', // Name
            array( 'description' => __( 'Display Aklamator Widgets in Sidebar')) // Widget Description
        );


    }

    function widget( $args, $instance ) {
        extract($args);
        //var_dump($instance); die();

        $supertitle_html = '';
        if ( ! empty( $instance['supertitle'] ) ) {
            $supertitle_html = sprintf( __( '<span class="super-title">%s</span>', 'envirra' ), $instance['supertitle'] );
        }

        $title_html = '';
        if ( ! empty( $instance['title_yt'] ) ) {
            $title = apply_filters( 'widget_title', $instance['title_yt'], $instance, $this->id_base);
            $title_html = $supertitle_html.$title;
        }

        echo $before_widget;
        if ( $instance['title_yt'] ) echo $before_title . $title_html . $after_title;
        ?>
        <?php echo $this->show_widget(do_shortcode( $instance['widget_id_yt'] )); ?>
        <?php

        echo $after_widget;
    }

    private function show_widget($widget_id){
        $code = ""; ?>
        <!-- created 2014-11-25 16:22:10 -->
        <div id="akla<?php echo $widget_id; ?>"></div>
        <script>(function(d, s, id) {
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) return;
                js = d.createElement(s); js.id = id;
                js.src = "http://aklamator.com/widget/<?php echo $widget_id; ?>";
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'aklamator-<?php echo $widget_id; ?>'));</script>
        <!-- end -->
    <?php }

    function form( $instance ) {

        $widget_data = new AklamatorYoutubeWidget();

        $instance = wp_parse_args( (array) $instance, $this->default );

        $title = strip_tags( $instance['title_yt'] );
        $widget_id = $instance['widget_id_yt'];


        if($widget_data->api_data->flag && !empty($widget_data->api_data->data)): ?>

            <!-- title -->
            <p>
                <label for="<?php echo $this->get_field_id('title_yt'); ?>"><?php _e('Title (text shown above widget):','envirra-backend'); ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id('title_yt'); ?>" name="<?php echo $this->get_field_name('title_yt'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
            </p>

            <!-- Select - dropdown -->
            <label for="<?php echo $this->get_field_id('widget_id_yt'); ?>"><?php _e('Widget:','envirra-backend'); ?></label>
            <select id="<?php echo $this->get_field_id('widget_id_yt'); ?>" name="<?php echo $this->get_field_name('widget_id_yt'); ?>">
                <?php foreach ( $widget_data->api_data->data as $item ): ?>
                    <option <?php echo ($widget_id == $item->uniq_name)? 'selected="selected"' : '' ;?> value="<?php echo $item->uniq_name; ?>"><?php echo $item->title; ?></option>
                <?php endforeach; ?>
            </select>
            <br>
            <br>
            <br>
        <?php else :?>
            <br>
            <span style="color:red">Please make sure that you configured Aklamator plugin correctly</span>
            <a href="<?php echo admin_url(); ?>admin.php?page=aklamator-youtube-your-blog">Click here to configure Aklamator plugin</a>
            <br>
            <br>
        <?php endif;

    }
}