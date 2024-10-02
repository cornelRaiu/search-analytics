(function (_, $) {

  const initCharts = function (dates, searches) {
    const lineStyle = $('#chart-type').val();
    const ctx = document.getElementById('mwtsa-stats-chart').getContext('2d');
    const stepSize = Math.ceil(Math.max(searches[0]) / 15);

    new Chart(ctx, {
      type: 'line',
      data: {
        labels: dates,
        datasets: searches.map(function (dataSet, index) {
          return {
            label: index === 0 ? mwtsa_chart_obj.strings.currentPeriod : mwtsa_chart_obj.strings.previousPeriod,
            data: Object.values(dataSet),
            borderColor: index === 0 ? 'rgba(255,99,132,1)' : 'rgba(0,0,0,1)',
            borderWidth: 1,
            steppedLine: lineStyle === 'stepped'
          };
        }),
      },
      options: {
        scales: {
          yAxes: [{
            ticks: {
              beginAtZero: true,
              callback: function (value) {
                if (Number.isInteger(value)) {
                  return value;
                }
              },
              stepSize: stepSize
            }
          }]
        },
        elements: {
          line: {
            tension: 0
          }
        },
        tooltips: {
          mode: 'index'
        }
      }
    });
  };

  _.loadCharts = function () {
    const data = {
      'nonce': mwtsa_chart_obj.nonce,
      'action': 'render_chart_data',
      'chart_ranges': $('#chart-ranges').val()
    };

    $.post(mwtsa_chart_obj.ajax_url, data, function (response) {
      if (!response.success) {
        return;
      }

      initCharts(response.data.dates, response.data.searches);
    });
  };

  _.saveAsDefault = function () {
    const data = {
      'nonce': mwtsa_chart_obj.nonce,
      'action': 'save_default_chart_settings',
      'line_style': $('#chart-type').val(),
      'chart_ranges': $('#chart-ranges').val()
    };

    $.post(mwtsa_chart_obj.ajax_url, data, function (response) {
      console.log('defaults updated');
    });
  };

  $(document).ready(function () {
    loadCharts();
  });
})(window, jQuery);