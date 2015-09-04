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
        add_action('wp_ajax_clone_comments', array($this, 'clone_comments'));
        add_action('wp_ajax_wpcc_delete_comments', array($this, 'wpcc_delete_comments'));
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

    function wpcc_add_meta_box_html($post){
        $details = get_post_meta($post->ID,'wpcc_details',true);

        ?>
            <?php if(empty($details)):?>
                <p class="description">You can clone other post's comment using this option. Please select a post to clone.</p>
                <div id="wpcc_html_wrap">
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
                    <p><input data-post-id="<?php echo $post->ID;?>" disabled="disabled" autocomplete="off" type="button" value="Clone Comments" name="wpcc-clone-btn" id="wpcc-clone-btn" class="button-primary"></p>
                </div>
            <?php else:?>
                <?php
                    $title = get_the_title($details);
                ?>
                <p class="description"><span class="red">NOTE:</span> You've already cloned <a href="<?php echo get_edit_post_link($details);?>"><?php echo $title;?></a>'s comments. You need to <a class="wpcc_advance_settings" href="#">delete all comments</a> before cloning new post's comments.</p>
            <?php endif;?>
            <a href="#" class="wpcc_advance_settings">Advance settings</a>
            <div id="wpcc_advance_settings_wrap">
                <?php wp_nonce_field( 'wpcc_comment_delete_xxx', 'wpcc_delete_mb_nonce_' );?>
                <p class="description">You can use this option to delete all post's comments. <strong>Note that this process is irreversible.</strong></p>
                <p><input data-post-id="<?php echo $post->ID;?>" autocomplete="off" type="button" value="Delete All Comments" id="wpcc-delete-comments-btn" class="button-primary"><span class="spinner wpcc-delete-spinner"></span></p>
            </div>
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
    function clone_comments(){
        $response = array("status" => false);

        if ( wp_verify_nonce( $_POST['nonce'], 'wpcc_comment_clone_xxx' ) ) {

            $post_id_to_clone = $_POST['post_id_to_clone'];
            $post_id = $_POST['post_id'];

            $comments_count = $this->_clone_comments($post_id,$post_id_to_clone);
            update_post_meta($post_id,'wpcc_details',$post_id_to_clone);

            $response = array("status" => true, 'post_id_to_clone' => $post_id_to_clone, "count" => $comments_count);
      	}

        wp_send_json( $response );
    }

    function _clone_comments($post_id=0,$post_id_to_clone=0){
        global $wpdb;

        $sql = "SELECT * FROM $wpdb->comments WHERE comment_post_ID=%d AND comment_approved=1";
        $comments = $wpdb->get_results($wpdb->prepare($sql,$post_id_to_clone),ARRAY_A);
        $comment_ids_translation = array();
        $new_comment_created = array();

        if(is_array($comments) && count($comments)>0){
            foreach($comments as $comment){
                $comment_id = $comment['comment_ID'];
                $comment_parent = intval($comment['comment_parent']);
                $comment['comment_post_ID'] = $post_id;
                $new_comment_id = wp_insert_comment($comment);
                $this->_clone_comment_meta($comment_id,$new_comment_id);
                $comment_ids_translation[$comment_id] = $new_comment_id;

                if($comment_parent)
                    $new_comment_created[] = $new_comment_id;
            }

            if(count($new_comment_created) > 0)
                $this->_translate_old_comment_parent_ids($new_comment_created,$comment_ids_translation);

        }

        return count($comments);
    }

    function _translate_old_comment_parent_ids($new_comments, $translation){
        global $wpdb;

        $sql = "SELECT comment_ID,comment_parent FROM $wpdb->comments WHERE comment_ID IN(%s)";
        $comments_with_parent = $wpdb->get_results($wpdb->prepare($sql,implode(",", $new_comments)),ARRAY_A);

        if(is_array($comments_with_parent) && count($comments_with_parent)>0){
            foreach($comments_with_parent as $comment){
                $comment_id = $comment['comment_ID'];
                $comment_parent = $comment['comment_parent'];

                $wpdb->update(
                	$wpdb->comments,
                	array(
                		'comment_parent' => $translation[$comment_parent]
                	),
                	array( 'comment_ID' => $comment_id ),
                	array( '%d' ),
                	array( '%d' )
                );
            }
        }
    }


    function _clone_comment_threads($comment_id,$new_comment_id=0){
        global $wpdb;

        $sql = "SELECT * FROM $wpdb->comments WHERE comment_parent=%d AND comment_approved=1";
        $comments_thread = $wpdb->get_results($wpdb->prepare($sql,$comment_id),ARRAY_A);
        //return $comments_thread;

        if(is_array($comments_thread) && count($comments_thread)>0){
            foreach($comments_thread as $comment){
                $comment_id = $comment['comment_ID'];
                $comment['comment_post_ID'] = $post_id;
                $new_comment_id = wp_insert_comment($comment);
            }
        }
    }

    function _clone_comment_meta($comment_id,$new_comment_id){
        global $wpdb;

        $sql_meta = "SELECT * FROM $wpdb->commentmeta WHERE comment_id=%d";
        $comments_meta = $wpdb->get_results($wpdb->prepare($sql_meta,$comment_id),ARRAY_A);

        if(is_array($comments_meta) && count($comments_meta)>0){
            foreach($comments_meta as $meta){
                $meta_key = $meta['meta_key'];
                $meta_value = $meta['meta_value'];

                $new_comment_meta = $wpdb->insert(
                    $wpdb->commentmeta,
                    array(
                        'comment_id' => $new_comment_id,
                        'meta_key' => $meta_key,
                        'meta_value' => $meta_value
                    ),
                    array(
                        '%d',
                        '%s',
                        '%s'
                    )
                );
            }
        }
    }

    function wpcc_delete_comments(){
        $response = array("status" => false);

        if ( wp_verify_nonce( $_POST['nonce'], 'wpcc_comment_delete_xxx' ) ) {
            global $wpdb;
            $post_id = $_POST['post_id'];

            $deleted = $wpdb->delete( $wpdb->comments, array( 'comment_post_ID' => $post_id ), array( '%d' ) );
            delete_post_meta($post_id,'wpcc_details');
            $response = array("status" => true, 'delete_count' => $deleted);
      	}

        wp_send_json( $response );
    }
}
$WPCommentClone = new WPCommentClone();
