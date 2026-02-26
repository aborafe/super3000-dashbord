'use strict';

document.addEventListener('DOMContentLoaded', function () {
  let cardColor, headingColor, legendColor, labelColor, borderColor, fontFamily;
  cardColor = config.colors.cardColor;
  headingColor = config.colors.headingColor;
  legendColor = config.colors.bodyColor;
  labelColor = config.colors.textMuted;
  borderColor = config.colors.borderColor;
  fontFamily = config.fontFamily;

  const parseData = (value, fallback) => {
    if (!value) {
      return fallback;
    }
    try {
      return JSON.parse(value);
    } catch (error) {
      return fallback;
    }
  };

  const orderAreaChartEl = document.querySelector('#orderChart');
  if (orderAreaChartEl) {
    const seriesData = parseData(orderAreaChartEl.dataset.series, [10, 12, 8, 15, 12, 14, 16]);
    const orderAreaChartConfig = {
      chart: {
        height: 80,
        type: 'area',
        toolbar: { show: false },
        sparkline: { enabled: true }
      },
      markers: {
        size: 6,
        colors: 'transparent',
        strokeColors: 'transparent',
        strokeWidth: 4,
        discrete: [
          {
            fillColor: cardColor,
            seriesIndex: 0,
            dataPointIndex: Math.max(seriesData.length - 1, 0),
            strokeColor: config.colors.success,
            strokeWidth: 2,
            size: 6,
            radius: 8
          }
        ],
        offsetX: -1,
        hover: { size: 7 }
      },
      grid: {
        show: false,
        padding: { top: 15, right: 7, left: 0 }
      },
      colors: [config.colors.success],
      fill: {
        type: 'gradient',
        gradient: {
          shadeIntensity: 1,
          opacityFrom: 0.4,
          gradientToColors: [config.colors.cardColor],
          opacityTo: 0.4,
          stops: [0, 100]
        }
      },
      dataLabels: { enabled: false },
      stroke: { width: 2, curve: 'smooth' },
      series: [{ data: seriesData }],
      xaxis: {
        show: false,
        lines: { show: false },
        labels: { show: false },
        stroke: { width: 0 },
        axisBorder: { show: false }
      },
      yaxis: { stroke: { width: 0 }, show: false }
    };
    new ApexCharts(orderAreaChartEl, orderAreaChartConfig).render();
  }

  const totalRevenueChartEl = document.querySelector('#totalRevenueChart');
  if (totalRevenueChartEl) {
    const labels = parseData(totalRevenueChartEl.dataset.labels, []);
    const current = parseData(totalRevenueChartEl.dataset.current, []);
    const previous = parseData(totalRevenueChartEl.dataset.previous, []);
    const currentYear = totalRevenueChartEl.dataset.currentYear || String(new Date().getFullYear());
    const previousYear = totalRevenueChartEl.dataset.previousYear || String(new Date().getFullYear() - 1);
    const totalRevenueChartOptions = {
      series: [
        { name: currentYear, data: current },
        { name: previousYear, data: previous }
      ],
      chart: {
        height: 300,
        type: 'bar',
        toolbar: { show: false }
      },
      plotOptions: {
        bar: {
          horizontal: false,
          columnWidth: '30%',
          borderRadius: 8,
          startingShape: 'rounded',
          endingShape: 'rounded',
          borderRadiusApplication: 'around'
        }
      },
      colors: [config.colors.primary, config.colors.info],
      dataLabels: { enabled: false },
      stroke: {
        curve: 'smooth',
        width: 6,
        lineCap: 'round',
        colors: [cardColor]
      },
      legend: {
        show: true,
        horizontalAlign: 'left',
        position: 'top',
        markers: { size: 4, radius: 12, shape: 'circle', strokeWidth: 0 },
        fontSize: '13px',
        fontFamily: fontFamily,
        fontWeight: 400,
        labels: { colors: legendColor, useSeriesColors: false },
        itemMargin: { horizontal: 10 }
      },
      grid: {
        strokeDashArray: 7,
        borderColor: borderColor,
        padding: { top: 0, bottom: -8, left: 20, right: 20 }
      },
      fill: { opacity: [1, 1] },
      xaxis: {
        categories: labels,
        labels: {
          style: { fontSize: '13px', fontFamily: fontFamily, colors: labelColor }
        },
        axisTicks: { show: false },
        axisBorder: { show: false }
      },
      yaxis: {
        labels: {
          style: { fontSize: '13px', fontFamily: fontFamily, colors: labelColor }
        }
      },
      states: {
        hover: { filter: { type: 'none' } },
        active: { filter: { type: 'none' } }
      }
    };
    new ApexCharts(totalRevenueChartEl, totalRevenueChartOptions).render();
  }

  const growthChartEl = document.querySelector('#growthChart');
  if (growthChartEl) {
    const growthValue = parseInt(growthChartEl.dataset.value || '0', 10);
    const growthLabel = growthChartEl.dataset.label || 'Growth';
    const growthChartOptions = {
      series: [growthValue],
      labels: [growthLabel],
      chart: { height: 200, type: 'radialBar' },
      plotOptions: {
        radialBar: {
          size: 150,
          offsetY: 10,
          startAngle: -150,
          endAngle: 150,
          hollow: { size: '55%' },
          track: { background: cardColor, strokeWidth: '100%' },
          dataLabels: {
            name: {
              offsetY: 15,
              color: legendColor,
              fontSize: '15px',
              fontWeight: '500',
              fontFamily: fontFamily
            },
            value: {
              offsetY: -25,
              color: headingColor,
              fontSize: '22px',
              fontWeight: '500',
              fontFamily: fontFamily
            }
          }
        }
      },
      colors: [config.colors.primary],
      fill: {
        type: 'gradient',
        gradient: {
          shade: 'dark',
          shadeIntensity: 0.5,
          gradientToColors: [config.colors.primary],
          inverseColors: true,
          opacityFrom: 1,
          opacityTo: 0.6,
          stops: [30, 70, 100]
        }
      },
      stroke: { dashArray: 5 },
      grid: { padding: { top: -35, bottom: -10 } },
      states: {
        hover: { filter: { type: 'none' } },
        active: { filter: { type: 'none' } }
      }
    };
    new ApexCharts(growthChartEl, growthChartOptions).render();
  }

  const revenueBarChartEl = document.querySelector('#revenueChart');
  if (revenueBarChartEl) {
    const seriesData = parseData(revenueBarChartEl.dataset.series, [30, 40, 25, 35, 45, 20, 30]);
    const revenueBarChartConfig = {
      chart: { height: 95, type: 'bar', toolbar: { show: false } },
      plotOptions: {
        bar: {
          barHeight: '80%',
          columnWidth: '75%',
          startingShape: 'rounded',
          endingShape: 'rounded',
          borderRadius: 4,
          distributed: true
        }
      },
      grid: { show: false, padding: { top: -20, bottom: -12, left: -10, right: 0 } },
      colors: new Array(seriesData.length).fill(config.colors.primary),
      dataLabels: { enabled: false },
      series: [{ data: seriesData }],
      legend: { show: false },
      xaxis: {
        categories: parseData(revenueBarChartEl.dataset.labels, ['M', 'T', 'W', 'T', 'F', 'S', 'S']),
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: labelColor, fontSize: '13px' } }
      },
      yaxis: { labels: { show: false } }
    };
    new ApexCharts(revenueBarChartEl, revenueBarChartConfig).render();
  }

  const profileReportChartEl = document.querySelector('#profileReportChart');
  if (profileReportChartEl) {
    const seriesData = parseData(profileReportChartEl.dataset.series, [110, 270, 145, 245, 205, 285]);
    const profileReportChartConfig = {
      chart: {
        height: 75,
        width: 240,
        type: 'line',
        toolbar: { show: false },
        dropShadow: {
          enabled: true,
          top: 10,
          left: 5,
          blur: 3,
          color: config.colors.warning,
          opacity: 0.15
        },
        sparkline: { enabled: true }
      },
      grid: { show: false, padding: { right: 8 } },
      colors: [config.colors.warning],
      dataLabels: { enabled: false },
      stroke: { width: 5, curve: 'smooth' },
      series: [{ data: seriesData }],
      xaxis: {
        show: false,
        lines: { show: false },
        labels: { show: false },
        axisBorder: { show: false }
      },
      yaxis: { show: false }
    };
    new ApexCharts(profileReportChartEl, profileReportChartConfig).render();
  }

  const chartOrderStatistics = document.querySelector('#orderStatisticsChart');
  if (chartOrderStatistics) {
    const labels = parseData(chartOrderStatistics.dataset.labels, ['Pending', 'Paid', 'Shipped', 'Cancelled']);
    const seriesData = parseData(chartOrderStatistics.dataset.series, [20, 30, 25, 10]);
    const totalLabel = chartOrderStatistics.dataset.totalLabel || 'Total';
    const totalValue = seriesData.reduce((sum, item) => sum + Number(item || 0), 0);
    const orderChartConfig = {
      chart: { height: 165, width: 136, type: 'donut', offsetX: 15 },
      labels: labels,
      series: seriesData,
      colors: [config.colors.success, config.colors.primary, config.colors.secondary, config.colors.info],
      stroke: { width: 5, colors: [cardColor] },
      dataLabels: {
        enabled: false,
        formatter: function (val) {
          return parseInt(val) + '%';
        }
      },
      legend: { show: false },
      grid: { padding: { top: 0, bottom: 0, right: 15 } },
      states: {
        hover: { filter: { type: 'none' } },
        active: { filter: { type: 'none' } }
      },
      plotOptions: {
        pie: {
          donut: {
            size: '75%',
            labels: {
              show: true,
              value: {
                fontSize: '1.125rem',
                fontFamily: fontFamily,
                fontWeight: 500,
                color: headingColor,
                offsetY: -17,
                formatter: function (val) {
                  return parseInt(val) + '%';
                }
              },
              name: { offsetY: 17, fontFamily: fontFamily },
              total: {
                show: true,
                fontSize: '13px',
                color: legendColor,
                label: totalLabel,
                formatter: function () {
                  return String(Math.round(totalValue));
                }
              }
            }
          }
        }
      }
    };
    new ApexCharts(chartOrderStatistics, orderChartConfig).render();
  }

  const incomeChartEl = document.querySelector('#incomeChart');
  if (incomeChartEl) {
    const labels = parseData(incomeChartEl.dataset.labels, ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul']);
    const seriesData = parseData(incomeChartEl.dataset.series, [21, 30, 22, 42, 26, 35, 29]);
    const maxSeriesValue = seriesData.reduce((max, value) => Math.max(max, Number(value || 0)), 0);
    const yAxisMax = maxSeriesValue > 0 ? Math.ceil(maxSeriesValue * 1.15) : 10;
    const incomeChartConfig = {
      series: [{ data: seriesData }],
      chart: {
        height: 200,
        parentHeightOffset: 0,
        parentWidthOffset: 0,
        toolbar: { show: false },
        type: 'area'
      },
      dataLabels: { enabled: false },
      stroke: { width: 3, curve: 'smooth' },
      legend: { show: false },
      markers: {
        size: 6,
        colors: 'transparent',
        strokeColors: 'transparent',
        strokeWidth: 4,
        discrete: [
          {
            fillColor: config.colors.white,
            seriesIndex: 0,
            dataPointIndex: Math.max(seriesData.length - 1, 0),
            strokeColor: config.colors.primary,
            strokeWidth: 2,
            size: 6,
            radius: 8
          }
        ],
        offsetX: -1,
        hover: { size: 7 }
      },
      colors: [config.colors.primary],
      fill: {
        type: 'gradient',
        gradient: {
          shadeIntensity: 1,
          opacityFrom: 0.3,
          gradientToColors: [config.colors.cardColor],
          opacityTo: 0.3,
          stops: [0, 100]
        }
      },
      grid: {
        borderColor: borderColor,
        strokeDashArray: 8,
        padding: { top: -20, bottom: -8, left: 0, right: 8 }
      },
      xaxis: {
        categories: labels,
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { show: true, style: { fontSize: '13px', colors: labelColor } }
      },
      yaxis: { labels: { show: false }, min: 0, max: yAxisMax, tickAmount: 4 }
    };
    new ApexCharts(incomeChartEl, incomeChartConfig).render();
  }

  const weeklyExpensesEl = document.querySelector('#expensesOfWeek');
  if (weeklyExpensesEl) {
    const value = Math.min(Math.max(parseInt(weeklyExpensesEl.dataset.value || '0', 10), 0), 100);
    const weeklyExpensesConfig = {
      series: [value],
      chart: { width: 60, height: 60, type: 'radialBar' },
      plotOptions: {
        radialBar: {
          startAngle: 0,
          endAngle: 360,
          strokeWidth: '8',
          hollow: { margin: 2, size: '40%' },
          track: { background: borderColor },
          dataLabels: {
            show: true,
            name: { show: false },
            value: {
              formatter: function (val) {
                return val + '%';
              },
              offsetY: 5,
              color: legendColor,
              fontSize: '12px',
              fontFamily: fontFamily,
              show: true
            }
          }
        }
      },
      fill: { type: 'solid', colors: config.colors.primary },
      stroke: { lineCap: 'round' },
      grid: { padding: { top: -10, bottom: -15, left: -10, right: -10 } },
      states: {
        hover: { filter: { type: 'none' } },
        active: { filter: { type: 'none' } }
      }
    };
    new ApexCharts(weeklyExpensesEl, weeklyExpensesConfig).render();
  }

  document.querySelectorAll('tr[data-product-link]').forEach((row) => {
    row.addEventListener('click', (event) => {
      if (event.target.closest('a,button,.dropdown,.dropdown-menu,input,label,form')) {
        return;
      }

      const url = row.dataset.productLink;
      if (!url) {
        return;
      }

      window.location.href = url;
    });
  });
});
