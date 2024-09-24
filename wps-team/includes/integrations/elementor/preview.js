jQuery(function($) {

    var wps_widget = function( $scope, $ ) {
        var $wps_widget = $scope.find('.wps-widget.wps-widget--team');
        if ( ! $wps_widget.length ) return;
        $(document).trigger( 'wps_team:init' );
    }

    elementorFrontend.hooks.addAction( 'frontend/element_ready/wpspeedo_team.default', wps_widget );
    
});