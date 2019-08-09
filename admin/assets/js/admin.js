(function(_, $) {

    /**
     * add Number.isInteger() polyfill for browsers not supporting the function ( especially most versions of IE )
     * Thanks to Walter Roman
     * https://stackoverflow.com/a/27424770/3741900
     * */
    Number.isInteger = Number.isInteger || function(value) {
        return typeof value === "number" &&
            isFinite(value) &&
            Math.floor(value) === value;
    };

    var intervals = {
        init: function( $wrapper ) {
            this.wrapper = $wrapper;

            this.$ = this.wrapper.each( function( i, val ) {
                var container   = $( val ),
                    from        = container.find( '.field-from' ),
                    to          = container.find( '.field-to' ),
                    datepickers = $( '' ).add( to ).add( from );

                if ( jQuery.datepicker ) {

                    var	siteGMTOffsetHours  = parseFloat( mwtsa_obj.gmt_offset ),
                        localGMTOffsetHours = new Date().getTimezoneOffset() / 60 * -1,
                        totalGMTOffsetHours = siteGMTOffsetHours - localGMTOffsetHours,
                        localTime           = new Date(),
                        siteTime            = new Date( localTime.getTime() + ( totalGMTOffsetHours * 60 * 60 * 1000 ) ),
                        dayOffset           = '0';

                    if ( localTime.getDate() !== siteTime.getDate() || localTime.getMonth() !== siteTime.getMonth() ) {
                        if ( localTime.getTime() < siteTime.getTime() ) {
                            dayOffset = '+1d';
                        } else {
                            dayOffset = '-1d';
                        }
                    }

                    datepickers.datepicker({
                        dateFormat: mwtsa_obj.date_format,
                        maxDate: dayOffset,
                        defaultDate: siteTime,
                        showButtonPanel: true,
                        closeText: 'Clear',
                        numberOfMonths: 2,
                        beforeShow: function() {
                            $( this ).prop( 'disabled', true );
                        },
                        onClose: function() {
                            $( this ).prop( 'disabled', false );

                            var event = arguments.callee.caller.caller.arguments[0];

                            if ($(event.delegateTarget).hasClass('ui-datepicker-close')) {
                                $(this).val('');
                            }
                        }
                    });

                    datepickers.datepicker( 'widget' ).addClass( 'stream-datepicker' );
                }

                from.on( 'change', function() {
                    if ( '' !== from.val() ) {
                        to.datepicker( 'option', 'minDate', from.val() );
                    }

                    if ( true === arguments[ arguments.length - 1 ] ) {
                        return false;
                    }
                });

                to.on( 'change', function() {
                    if ( '' !== to.val() ) {
                        from.datepicker( 'option', 'maxDate', to.val() );
                    }

                    if ( true === arguments[ arguments.length - 1 ] ) {
                        return false;
                    }
                });
            });
        }
    };

    $( document ).ready( function() {
        intervals.init( $( '.date-interval' ) );
    });
})(window, jQuery);