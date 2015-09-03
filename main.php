<?php
/**
 * Plugin Name: WP Comment Clone
 * Plugin URI: http://odgr.pw
 * Description: Copy post's comments to another post.
 * Version: 1.0
 * Author: Oscar De Gracia Jr.
 * Author URI: http://odgr.pw
 * License: A "Slug" license name e.g. GPL2
 */
defined('ABSPATH') or die("No script kiddies please!");
define( 'WPCC_URL', plugin_dir_url( __FILE__ ) );
define( 'WPCC_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPCC_CACHE', time() );

class WPCommentClone{

    function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );
        add_action( 'add_meta_boxes', array($this,'wpcc_add_meta_box') );

        add_action('wp_ajax_get_posts_by_type', array($this, 'get_posts_by_type'));
    }

    function register_admin_scripts(){
        wp_enqueue_style( 'wpcc-admin-styles', WPCC_URL . 'assets/css/admin.css',array(),WPCC_CACHE);
        wp_enqueue_script('wpcc-admin-scripts', WPCC_URL.'assets/js/admin.js', array('jquery'), WPCC_CACHE, true);
    }

    function wpcc_add_meta_box( $post_type ) {
        $post_types = array('lg_level');     //limit meta box to certain post types
        if ( in_array( $post_type, $post_types )) {
            add_meta_box(
                'wpcc_add_meta_box'
                ,__( 'Comment Clone' )
                ,array($this,'wpcc_add_meta_box_html')
                ,$post_type
                ,'advanced'
                ,'high'
            );
        }
    }

    function wpcc_add_meta_box_html(){
        ?>
            <p class="description">You can clone other post's comment using this option. Please select a post to clone.</p>
            <p>
                <label for="my_meta_box_post_type">Post type: </label>
                <select autocomplete="off" name='wpcc_select_post_type' id='wpcc_select_post_type'>
                    <option selected="selected" value="0">Select Post Type</option>
                    <?php
                        $post_types=get_post_types('', 'objects');
                        $exclude = array( 'attachment', 'revision', 'nav_menu_item', 'featured_item', 'portfolio' );

                        foreach ($post_types as $post_type):

                            if( TRUE === in_array( $post_type->name, $exclude ) )
                                continue;

                    ?>
                        <option value="<?php echo esc_attr($post_type->name); ?>"><?php echo esc_html($post_type->labels->name); ?></option>
                    <?php endforeach; ?>
                </select>
                <select autocomplete="off" disabled="disabled" name='wpcc_select_post' id='wpcc_select_post'>
                    <option value="0">Select Post</option>
                </select>
                <span class="spinner wpcc-clone-spinner"></span>
            </p>
            <?php wp_nonce_field( 'wpcc_comment_clone_xxx', 'wpcc_mb_nonce_' );?>
            <p><input disabled="disabled" autocomplete="off" type="button" value="Clone Comments" name="wpcc-clone-btn" id="wpcc-clone-btn" class="button-primary"></p>
        <?php
    }

    function get_posts_by_type(){
        $response = array("status" => false);

        if ( wp_verify_nonce( $_POST['nonce'], 'wpcc_comment_clone_xxx' ) ) {
            $post_type = $_POST['post_type'];

            $args = array( 'posts_per_page' => -1, 'orderby' => 'title','post_type'=> $post_type );
            $_posts = array();
            $posts = get_posts( $args );

            if( is_array($posts) && count($posts) > 0){
                foreach($posts as $key=>$post){
                    $_posts[$key]['id'] = $post->ID;
                    $_posts[$key]['title'] = $post->post_title;
                }
            }

            $response = array("status" => true, 'posts' =>$_posts);
      	}

        wp_send_json( $response );
    }
}
$WPCommentClone = new WPCommentClone();
