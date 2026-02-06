window.addEventListener('load', () => {
    (function ($) {

        $(document).on('ajaxComplete', (event, xhr, settings) => {
            // Ensure data exists and target only VC shortcode load requests
            const data = settings?.data;
            if ( typeof data !== 'string' || ! data.includes('action=vc_load_shortcode') ) return;

            try {
                const decoded = decodeURIComponent(settings.data);

                // Dynamically extract all shortcode tags
                const shortcodeMatches = [...decoded.matchAll(/shortcodes\[\d+\]\[tag\]=([^\&]+)/g)];

                if (!shortcodeMatches.length) return;

                // Check if our shortcode is present
                const hasTeamShortcode = shortcodeMatches.some(match => match[1] === 'wpspeedo-team');
                if (!hasTeamShortcode) return;

                // Trigger event inside VC iframe safely
                const iframe = document.querySelector('#vc_inline-frame');
                if (!iframe || !iframe.contentWindow || !iframe.contentDocument) return;

                // Wait a tick to ensure iframe content is ready
                setTimeout(() => {
                    const $iframeWin = iframe.contentWindow.jQuery;
                    if (typeof $iframeWin !== 'function') return;

                    $iframeWin(iframe.contentDocument).trigger('wps_team:init');
                }, 150);

            } catch (err) {
                console.warn('wps_team:init trigger failed:', err);
            }
        });

    })(jQuery);
});