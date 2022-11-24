(function (_, $) {
  /**
   * add Number.isInteger() polyfill for browsers not supporting the function ( especially most versions of IE )
   * Thanks to Walter Roman
   * https://stackoverflow.com/a/27424770/3741900
   * */
  Number.isInteger = Number.isInteger || function (value) {
    return typeof value === "number" &&
      isFinite(value) &&
      Math.floor(value) === value;
  };

  var intervals = {
    init: function ($wrapper) {
      this.wrapper = $wrapper;

      this.$ = this.wrapper.each(function (i, val) {
        var container = $(val),
          from = container.find('.field-from'),
          to = container.find('.field-to'),
          datepickers = $('').add(to).add(from);

        if ($.datepicker) {

          var siteGMTOffsetHours = +mwtsa_admin_obj.gmt_offset / 3600,
            localGMTOffsetHours = new Date().getTimezoneOffset() / 60 * -1,
            totalGMTOffsetHours = siteGMTOffsetHours - localGMTOffsetHours,
            localTime = new Date(),
            siteTime = new Date( localTime.getTime() + (totalGMTOffsetHours * 60 * 60 * 1000) ),
            dayOffset = '0';

          if (localTime.getDate() !== siteTime.getDate() || localTime.getMonth() !== siteTime.getMonth()) {
            if (localTime.getTime() < siteTime.getTime()) {
              dayOffset = '+1d';
            } else {
              dayOffset = '-1d';
            }
          }

          datepickers.datepicker({
            dateFormat: 'yy-mm-dd',
            maxDate: dayOffset,
            defaultDate: siteTime,
            showButtonPanel: true,
            closeText: 'Close',
            numberOfMonths: 2,
          });
        }

        from.on('change', function () {
          if ('' !== from.val()) {
            to.datepicker('option', 'minDate', from.val());
          }

          if (true === arguments[arguments.length - 1]) {
            return false;
          }
        });

        to.on('change', function () {
          if ('' !== to.val()) {
            from.datepicker('option', 'maxDate', to.val());
          }

          if (true === arguments[arguments.length - 1]) {
            return false;
          }
        });
      });
    }
  };

  $(document).ready(function () {
    intervals.init($('.date-interval'));

    var select2Elements = $('.select2-select');

    if (select2Elements.length > 0) {
      select2Elements.select2();
    }

  });
})(window, jQuery);