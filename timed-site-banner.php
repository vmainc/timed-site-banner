<?php
/*
Plugin Name: Timed Site Banner
Description: Displays a custom banner at the top of the site with specific start and end times, and allows style customization.
Version: 1.0
Author: Doug Higson
*/

function tsb_enqueue_scripts() {
    wp_enqueue_style('tsb-style', plugin_dir_url(__FILE__) . 'css/timed-site-banner.css');
    wp_enqueue_script('tsb-script', plugin_dir_url(__FILE__) . 'js/timed-site-banner.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'tsb_enqueue_scripts');

function tsb_add_admin_menu() {
    add_menu_page('Timed Site Banner', 'Timed Site Banner', 'manage_options', 'timed-site-banner', 'tsb_banners_page', 'dashicons-welcome-widgets-menus');
    add_submenu_page('timed-site-banner', 'Create Banner', 'Create Banner', 'manage_options', 'timed-site-banner-create', 'tsb_create_banner_page');
}
add_action('admin_menu', 'tsb_add_admin_menu');

function tsb_enqueue_admin_scripts($hook) {
    if ($hook !== 'toplevel_page_timed-site-banner' && $hook !== 'timed-site-banner_page_timed-site-banner-create') {
        return;
    }
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('tsb-admin-script', plugin_dir_url(__FILE__) . 'js/tsb-admin.js', array('wp-color-picker'), false, true);
    wp_enqueue_editor(); // Enqueue the WordPress editor
}
add_action('admin_enqueue_scripts', 'tsb_enqueue_admin_scripts');

function tsb_register_settings() {
    register_setting('tsb_settings_group', 'tsb_banners');
}
add_action('admin_init', 'tsb_register_settings');

function tsb_banners_page() {
    ?>
    <div class="wrap">
        <h1>All Banners</h1>
        <table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th id="title" class="manage-column column-title column-primary">Title</th>
                    <th id="start_time" class="manage-column column-start_time">Start Time</th>
                    <th id="end_time" class="manage-column column-end_time">End Time</th>
                    <th id="active" class="manage-column column-active">Active</th>
                    <th id="actions" class="manage-column column-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $banners = get_option('tsb_banners', array());
                if (!empty($banners)) {
                    foreach ($banners as $index => $banner) {
                        ?>
                        <tr>
                            <td><?php echo esc_html($banner['title']); ?></td>
                            <td><?php echo esc_html($banner['start_time']); ?></td>
                            <td><?php echo esc_html($banner['end_time']); ?></td>
                            <td><?php echo $banner['active'] ? 'Yes' : 'No'; ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=timed-site-banner-create&edit=' . $index); ?>">Edit</a> |
                                <a href="<?php echo admin_url('admin.php?page=timed-site-banner&delete=' . $index); ?>">Delete</a> |
                                <a href="<?php echo admin_url('admin.php?page=timed-site-banner&activate=' . $index); ?>">Activate</a>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="5">No banners found.</td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
}

function tsb_create_banner_page() {
    $banners = get_option('tsb_banners', array());
    $index = isset($_GET['edit']) ? intval($_GET['edit']) : -1;
    $banner = $index >= 0 ? $banners[$index] : array(
        'title' => '',
        'content' => '',
        'start_time' => '',
        'end_time' => '',
        'background_color' => '',
        'text_color' => '',
        'padding' => '',
        'active' => false,
    );

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $banner['title'] = sanitize_text_field($_POST['title']);
        $banner['content'] = wp_kses_post($_POST['content']);
        $banner['start_time'] = sanitize_text_field($_POST['start_time']);
        $banner['end_time'] = sanitize_text_field($_POST['end_time']);
        $banner['background_color'] = sanitize_hex_color($_POST['background_color']);
        $banner['text_color'] = sanitize_hex_color($_POST['text_color']);
        $banner['padding'] = sanitize_text_field($_POST['padding']);
        
        if ($index >= 0) {
            $banners[$index] = $banner;
        } else {
            $banners[] = $banner;
        }

        update_option('tsb_banners', $banners);

        // Redirect to the banners page
        wp_redirect(admin_url('admin.php?page=timed-site-banner'));
        exit;
    }

    ?>
    <div class="wrap">
        <h1><?php echo $index >= 0 ? 'Edit Banner' : 'Create Banner'; ?></h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="title">Title</label></th>
                    <td><input name="title" type="text" id="title" value="<?php echo esc_attr($banner['title']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="content">Content</label></th>
                    <td><?php
                        wp_editor($banner['content'], 'content', array(
                            'textarea_name' => 'content',
                            'media_buttons' => false,
                            'textarea_rows' => 10,
                            'teeny'         => true
                        ));
                        ?></td>
                </tr>
                <tr>
                    <th scope="row"><label for="start_time">Start Time (YYYY-MM-DD HH:MM)</label></th>
                    <td><input name="start_time" type="text" id="start_time" value="<?php echo esc_attr($banner['start_time']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="end_time">End Time (YYYY-MM-DD HH:MM)</label></th>
                    <td><input name="end_time" type="text" id="end_time" value="<?php echo esc_attr($banner['end_time']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="background_color">Background Color</label></th>
                    <td><input name="background_color" type="text" id="background_color" value="<?php echo esc_attr($banner['background_color']); ?>" class="my-color-field" data-default-color="#ff0"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="text_color">Text Color</label></th>
                    <td><input name="text_color" type="text" id="text_color" value="<?php echo esc_attr($banner['text_color']); ?>" class="my-color-field" data-default-color="#000"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="padding">Padding (e.g., 10px 0)</label></th>
                    <td><input name="padding" type="text" id="padding" value="<?php echo esc_attr($banner['padding']); ?>" class="regular-text"></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" class="button-primary" value="<?php echo $index >= 0 ? 'Update Banner' : 'Create Banner'; ?>">
            </p>
        </form>
    </div>
    <?php
}

function tsb_handle_actions() {
    if (isset($_GET['delete'])) {
        $banners = get_option('tsb_banners', array());
        $index = intval($_GET['delete']);
        if (isset($banners[$index])) {
            array_splice($banners, $index, 1);
            update_option('tsb_banners', $banners);
        }
        wp_redirect(admin_url('admin.php?page=timed-site-banner'));
        exit;
    }

    if (isset($_GET['activate'])) {
        $banners = get_option('tsb_banners', array());
        $index = intval($_GET['activate']);
        foreach ($banners as &$banner) {
            $banner['active'] = false;
        }
        if (isset($banners[$index])) {
            $banners[$index]['active'] = true;
            update_option('tsb_banners', $banners);
        }
        wp_redirect(admin_url('admin.php?page=timed-site-banner'));
        exit;
    }
}
add_action('admin_init', 'tsb_handle_actions');

function tsb_display_banner() {
    $banners = get_option('tsb_banners', array());
    foreach ($banners as $banner) {
        if ($banner['active']) {
            $current_time = current_time('Y-m-d H:i');
            $start_time = $banner['start_time'];
            $end_time = $banner['end_time'];

            if ($current_time >= $start_time && $current_time <= $end_time) {
                $background_color = $banner['background_color'] ?: '#ff0';
                $text_color = $banner['text_color'] ?: '#000';
                $padding = $banner['padding'] ?: '10px 0';

                echo '<div class="tsb-banner" style="background-color: ' . esc_attr($background_color) . '; color: ' . esc_attr($text_color) . '; padding: ' . esc_attr($padding) . ';">' . apply_filters('the_content', $banner['content']) . '</div>';
            }
            break;
        }
    }
}
add_action('wp_head', 'tsb_display_banner');
?>
