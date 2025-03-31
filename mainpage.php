<?php
/**
 * Plugin Name: Kerkdienstgemist RSS - Kerkpoint
 * Plugin URI: https://kerkpoint.nl
 * Description: Een eenvoudige plugin om een RSS feed van Kerkdienstgemist.nl te tonen op je website.
 * Version: 1.0
 * Author: Jorian Beukens
 * Author URI: https://jorianbeukens.nl
 */

defined('ABSPATH') or die('Directe toegang is niet toegestaan.');

function rfr_add_settings_page() {
    add_menu_page(
        'Kerkdienstgemist RSS',
        'Kerkdienstgemist',
        'manage_options',
        'rss_feed_reader',
        'rfr_settings_page',
        plugin_dir_url(__FILE__) . 'kdglogo.svg',
        3
    );
}
add_action('admin_menu', 'rfr_add_settings_page');

function kdg_jb_enqueue_styles() {
    wp_enqueue_style('kdg-jb-style', plugin_dir_url(__FILE__) . 'style.css');
}
add_action('wp_enqueue_scripts', 'kdg_jb_enqueue_styles');

function kdg_jb_enqueue_scripts() {
    wp_enqueue_script('kdg-jb-popup', plugin_dir_url(__FILE__) . 'popup.js', ['jquery'], null, true);
}
add_action('wp_enqueue_scripts', 'kdg_jb_enqueue_scripts');

function rfr_settings_page() {
    $shortcodes = get_option('rfr_custom_shortcodes', []);

    if (isset($_GET['status'])) {
        echo '<div class="updated"><p>' . esc_html($_GET['status']) . '</p></div>';
    }
    ?>

    <div class="wrap">
    
    <div style="display: flex; align-items: center; gap: 10px;">
        <img src="<?php echo plugin_dir_url(__FILE__) . 'kdglogo.svg'; ?>" alt="Kerkdienstgemist logo" class="kdg-logo" width="30" height="30">
        <h1>Kerkdienstgemist RSS Instellingen</h1>
    </div>

        <h3>Nieuwe shortcode toevoegen</h3>
        <small>Vraag de gegevens op bij de beheerder van kerkdienstgemist.</small>
        <form method="post" class="kdg_form_adminmenu" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="rfr_save_custom_shortcode">
            <?php wp_nonce_field('rfr_save_shortcode_nonce', 'rfr_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="shortcode_name">Shortcode naam:</label></th>
                    <td>
                        <span><strong>kdg_</strong></span>
                        <input type="text" name="shortcode_name" id="shortcode_name" pattern="[a-zA-Z0-9_]+" required />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="station">Station ID:</label></th>
                    <td><input type="number" name="rfr_station" id="station" required /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="type">Type (audio/video):</label></th>
                    <td>
                        <select name="rfr_type" id="type">
                            <option value="audio">Audio</option>
                            <option value="video">Video</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="limit">Aantal items:</label></th>
                    <td><input type="number" name="rfr_limit" id="limit" value="5" min="1" required /></td>
                </tr>
            </table>
            <input type="submit" value="Shortcode toevoegen" class="button-primary" />
        </form>

        <h2>Gemaakte Shortcodes</h2>
        <?php if (!empty($shortcodes)) : ?>
            <ul>
                <?php foreach ($shortcodes as $shortcode_name => $shortcode) : ?>
                    <li>
                        <strong>[<?php echo esc_html($shortcode_name); ?>]</strong>
                        <span>(Station: <?php echo esc_html($shortcode['station']); ?>, Type: <?php echo esc_html($shortcode['type']); ?>, Items: <?php echo esc_html($shortcode['limit']); ?>)</span>
                        <a href="<?php echo esc_url(admin_url('admin-post.php?action=rfr_delete_shortcode&shortcode_name=' . urlencode($shortcode_name) . '&nonce=' . wp_create_nonce('rfr_delete_shortcode_nonce'))); ?>" class="delete">Verwijder</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p>Er zijn nog geen shortcodes aangemaakt.</p>
        <?php endif; ?>
    </div>
    <?php
}

// Shortcode opslaan
function rfr_save_custom_shortcode() {
    if (!isset($_POST['rfr_nonce']) || !wp_verify_nonce($_POST['rfr_nonce'], 'rfr_save_shortcode_nonce')) {
        wp_die('Ongeldige aanvraag.');
    }

    if (isset($_POST['shortcode_name'], $_POST['rfr_station'], $_POST['rfr_type'], $_POST['rfr_limit'])) {
        $shortcode_name = 'kdg_' . sanitize_text_field($_POST['shortcode_name']);
        
        $custom_shortcodes = get_option('rfr_custom_shortcodes', []);

        if (isset($custom_shortcodes[$shortcode_name])) {
            wp_redirect(admin_url('admin.php?page=rss_feed_reader&status=Deze shortcode bestaat al!'));
            exit;
        }

        $new_shortcode = [
            'station' => sanitize_text_field($_POST['rfr_station']),
            'type'    => sanitize_text_field($_POST['rfr_type']),
            'limit'   => absint($_POST['rfr_limit']),
        ];

        $custom_shortcodes[$shortcode_name] = $new_shortcode;
        update_option('rfr_custom_shortcodes', $custom_shortcodes);

        wp_redirect(admin_url('admin.php?page=rss_feed_reader&status=Shortcode toegevoegd!'));
        exit;
    }
}
add_action('admin_post_rfr_save_custom_shortcode', 'rfr_save_custom_shortcode');

// Shortcode verwijderen
function rfr_delete_shortcode() {
    if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'rfr_delete_shortcode_nonce')) {
        wp_die('Ongeldige aanvraag.');
    }

    if (isset($_GET['shortcode_name'])) {
        $shortcode_name = sanitize_text_field($_GET['shortcode_name']);
        $shortcodes = get_option('rfr_custom_shortcodes', []);

        if (isset($shortcodes[$shortcode_name])) {
            unset($shortcodes[$shortcode_name]);
            update_option('rfr_custom_shortcodes', $shortcodes);
        }

        wp_redirect(admin_url('admin.php?page=rss_feed_reader&status=Shortcode verwijderd!'));
        exit;
    }
}
add_action('admin_post_rfr_delete_shortcode', 'rfr_delete_shortcode');

// Shortcodes registreren
function rfr_register_dynamic_shortcodes() {
    $shortcodes = get_option('rfr_custom_shortcodes', []);

    foreach ($shortcodes as $shortcode_name => $shortcode) {
        add_shortcode($shortcode_name, function($atts) use ($shortcode) {
            return rfr_display_dynamic_feed(array_merge($atts, $shortcode));
        });
    }
}
add_action('init', 'rfr_register_dynamic_shortcodes');

// Feed tonen
function rfr_display_dynamic_feed($atts) {
    $atts = shortcode_atts([
        'station' => '246',
        'type'    => 'video',
        'limit'   => 5,
    ], $atts);

    $feed_url = "https://kerkdienstgemist.nl/playlists/{$atts['station']}.rss?media={$atts['type']}&limit={$atts['limit']}";
    $feed = fetch_feed($feed_url);

    if (is_wp_error($feed)) {
        return '<p>Kan de feed niet ophalen.</p>';
    }

    $feed->set_item_limit($atts['limit']);
    $items = $feed->get_items();

    if (!$items) {
        return '<p>Geen items gevonden.</p>';
    }

    $output = '<div class="rss-feed">';
    foreach ($items as $item) {
        $embed_url = esc_url($item->get_permalink()) . '/embed';
        $output .= '<div class="rss-item">';
        $description = esc_html($item->get_description());
        $title = esc_html($item->get_title());
        $output .= '<h3><a href="#" class="open-popup" data-url="' . $embed_url . '" data-description="' . $description . '" data-title="' . $title . '">' . esc_html($item->get_title()) . '</a></h3>';
        $datum = date_i18n('j F Y H:i', strtotime($item->get_date('Y-m-d H:i:s')) + 3600);
        $output .= '<p class="rss-date">' . esc_html($datum) . '</p>';
        $author = $item->get_item_tags(SIMPLEPIE_NAMESPACE_ITUNES, 'author');
        if ($author) {
            $output .= '<p>Voorganger: ' . esc_html($author[0]['data']) . '</p>';
        }
        $output .= '</div>';
    }
    $output .= '</div>';

    // Voeg de popup HTML toe
    $output .= '
        <div id="rss-popup" class="rss-popup">
            <div class="rss-popup-content">
                <span class="rss-popup-close">&times;</span>
                <iframe id="rss-popup-frame" src="" frameborder="0" allowfullscreen></iframe>
                <div id="rss-popup-description"></div>
            </div>
        </div>';

    return $output;
}

