<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

?>

<div class="wps-team--return-page-link">
    <a href="<?php echo esc_url( Utils::get_setting('archive_page_link') ); ?>">
        <i class="fas fa-undo"></i>
        <span><?php echo esc_html( plugin()->translations->get( 'return_to_archive_text', _x('Back to Team Members', 'Public', 'wps-team') ) ); ?></span>
    </a>
</div>