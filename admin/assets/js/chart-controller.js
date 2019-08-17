(function(_, $) {

    _.loadCharts = function(){
        var lineStyle = $('#chart-type').val();
        var chartRanges = $('#chart-ranges').val();
        var chartContent = $('#chart-content');

        var data = {
            'action': 'render_chart_data',
            'line_style': lineStyle,
            'chart_ranges': chartRanges
        };

        $.post( mwtsa_obj.ajax_url, data, function( response ) {
            chartContent.html( response );
        } );
    };

    _.saveAsDefault = function() {
        var lineStyle = $('#chart-type').val();
        var chartRanges = $('#chart-ranges').val();

        var data = {
            'action': 'save_default_chart_settings',
            'line_style': lineStyle,
            'chart_ranges': chartRanges
        };

        $.post( mwtsa_obj.ajax_url, data, function( response ) {
            console.log( 'defaults updated' );
        } );
    };

    $(document).ready(function(){
        loadCharts();
    });
})(window, jQuery);