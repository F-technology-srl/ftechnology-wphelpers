<?php

namespace Ftechnology\WPHelpers;


class WP
{

    function __construct()
    {

        if (self::funcsExist(['add_filter', 'add_action', 'remove_action'])) {

            add_filter('embed_oembed_html', function ($html, $url, $attr, $post_id) {
                $html = preg_replace('/(width|height|frameborder)="\d*"\s/', "", $html);
                $html = str_replace('<iframe', '<iframe class="embed-responsive-item"', $html);

                preg_match('/src="(.+?)"/', $html, $matches);
                $src = $matches[1];

                // add extra params to iframe src
                $params = array(
                    'rel' => 0,
                );

                $new_src = add_query_arg($params, $src);

                $html = str_replace($src, $new_src, $html);

                $attributes = 'frameborder="0"';

                $html = str_replace('></iframe>', ' ' . $attributes . '></iframe>', $html);

                return '<div class="embed-responsive embed-responsive-16by9 le-embed-custom">' . $html . '</iframe></div>';
            }, 99, 4);

            add_action('wp_head', function () {
                echo "<script> var ftechnology_ajaxurl = '" . admin_url('admin-ajax.php') . "'; </script>";
            });

            add_action('wp_ajax_ftechnology_load_more', array($this, 'ftechnologyLoadMore'));
            add_action('wp_ajax_nopriv_ftechnology_load_more', array($this, 'ftechnologyLoadMore'));

        }
    }


    public function overrideLoadMoreAction($func)
    {
        if (has_action('wp_ajax_ftechnology_load_more')) {
            remove_action('wp_ajax_ftechnology_load_more', array($this, 'ftechnologyLoadMore'));
        }
        if (has_action('wp_ajax_nopriv_ftechnology_load_more')) {
            remove_action('wp_ajax_nopriv_ftechnology_load_more', array($this, 'ftechnologyLoadMore'));
        }

        add_action('wp_ajax_ftechnology_load_more', $func);
        add_action('wp_ajax_nopriv_ftechnology_load_more', $func);

    }

    /**
     * Init Ftechnology Helpers
     */
    public static function ftechnologyLoadMore()
    {

        $ret = array(
            'status' => false,
            'elements' => array(),
        );

        try {
            if (isset($_POST['post_type']) and isset($_POST['template']) and isset($_POST['posts_per_page']) and isset($_POST['page'])) {
                $post_type = $_POST['post_type'];
                $template = base64_decode($_POST['template']);
                $posts_per_page = $_POST['posts_per_page'];
                $page = $_POST['page'];

                $tax_query = array();
                if (isset($_POST['terms'])) {
                    $tax_query['relation'] = 'AND';
                    foreach ($_POST['terms'] as $term) {
                        $tax_query[] = array(
                            'taxonomy' => $term['type'],
                            'terms' => $term['ids'],
                            'field' => 'term_id',
                            'include_children' => false,
                            'operator' => 'IN'
                        );
                    }
                }

                $args = array(
                    'post_type' => $post_type,
                    'tax_query' => $tax_query,
                    'posts_per_page' => $posts_per_page,
                    'paged' => $page,
                    'post_status' => 'publish',
                );

                $query = new \WP_Query($args);
                if ($query->have_posts()) {
                    $ret['status'] = true;
                    while ($query->have_posts()) {
                        $query->the_post();
                        $ret['elements'][] = self::loadBladePartContent($template);
                    }
                }
                wp_reset_postdata();

            }
        } catch (\Exception $e) {
            // errore
            var_dump($e->getMessage());
        }

        wp_send_json($ret);
    }


    /**
     * @param $logo
     * @param $width
     * @param $height
     * @param $hidePostTypeLink
     * @param $extraStyles
     */
    private static function stylizeAdmin($logo, $width, $height, $hidePostTypeLink, $extraStyles)
    {
        ?>
        <style type="text/css">
            <?php
            if($logo and $width and $height){
                ?>
            .login h1 a {
                background-image: url(<?php echo $logo; ?>) !important;
                background-size: <?php echo $width; ?>px <?php echo $height; ?>px !important;
                width: <?php echo $width; ?>px !important;
                height: <?php echo $height; ?>px !important;
                margin: 0 auto 20px auto !important;
            }

            <?php
        }
        if($hidePostTypeLink){
            foreach ($hidePostTypeLink as $type){
                ?>
            body.wp-admin.post-type-<?php echo $type; ?> #preview-action, body.wp-admin.post-type-<?php echo $type; ?> #edit-slug-box, body.wp-admin.post-type-<?php echo $type; ?> #message.updated.notice-success p a, body.wp-admin.post-type-<?php echo $type; ?> #posts-filter .row-actions .view {
                display: none !important;
            }

            <?php
        }
    }
    if($extraStyles){
        echo $extraStyles;
    }
    ?>
        </style>
        <?php
    }

    /**
     * @param bool|string $logo
     * @param bool|integer $width
     * @param bool|integer $height
     * @param array $hidePostTypeLink
     * @param bool|string $extraStyles
     */
    public static function stylizeAdminAddAction($logo = false, $width = false, $height = false, $hidePostTypeLink = [], $extraStyles = false)
    {
        if (self::funcsExist(['add_action'])) {
            add_action('login_enqueue_scripts', function () use ($logo, $width, $height, $hidePostTypeLink, $extraStyles) {
                self::stylizeAdmin($logo, $width, $height, $hidePostTypeLink, $extraStyles);
            });
            add_action('admin_print_styles', function () use ($logo, $width, $height, $hidePostTypeLink, $extraStyles) {
                self::stylizeAdmin($logo, $width, $height, $hidePostTypeLink, $extraStyles);
            });
        }
    }


    /**
     * @param $template_name
     * @param array $data
     * @return false|string
     */
    public static function loadTemplatePartContent($template_name, $data = array())
    {
        try {
            ob_start();
            if (self::funcsExist(['set_query_var', 'get_template_part'])) {
                foreach ($data as $key => $value) {
                    set_query_var($key, $value);
                }
                get_template_part($template_name);
            }
            $var = ob_get_contents();
            ob_end_clean();
            return $var;
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @param $template_name
     * @param array $data
     * @return false|string
     */
    public static function loadBladePartContent($template_name, $data = array())
    {
        try {
            ob_start();
            $vecData = [];
            foreach ($data as $key => $value) {
                $vecData[] = ${$key} = $value;
            }
            if (self::funcsExist(['\App\template_path', 'locate_template'])) {
                include \App\template_path(locate_template($template_name), $vecData);
            }
            $var = ob_get_contents();
            ob_end_clean();
            return $var;
        } catch (\Exception $e) {
            //return '';
            return 'ERROR: ' . $e->getMessage();
        }
    }


    /**
     * @param $id
     * @param bool $lang
     * @param bool $esc_url
     * @return bool|string
     */
    public static function getPostEditLink($id, $lang = false, $esc_url = true)
    {

        // https://codex.wordpress.org/Template_Tags/get_edit_post_link
        // get_edit_post_link( $id, $context )

        $url = false;
        if (self::funcsExist(['get_site_url', 'esc_url'])) {
            $id = intval($id);
            if ($lang) {
                $lang = "&lang=" . $lang;
            }
            $url = get_site_url() . '/wp-admin/post.php?post=' . $id . '&action=edit' . $lang;
            if ($esc_url) {
                return esc_url($url);
            }
        }
        return $url;
    }


    /**
     * @param $posts
     * @param $orderBy
     * @param string $order
     * @param bool $unique
     * @return array|bool|false
     */
    public static function orderArrayPostBy($posts, $orderBy, $order = 'ASC', $unique = true)
    {
        if (!is_array($posts)) {
            return false;
        }
        if (self::funcsExist(['\Ftechnology\WPHelpers\Sort_Posts', 'wp_list_pluck'])) {
            usort($posts, array(new \Ftechnology\WPHelpers\Sort_Posts($orderBy, $order), 'sort'));
            // use post ids as the array keys
            if ($unique && count($posts)) {
                $posts = array_combine(wp_list_pluck($posts, 'ID'), $posts);
            }
        }

        return $posts;
    }


    /**
     * @param string $forceTitle
     * @param int $id
     * @return string
     */
    public static function getAltTag($forceTitle = false, $id = 0)
    {
        $return = '';
        if (self::funcsExist(['get_post_type', 'wp_get_attachment_metadata', 'get_the_title'])) {
            if ($forceTitle) {
                return $forceTitle;
            } elseif ($id > 0) {

                $curr_type = get_post_type($id);

                if ($curr_type == 'attachment') {
                    $meta = wp_get_attachment_metadata($id);

                    if (isset($meta['image_meta']['title'])) {
                        $return = addslashes($meta['image_meta']['title']);
                    }

                } elseif (get_the_title($id)) {
                    $return = addslashes(get_the_title($id));
                }
            }
        }
        return $return;
    }

    /**
     * Utility di stampa (o ritorno html) di un attachment ID specifico
     *
     * @param int $attachment_id
     * @param string $size
     * @param array $attr
     * @param bool $initial_alt
     * @param bool $print
     * @return string
     */
    public static function printImage($attachment_id = 0, $size = 'thumbnail', $attr = [], $initial_alt = false, $print = true)
    {
        $html = '';
        if (self::funcsExist(['wp_get_attachment_image_src', 'get_the_title', 'get_the_ID', 'get_post_meta'])) {
            $img = wp_get_attachment_image_src($attachment_id, $size);
            if ($img) {
                $vec_attr = [];
                foreach ($attr as $key => $value) {
                    $vec_attr[] = $key . '="' . $value . '"';
                }
                if (!$initial_alt) {
                    $initial_alt = get_the_title(get_the_ID());
                }
                if (get_the_title($attachment_id)) {
                    $initial_alt = get_the_title($attachment_id);
                }
                if (get_post_meta($attachment_id, '_wp_attachment_image_alt', TRUE)) {
                    $initial_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', TRUE);
                }
                $vec_attr[] = 'alt="' . $initial_alt . '"';
                $html = '<img src="' . $img[0] . '" ' . implode(' ', $vec_attr) . ' />';
            }
        }
        if ($print) {
            echo $html;
        } else {
            return $html;
        }
    }

    /**
     * @param $file
     * @param $content
     * @param bool $encode
     * @param int $append pass zero to override
     * @return bool
     */
    public static function saveLog($file, $content, $encode = false, $append = FILE_APPEND)
    {
        if (self::funcsExist(['get_post_type', 'file_put_contents'])) {
            try {
                $upload_array = wp_upload_dir();
                $upload_dir = $upload_array['basedir'];
                if ($encode) {
                    $content = json_encode($content);
                }
                if ($append === FILE_APPEND) {
                    $content .= PHP_EOL;
                }
                file_put_contents($upload_dir . '/' . $file, $content, $append);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * @param $vecFunc
     * @return bool
     */
    private static function funcsExist($vecFunc)
    {
        foreach ($vecFunc as $func) {
            if (!function_exists($func)) {
                return false;
                break;
            }
        }
        return true;
    }


    /**
     * Modification of "Build a tree from a flat array in PHP"
     *
     * Authors: @DSkinner, @ImmortalFirefly and @SteveEdson
     *
     * @link https://stackoverflow.com/a/28429487/2078474
     * @param array $elements
     * @param int $parentId
     * @return array
     */
    private static function buildTree(array &$elements, $parentId = 0)
    {
        $branch = array();
        foreach ($elements as &$element) {
            if ($element->menu_item_parent == $parentId) {
                $children = self::buildTree($elements, $element->ID);
                if ($children) {
                    $element->item_children = $children;
                }

                $branch[$element->ID] = $element;
                unset($element);
            }
        }
        return $branch;
    }

    /**
     * Transform a navigational menu to it's tree structure
     * @param $menu_id
     * @return array|null
     */
    public static function wpMenuToTree($menu_id)
    {
        $items = wp_get_nav_menu_items($menu_id);
        if ($items) {
            return self::buildTree($items, 0);
        } else {
            return null;
        }
    }

    /**
     * Rimuove le voci di menù nell'admin, è possibile passare le voci da rimuovere per tutti o solo per i NON ADMIN
     * @param array $removeForAll
     * @param array $removeForNONAdmin
     */
    public static function removeAdminMenuPage($removeForAll = [], $removeForNONAdmin = [])
    {
        if (self::funcsExist(['add_action'])) {

            add_action('admin_menu', function () use ($removeForAll, $removeForNONAdmin) {

                if ($removeForAll) {
                    foreach ($removeForAll as $page1) {
                        remove_menu_page($page1);
                    }
                }
                if ($removeForNONAdmin) {
                    $user = wp_get_current_user();
                    if (!in_array("administrator", $user->roles)) {
                        foreach ($removeForNONAdmin as $page2) {
                            remove_menu_page($page2);
                        }
                    }
                }
            });
        }
    }

    /**
     * Rimuove le voci in admin bar, è possibile passare le voci da rimuovere per tutti o solo per i NON ADMIN
     *
     * @param array $removeForAll
     * @param array $removeForNONAdmin
     */
    public static function removeAdminMenuBar($removeForAll = [], $removeForNONAdmin = [])
    {
        if (self::funcsExist(['add_action'])) {
            add_action('wp_before_admin_bar_render', function () use ($removeForAll, $removeForNONAdmin) {
                global $wp_admin_bar;
                if ($removeForAll) {
                    foreach ($removeForAll as $page1) {
                        $wp_admin_bar->remove_menu($page1);
                    }
                }
                if ($removeForNONAdmin) {
                    $user = wp_get_current_user();
                    if (!in_array("administrator", $user->roles)) {
                        foreach ($removeForNONAdmin as $page2) {
                            $wp_admin_bar->remove_menu($page2);
                        }
                    }
                }
            });
        }
    }

    /**
     * Disabilita le single per i post type specificati
     *
     * @param array $postTypes
     */
    public static function disableSingle($postTypes = [])
    {
        if (self::funcsExist(['add_action'])) {
            add_action('template_redirect', function () use ($postTypes) {
                $queried_post_type = get_query_var('post_type');
                if (is_single() && in_array($queried_post_type, $postTypes)) {
                    wp_redirect(home_url(), 301);
                    exit;
                }
            });

            $vecSelectors = [];
            foreach ($postTypes as $type) {
                $vecSelectors[] = 'body.wp-admin.post-type-' . $type . ' #preview-action, body.wp-admin.post-type-' . $type . ' #edit-slug-box, body.wp-admin.post-type-' . $type . ' #message.updated.notice-success p a, body.wp-admin.post-type-' . $type . ' #posts-filter .row-actions .view';
            }
            add_action('login_enqueue_scripts', function () use ($vecSelectors) {
                echo '<style type="text/css">' . implode(',', $vecSelectors) . '{display: none !important;}</style>';
            });
            add_action('admin_print_styles', function () use ($vecSelectors) {
                echo '<style type="text/css">' . implode(',', $vecSelectors) . '{display: none !important;}</style>';
            });
        }
    }

    /**
     * Rinuove l'editor in admin per i tipi/template specificati in base agli array
     *
     * @param array $postTypes
     * @param array $templates (views/template-name.blade.php)
     * @param array $ids list of id page
     * @param boolean $hideInFrontPage true/false for hide editor in home page
     */
    public static function removeEditor($postTypes = [], $templates = [], $ids = [], $hideInFrontPage = false)
    {
        global $pagenow;
        if (self::funcsExist(['add_action'])) {
            add_action('admin_init', function () use ($postTypes, $templates, $ids, $hideInFrontPage, $pagenow) {
                if ($pagenow == 'post-new.php') {
                    if (isset($_GET['post_type'])) {
                        $type = $_GET['post_type'];
                        if (in_array($type, $postTypes) || $type == 'page') {
                            remove_post_type_support($type, 'editor');
                        }
                    }
                } else if (isset($_GET['post']) or isset($_POST['post_ID'])) {
                    if (isset($_GET['post'])) {
                        $post_id = $_GET['post'];
                    } elseif (isset($_POST['post_ID'])) {
                        $post_id = $_POST['post_ID'];
                    }

                    if (!isset($post_id)) {
                        return;
                    }
                    $template_file = get_post_meta($post_id, '_wp_page_template', true);
                    $post = get_post($post_id);

                    if($post) {
                        if(!empty($post->post_type)) {
                            if(in_array($post->post_type, $postTypes)) {
                                remove_post_type_support($post->post_type, 'editor');
                            }
                        }
                    }

                    if (in_array($template_file, $templates) or in_array($post_id, $ids)) {
                        remove_post_type_support($post->post_type, 'editor');
                    }

                    if ((int)get_option('page_on_front') == $post_id && $hideInFrontPage) {
                        remove_post_type_support('page', 'editor');
                    }
                }
            });
        }
    }

    /**
     * Rimuove gli attributi superflui sui tag script per una corretta validazione w3c
     */
    public static function disableJsCssAttributes()
    {
        if (self::funcsExist(['add_filter', 'remove_action'])) {

            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('wp_print_styles', 'print_emoji_styles');
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('admin_print_styles', 'print_emoji_styles');
            add_filter('style_loader_tag', function ($tag, $handle) {
                return preg_replace("/type=['\"]text\/(javascript|css)['\"]/", '', $tag);
            }, 10, 2);
            add_filter('script_loader_tag', function ($tag, $handle) {
                return preg_replace("/type=['\"]text\/(javascript|css)['\"]/", '', $tag);
            }, 10, 2);


            add_action('widgets_init', function () {
                global $wp_widget_factory;
                remove_action('wp_head', array($wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style'));
            });

        }
    }


    /**
     * Utility per il ridimensionamento dei campi Wysiwyg ad un altezza personalizzata
     *
     * @param int $height
     */
    public static function resizeWysiwygHeight($height = 100)
    {
        if (self::funcsExist(['add_action'])) {

            add_action('acf/input/admin_footer', function () use ($height) {
                ?>
                <style>
                    .acf-editor-wrap iframe {
                        min-height: 0;
                    }
                </style>
                <script>
                    (function ($) {
                        // (filter called before the tinyMCE instance is created)
                        acf.add_filter('wysiwyg_tinymce_settings', function (mceInit, id, $field) {
                            // enable autoresizing of the WYSIWYG editor
                            mceInit.wp_autoresize_on = false;
                            return mceInit;
                        });
                        // (action called when a WYSIWYG tinymce element has been initialized)
                        acf.add_action('wysiwyg_tinymce_init', function (ed, id, mceInit, $field) {
                            // reduce tinymce's min-height settings
                            ed.settings.autoresize_min_height = <?php echo $height; ?>;
                            // reduce iframe's 'height' style to match tinymce settings
                            $('.acf-editor-wrap iframe').css('height', '<?php echo $height; ?>px');
                        });
                    })(jQuery)
                </script>
                <?php
            });


        }
    }


    /**
     * Returns all child nav_menu_items under a specific parent
     *
     * @param int the parent nav_menu_item ID
     * @param array nav_menu_items
     * @param bool gives all children or direct children only
     *
     * @return array returns filtered array of nav_menu_items
     */
    public static function get_nav_menu_item_children($parent_id, $nav_menu_items, $depth = true)
    {
        $nav_menu_item_list = array();
        foreach ((array)$nav_menu_items as $nav_menu_item) {
            if ($nav_menu_item->menu_item_parent == $parent_id) {
                $nav_menu_item_list[] = $nav_menu_item;
                if ($depth) {
                    if ($children = get_nav_menu_item_children($nav_menu_item->ID, $nav_menu_items)) {
                        $nav_menu_item_list = array_merge($nav_menu_item_list, $children);
                    }
                }
            }
        }

        return $nav_menu_item_list;
    }


    public static function getIdMenuFromLocation($location)
    {
        $theme_locations = get_nav_menu_locations();

        $menu_obj = get_term($theme_locations[$location], 'nav_menu');

        $menu_id = $menu_obj->term_id;

        return $menu_id;
    }

    /**
     * Disable WP Comments
     */
    public static function disableComments()
    {

        if (self::funcsExist(['add_action'])) {
            add_action('admin_init', function () {
                // Redirect any user trying to access comments page
                global $pagenow;

                if ($pagenow === 'edit-comments.php') {
                    wp_redirect(admin_url());
                    exit;
                }

                // Remove comments metabox from dashboard
                remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');

                // Disable support for comments and trackbacks in post types
                foreach (get_post_types() as $post_type) {
                    if (post_type_supports($post_type, 'comments')) {
                        remove_post_type_support($post_type, 'comments');
                        remove_post_type_support($post_type, 'trackbacks');
                    }
                }
            });
        }

        if (self::funcsExist(['add_filter', 'add_action'])) {
            add_filter('comments_open', '__return_false', 20, 2);
            add_filter('pings_open', '__return_false', 20, 2);
            add_filter('comments_array', '__return_empty_array', 10, 2);

            // Remove comments page in menu
            add_action('admin_menu', function () {
                remove_menu_page('edit-comments.php');
            });

            // Remove comments links from admin bar
            add_action('init', function () {
                if (is_admin_bar_showing()) {
                    remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
                }
            });
        }

    }

}
