const variationTextMonth = $('#trans_variation_month').data('text') || '';

function getSelectedNetwork() {
    const select = document.querySelector('#network-switcher select');
    return select ? select.value : 'global';
}

function buildUrlWithNetwork(url) {
    const network = getSelectedNetwork();
    const separator = url.includes('?') ? '&' : '?';
    return `${url}${separator}network=${encodeURIComponent(network)}`;
}

function destroyExistingChart(selector) {
    const el = document.querySelector(selector);
    if (!el) {
        return null;
    }

    if (el._chart) {
        try {
            el._chart.destroy();
        } catch (e) {
            console.warn('Erreur destruction chart:', e);
        }
        el._chart = null;
    }

    el.innerHTML = '';
    return el;
}

function formatNumber(value, decimals = 0) {
    return Number(value || 0).toLocaleString('fr-CH', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

// Taux conversion
function chargerConversionChart(path, targetSelectors, chartId, barColors) {
    $.getJSON(path, function (seriesData) {
        const tauxCourant = Number(seriesData?.taux_courant || 0);
        const evolution = Number(seriesData?.evolution_pourcent || 0);

        $(targetSelectors.text).text(tauxCourant.toFixed(3));
        $(targetSelectors.value).text(tauxCourant.toFixed(3));

        let trendHtml = '';
        if (evolution > 0) {
            trendHtml = `<span class="text-success">${evolution}% <i class="fa fa-arrow-up"></i></span> ${variationTextMonth}`;
        } else if (evolution < 0) {
            trendHtml = `<span class="text-danger">${Math.abs(evolution)}% <i class="fa fa-arrow-down"></i></span> ${variationTextMonth}`;
        } else {
            trendHtml = `<span class="text-muted">0% <i class="fa fa-minus"></i></span> stable`;
        }
        $(targetSelectors.evolution).html(trendHtml);

        const el = destroyExistingChart(chartId);
        if (!el) return;

        const labels = Array.isArray(seriesData?.labels) ? seriesData.labels : [];
        const positive = Array.isArray(seriesData?.positive) ? seriesData.positive : [];
        const negative = Array.isArray(seriesData?.negative) ? seriesData.negative : [];

        const chartOptions = {
            chart: {
                height: 114,
                stacked: true,
                type: 'bar',
                toolbar: { show: false },
                sparkline: { enabled: true }
            },
            plotOptions: {
                bar: {
                    columnWidth: '20%',
                    endingShape: 'rounded'
                }
            },
            colors: barColors,
            series: [
                { name: 'Taux en hausse', data: positive },
                { name: 'Taux en baisse', data: negative }
            ],
            xaxis: {
                categories: labels,
                labels: { show: false },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: { show: false },
            legend: { show: false },
            dataLabels: { enabled: false },
            grid: { show: false },
            tooltip: {
                x: {
                    formatter: function (_, opts) {
                        const moisMap = {
                            '01': 'Jan', '02': 'Fév', '03': 'Mar', '04': 'Avr',
                            '05': 'Mai', '06': 'Juin', '07': 'Juil', '08': 'Août',
                            '09': 'Sept', '10': 'Oct', '11': 'Nov', '12': 'Déc'
                        };

                        const label = labels[opts.dataPointIndex] || '';
                        const parts = label.split('-');

                        if (parts.length !== 2) {
                            return label;
                        }

                        const [annee, mois] = parts;
                        return `${moisMap[mois] || mois} 20${annee}`;
                    }
                },
                y: {
                    formatter: val => (Number(val || 0) / 1000).toFixed(4)
                }
            }
        };

        const chart = new ApexCharts(el, chartOptions);
        chart.render();
        el._chart = chart;
    });
}

// === Fonctions de chargement de graphiques ===

function renderSalesYearChart(apiUrl, caSelector, variationSelector, chartSelector) {
    const finalUrl = buildUrlWithNetwork(apiUrl);

    $.getJSON(finalUrl, function (salesData) {
        const caN = Number(salesData?.ca_n || 0);
        const variation = Number(salesData?.variation || 0);
        const labels = Array.isArray(salesData?.labels) ? salesData.labels : [];
        const series = Array.isArray(salesData?.series) ? salesData.series : [];

        $(caSelector).html(formatNumber(caN, 0));

        let variationHtml = '';
        if (variation > 0) {
            variationHtml = `<span class="text-success">${variation.toFixed(1)}% <i class="fa fa-arrow-up"></i></span> variation annuelle`;
        } else if (variation < 0) {
            variationHtml = `<span class="text-danger">${Math.abs(variation).toFixed(1)}% <i class="fa fa-arrow-down"></i></span> variation annuelle`;
        } else {
            variationHtml = `<span class="text-muted">0% <i class="fa fa-minus"></i></span> stable`;
        }

        $(variationSelector).html(variationHtml);

        const el = destroyExistingChart(chartSelector);
        if (!el) return;

        const options = {
            chart: {
                height: 114,
                type: 'line',
                toolbar: { show: false },
                sparkline: { enabled: true }
            },
            colors: ['#1a3767', '#e84a50'],
            dataLabels: { enabled: false },
            stroke: { width: 2, curve: 'smooth' },
            series: series,
            xaxis: {
                categories: labels,
                labels: { show: true },
                axisBorder: { show: false }
            },
            yaxis: { show: false },
            tooltip: {
                y: {
                    formatter: val => Number(val || 0).toLocaleString('fr-CH', {
                        style: 'currency',
                        currency: 'EUR'
                    })
                }
            }
        };

        const chart = new ApexCharts(el, options);
        chart.render();
        el._chart = chart;
    });
}

function renderSalesMonthChart(apiUrl, caSelector, variationSelector, chartSelector) {
    const finalUrl = buildUrlWithNetwork(apiUrl);

    $.getJSON(finalUrl, function (data) {
        const caN = Number(data?.ca_n || 0);
        const variation = Number(data?.variation || 0);
        const labels = Array.isArray(data?.labels) ? data.labels : [];
        const series = Array.isArray(data?.series) ? data.series : [];

        $(caSelector).html(formatNumber(caN, 0));

        let html = '';
        if (variation > 0) {
            html = `<span class="text-success">${Math.abs(variation).toFixed(1)}% <i class="fa fa-arrow-up"></i></span> variation mensuelle`;
        } else if (variation < 0) {
            html = `<span class="text-danger">${Math.abs(variation).toFixed(1)}% <i class="fa fa-arrow-down"></i></span> variation mensuelle`;
        } else {
            html = `<span class="text-muted">0% <i class="fa fa-minus"></i></span>`;
        }
        $(variationSelector).html(html);

        const el = destroyExistingChart(chartSelector);
        if (!el) return;

        const options = {
            chart: {
                height: 114,
                type: 'line',
                toolbar: { show: false },
                sparkline: { enabled: true }
            },
            grid: {
                show: false,
                padding: { bottom: 5, top: 5, left: 10, right: 0 }
            },
            colors: ['#4a6ae8', '#dcdcdc'],
            dataLabels: { enabled: false },
            stroke: { width: 3, curve: 'smooth' },
            series: series,
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    type: 'horizontal',
                    gradientToColors: ['#e84a50', '#999999'],
                    opacityFrom: 0,
                    opacityTo: 0.9,
                    stops: [0, 30, 70, 100]
                }
            },
            xaxis: {
                categories: labels,
                show: false,
                labels: { show: false },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: { show: false },
            tooltip: {
                x: {
                    formatter: function (_, opts) {
                        return labels[opts.dataPointIndex] || '';
                    }
                },
                y: {
                    formatter: val => formatNumber(val, 0)
                }
            }
        };

        const chart = new ApexCharts(el, options);
        chart.render();
        el._chart = chart;
    });
}

function renderTopClientsChart(apiUrl, selector) {
    const finalUrl = buildUrlWithNetwork(apiUrl);

    $.getJSON(finalUrl, function (data) {
        const el = destroyExistingChart(selector);
        if (!el) return;

        const labels = Array.isArray(data?.labels) ? data.labels : [];
        const values = Array.isArray(data?.data) ? data.data.map(v => Number(v || 0)) : [];

        if (labels.length === 0) {
            el.innerHTML = '<p class="text-muted text-center mt-5">Aucune donnée disponible.</p>';
            return;
        }

        const options = {
            chart: { type: 'bar', height: 385, toolbar: { show: false } },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 1,
                    barHeight: '70%',
                }
            },
            dataLabels: {
                enabled: true,
                position: 'right',
                offsetX: 25,
                formatter: val => formatNumber(val, 0) + ' €',
                style: { fontSize: '12px', colors: ['#333'] }
            },
            colors: ['#2e62b9'],
            xaxis: { categories: labels },
            series: [{ name: 'CA en EUR', data: values }],
            tooltip: {
                y: {
                    formatter: val => formatNumber(val, 2) + ' €'
                }
            }
        };

        const chart = new ApexCharts(el, options);
        chart.render();
        el._chart = chart;
    });
}

function renderTopCompanySalesChart(apiUrl, chartSelector, tableSelector) {
    const finalUrl = buildUrlWithNetwork(apiUrl);

    $.getJSON(finalUrl, function (result) {
        const el = destroyExistingChart(chartSelector);
        const tableEl = document.querySelector(tableSelector);

        if (!el || !tableEl) return;

        tableEl.innerHTML = '';

        if (!result || !Array.isArray(result.labels) || result.labels.length === 0) {
            el.innerHTML = '<p class="text-muted text-center mt-5">Aucune donnée disponible.</p>';
            return;
        }

        const safeValues = result.values.map(v => Number(v) || 0);

        const options = {
            chart: { type: 'donut', height: 240, toolbar: { show: false } },
            labels: result.labels,
            series: safeValues,
            legend: { show: false },
            dataLabels: {
                formatter: val => Number(val || 0).toFixed(1) + ' %'
            },
            tooltip: {
                y: {
                    formatter: val => formatNumber(val, 2) + ' €'
                }
            },
            colors: [
                '#775DD0', '#00E396', '#FEB019', '#d0223c', '#008FFB',
                '#3F51B5', '#546E7A', '#D4526E', '#8D5B4C', '#F86624'
            ],
            plotOptions: {
                pie: {
                    donut: {
                        size: '60%',
                        labels: { show: false }
                    }
                }
            }
        };

        const chart = new ApexCharts(el, options);
        chart.render();
        el._chart = chart;

        let html = '<table class="table table-sm mb-0" style="font-size: 12px;"><tbody>';
        result.labels.forEach((label, i) => {
            const val = safeValues[i];
            html += `<tr>
                        <td class="text-start text-truncate" style="max-width: 140px;">${label}</td>
                        <td class="text-end fw-bold">${formatNumber(val, 2)} €</td>
                     </tr>`;
        });
        html += '</tbody></table>';
        tableEl.innerHTML = html;
    });
}

function renderTopProductSalesChart(apiUrl, selector) {
    const finalUrl = buildUrlWithNetwork(apiUrl);

    $.getJSON(finalUrl, function (result) {
        const el = document.querySelector(selector);
        if (!el) return;

        el.innerHTML = '';

        if (!Array.isArray(result) || result.length === 0) {
            el.innerHTML = '<p class="text-muted text-center mt-5">Aucune donnée disponible.</p>';
            return;
        }

        const safeResult = result.map(p => ({
            image: p.image || '',
            label: p.label || '',
            value: Number(p.value || 0)
        }));

        const maxValue = Math.max(...safeResult.map(p => p.value), 0);

        safeResult.forEach(p => {
            const percent = maxValue > 0 ? (p.value / maxValue) * 100 : 0;

            el.innerHTML += `
                <div class="top-product-row">
                    <img src="${p.image}"
                         class="top-product-img"
                         onerror="this.src='/assets/images/no-image.png';">

                    <div class="top-product-info">
                        <div class="top-product-label">${p.label}</div>
                        <div class="top-product-bar-container">
                            <div class="top-product-bar" style="width:${percent}%"></div>
                        </div>
                    </div>

                    <div class="top-product-value">
                        ${formatNumber(p.value, 0)} €
                    </div>
                </div>
            `;
        });
    });
}

function injectImagesIntoYAxis(chartContext, data) {
    const labels = chartContext.el.querySelectorAll('.apexcharts-yaxis-label');

    labels.forEach(label => {
        let text = label.textContent.trim();
        let item = data.find(p => p.label === text);

        if (!item) {
            item = data.find(p => text === p.label + p.label);
        }

        if (!item) {
            item = data.find(p => text.indexOf(p.label) === 0);
        }

        if (item && item.image) {
            const existingImage = label.parentNode.querySelector(`image[data-label="${item.label}"]`);
            if (existingImage) return;

            let bbox = { width: 0 };
            try {
                bbox = label.getBBox();
            } catch (e) {
                // ignore
            }

            const img = document.createElementNS("http://www.w3.org/2000/svg", "image");

            const x = parseFloat(label.getAttribute('x'));
            const y = parseFloat(label.getAttribute('y'));
            const imgSize = 30;
            const gap = 8;

            const imgX = x - bbox.width - imgSize - gap;
            const imgY = y - (imgSize / 2) - 5;

            img.setAttributeNS(null, 'href', item.image);
            img.setAttributeNS(null, 'x', imgX.toString());
            img.setAttributeNS(null, 'y', imgY.toString());
            img.setAttributeNS(null, 'width', imgSize.toString());
            img.setAttributeNS(null, 'height', imgSize.toString());
            img.setAttribute('data-label', item.label);
            img.style.pointerEvents = 'none';

            label.parentNode.insertBefore(img, label);
        }
    });
}

function renderSalesEvolutionChart(apiUrl, selector, legendSelector) {
    const finalUrl = buildUrlWithNetwork(apiUrl);

    $.getJSON(finalUrl, function (response) {
        const el = destroyExistingChart(selector);
        const legendEl = document.querySelector(legendSelector);

        if (!el) return;

        const series = Array.isArray(response?.series) ? response.series : [];
        const categories = Array.isArray(response?.categories) ? response.categories : [];

        if (legendEl) {
            legendEl.innerHTML = '';
        }

        if (series.length === 0) {
            el.innerHTML = '<p class="text-muted text-center mt-5">Aucune donnée disponible.</p>';
            return;
        }

        const colors = ['#ff9800', '#40a2ed', '#26c6da', '#9b59b6', '#e74c3c'];

        const options = {
            chart: { type: 'bar', height: 340, toolbar: { show: false } },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '60%',
                    endingShape: 'flat',
                    dataLabels: { position: 'top' }
                }
            },
            colors: colors,
            dataLabels: { enabled: false },
            stroke: { show: true, width: 0, colors: ['#fff'] },
            series: series,
            xaxis: {
                categories: categories,
                labels: { style: { fontSize: '12px' } }
            },
            yaxis: {
                labels: {
                    formatter: val => formatNumber(val, 0)
                }
            },
            legend: { show: false },
            tooltip: {
                y: {
                    formatter: val => formatNumber(val, 0) + ' €'
                }
            },
            grid: {
                borderColor: '#f1f1f1',
                xaxis: { lines: { show: true } },
                yaxis: { lines: { show: true } },
                strokeDashArray: 0
            }
        };

        const chart = new ApexCharts(el, options);
        chart.render();
        el._chart = chart;

        if (legendEl) {
            const legendHtml = series.map((serie, index) => {
                return `<span class="badge me-1" style="background-color: ${colors[index]}; font-size: 12px;">${serie.name}</span>`;
            }).join(' ');

            legendEl.innerHTML = legendHtml;
        }
    });
}

function chargerCaJour(apiUrl) {
    const finalUrl = buildUrlWithNetwork(apiUrl);

    $.getJSON(finalUrl, function (data) {
        const value = data?.ca_n_j_1;
        if (value !== null && value !== undefined) {
            $('#ca-jour-kpi').text(formatNumber(value, 2));
        } else {
            $('#ca-jour-kpi').text('—');
        }
    });
}

function renderBacklogClientChart(apiUrl, chartSelector, tableSelector) {
    const finalUrl = buildUrlWithNetwork(apiUrl);

    $.getJSON(finalUrl, function (result) {
        const chartEl = destroyExistingChart(chartSelector);
        const tableEl = document.querySelector(tableSelector);

        if (!chartEl || !tableEl) return;

        tableEl.innerHTML = '';

        if (!result || !Array.isArray(result.labels) || result.labels.length === 0) {
            chartEl.innerHTML = '<p class="text-muted text-center mt-5">Aucune donnée disponible.</p>';
            return;
        }

        const safeValues = result.values.map(v => Number(v) || 0);
        const safeQuantities = result.quantities.map(v => Number(v) || 0);

        const options = {
            chart: {
                type: 'donut',
                height: 240,
                toolbar: { show: false }
            },
            labels: result.labels,
            series: safeValues,
            legend: { show: false },
            dataLabels: {
                formatter: function (val) {
                    return Number(val || 0).toFixed(1) + ' %';
                }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return formatNumber(val, 2) + ' €';
                    }
                }
            },
            colors: ['#00C9A7', '#FFC75F', '#FF6F61'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '60%',
                        labels: { show: false }
                    }
                }
            }
        };

        const chart = new ApexCharts(chartEl, options);
        chart.render();
        chartEl._chart = chart;

        let html = '<table class="table table-sm mb-0" style="font-size: 12px;"><tbody>';

        result.labels.forEach((label, i) => {
            html += `
                <tr>
                    <td class="text-start">${label}</td>
                    <td class="text-end">
                        <div class="fw-bold">
                            ${formatNumber(safeValues[i], 2)} €
                        </div>
                        <div class="text-muted small">
                            ${formatNumber(safeQuantities[i], 0)} pcs
                        </div>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        tableEl.innerHTML = html;
    });
}

function loadDashboardData() {
    renderSalesYearChart(
        window.dashboardRoutes.caParMois,
        '#ca_n',
        '#variation',
        '#primary-line-chart'
    );

    renderSalesMonthChart(
        window.dashboardRoutes.salesCurrentMonth,
        '#ca_nm',
        '#variation_m',
        '#warning-line-chart'
    );

    renderTopClientsChart(window.dashboardRoutes.topClients, '#chart-top-clients');
    renderTopCompanySalesChart(window.dashboardRoutes.topFamilySales, '#chart-top-family-sales', '#table-top-family-sales');
    renderTopProductSalesChart(window.dashboardRoutes.topProductSales, '#chart-top-product-sales');
    renderSalesEvolutionChart(window.dashboardRoutes.salesEvolution5y, '#userflow', '#apex-legend-sales');
    chargerCaJour(window.dashboardRoutes.caToday);
}
