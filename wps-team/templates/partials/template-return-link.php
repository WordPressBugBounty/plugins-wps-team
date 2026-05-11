<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

/**
 * Return link.
 *
 * Override: copy to `wpspeedo-team/` (mu-plugins, child, or parent theme; see {@link https://wpspeedo.com/docs/team-template-override/}).
 * Folder: filter `wpspeedo_team/template/folder`, {@see Utils::load_template()}.
 *
 * @version 1.0.0 Bump when this file changes; compare after updates if you override.
 */
?>

<div class="wps-team--return-page-link">
    <a href="<?php echo esc_url( Utils::get_setting('archive_page_link') ); ?>">
        <i class="fas fa-undo"></i>
        <span><?php echo esc_html( plugin()->translations->get( 'return_to_archive_text', _x('Back to Team Members', 'Public', 'wps-team') ) ); ?></span>
    </a>
</div>