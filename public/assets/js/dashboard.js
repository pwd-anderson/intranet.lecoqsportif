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

// Taux conversion — affichage simple dans le header
function chargerTauxHeaderSimple(path) {
    $.getJSON(path, function (data) {
        const taux = Number(data?.taux_courant || 0);
        if (taux > 0) {
            $('#header-eur-usd').text('1 EUR = ' + taux.toFixed(3) + ' USD');
        }
    });
}

// CA Mensuel N vs N-1 — barres groupées
function renderCaMensuelChart(data, selector, height = 195, colors = ['#1a3767', '#90aad4']) {
    const el = destroyExistingChart(selector);
    if (!el) return;

    const year   = data.year || new Date().getFullYear();
    const labels = data.labels || [];
    const series = data.series || [];

    const options = {
        chart: { type: 'bar', height: height, toolbar: { show: false } },
        plotOptions: { bar: { horizontal: false, columnWidth: '80%', borderRadius: 2 } },
        colors: colors,
        dataLabels: { enabled: false },
        series: [...series].reverse(),
        xaxis: {
            categories: labels,
            labels: { style: { fontSize: '11px' } },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            labels: {
                formatter: val => {
                    if (Math.abs(val) >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                    if (Math.abs(val) >= 1000) return (val / 1000).toFixed(0) + 'k';
                    return formatNumber(val, 0);
                }
            }
        },
        legend: { show: true, position: 'top', fontSize: '11px' },
        tooltip: { y: { formatter: val => formatNumber(val, 0) + ' €' } },
        grid: { borderColor: '#f1f1f1', strokeDashArray: 3 }
    };

    const chart = new ApexCharts(el, options);
    chart.render();
    el._chart = chart;
}

// CA Annuel — 4 barres : Total N-1, Total N, YTD N-1, YTD N
function renderCaAnnuelChart(data, selector, unit = '€', height = 195, colors = ['#1a3767', '#90aad4']) {
    const el = destroyExistingChart(selector);
    if (!el) return;

    const year     = data.year || new Date().getFullYear();
    const caN      = data.ca_n      || 0;
    const caN1     = data.ca_n_1    || 0;
    const caYtdN   = data.ca_n      || 0;
    const caYtdN1  = data.ca_ytd_n1 || 0;

    const options = {
        chart: { type: 'bar', height: height, toolbar: { show: false } },
        plotOptions: { bar: { horizontal: false, columnWidth: '70%', borderRadius: 2 } },
        colors: colors,
        dataLabels: { enabled: false },
        series: [
            { name: year - 1,   data: [caN1,   caYtdN1] },
            { name: year,       data: [caN,    caYtdN]  }
        ],
        xaxis: {
            categories: ['Total année', 'YTD'],
            labels: { style: { fontSize: '11px' } },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            labels: {
                formatter: val => {
                    if (Math.abs(val) >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                    if (Math.abs(val) >= 1000) return (val / 1000).toFixed(0) + 'k';
                    return formatNumber(val, 0);
                }
            }
        },
        legend: { show: true, position: 'top', fontSize: '11px' },
        tooltip: { y: { formatter: val => formatNumber(val, 0) + ' ' + unit } },
        grid: { borderColor: '#f1f1f1', strokeDashArray: 3 }
    };

    const chart = new ApexCharts(el, options);
    chart.render();
    el._chart = chart;
}

// Chargement combiné CA mensuel + annuel
function renderCaVentesBlock(apiUrl) {
    const finalUrl = buildUrlWithNetwork(apiUrl);

    $.getJSON(finalUrl, function (data) {
        // Variation affichée dans le titre
        const variation = Number(data?.variation || 0);
        let varHtml = '';
        if (variation > 0) {
            varHtml = `<span class="text-success">${variation.toFixed(1)}% <i class="fa fa-arrow-up"></i></span> vs N-1 YTD`;
        } else if (variation < 0) {
            varHtml = `<span class="text-danger">${Math.abs(variation).toFixed(1)}% <i class="fa fa-arrow-down"></i></span> vs N-1 YTD`;
        } else {
            varHtml = `<span class="text-muted">0% stable</span>`;
        }
        $('#variation').html(varHtml);

        renderCaMensuelChart(data, '#chart-ca-mensuel');
        renderCaAnnuelChart(data, '#chart-ca-annuel');
    });
}

// Taux conversion (ancienne version bloc — conservée pour compatibilité)
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

function renderTopClientsChart(apiUrl, selector, barColor = '#2e62b9', unit = '€') {
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
                formatter: val => formatNumber(val, 0) + ' ' + unit,
                style: { fontSize: '12px', colors: ['#333'] }
            },
            colors: [barColor],
            xaxis: { categories: labels },
            series: [{ name: 'CA', data: values }],
            tooltip: {
                y: {
                    formatter: val => formatNumber(val, 2) + ' ' + unit
                }
            }
        };

        const chart = new ApexCharts(el, options);
        chart.render();
        el._chart = chart;
    });
}

function renderTopCompanySalesChart(apiUrl, chartSelector, tableSelector, donutColors = [
    '#775DD0', '#00E396', '#FEB019', '#d0223c', '#008FFB',
    '#3F51B5', '#546E7A', '#D4526E', '#8D5B4C', '#F86624'
], height = 240, unit = '€') {
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
            chart: { type: 'donut', height: height, toolbar: { show: false } },
            labels: result.labels,
            series: safeValues,
            legend: { show: false },
            dataLabels: { enabled: false },
            tooltip: {
                y: {
                    formatter: val => formatNumber(val, 2) + ' ' + unit
                }
            },
            colors: donutColors,
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

        const compact = height < 200;
        const total = safeValues.reduce((s, v) => s + v, 0);

        if (compact) {
            let html = '';
            result.labels.forEach((label, i) => {
                const val = safeValues[i];
                const pct = total > 0 ? ((val / total) * 100).toFixed(1) : '0.0';
                html += `<div class="d-flex align-items-center mb-1" style="gap:6px; font-size:11px;">
                    <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:${donutColors[i] ?? '#ccc'};flex-shrink:0;"></span>
                    <span class="text-truncate" style="flex:1;">${label}</span>
                    <span class="fw-bold" style="white-space:nowrap;">${formatNumber(val, 0)} ${unit}</span>
                </div>`;
            });
            tableEl.innerHTML = html;
        } else {
            let html = '<table class="table table-sm mb-0" style="font-size: 12px;"><tbody>';
            result.labels.forEach((label, i) => {
                const val = safeValues[i];
                html += `<tr>
                            <td class="text-start text-truncate" style="max-width: 140px;">${label}</td>
                            <td class="text-end fw-bold">${formatNumber(val, 2)} ${unit}</td>
                         </tr>`;
            });
            html += '</tbody></table>';
            tableEl.innerHTML = html;
        }
    });
}

function renderTopProductSalesChart(apiUrl, selector, barColor = '#00b894', unit = '€') {
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
             alt=""
             data-fallback-step="0"
             onerror="
                 const step = parseInt(this.dataset.fallbackStep, 10);
                 if (step === 0) {
                     this.dataset.fallbackStep = '1';
                     this.src = this.src.replace('_2.jpg', '_new_1.jpg');
                 } else if (step === 1) {
                     this.dataset.fallbackStep = '2';
                     this.src = this.src.replace('_new_1.jpg', '_1.jpg');
                 } else if (step === 2) {
                     this.dataset.fallbackStep = '3';
                     this.src = '/assets/images/no-image.png';
                 } else {
                     this.onerror = null;
                 }
             ">

        <div class="top-product-info">
            <div class="top-product-label">${p.label}</div>
            <div class="top-product-bar-container">
                <div class="top-product-bar" style="width:${percent}%; background: ${barColor};"></div>
            </div>
        </div>

        <div class="top-product-value">
            ${formatNumber(p.value, 0)} ${unit}
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

function renderSalesEvolutionChart(apiUrl, selector, legendSelector, colors = ['#e74c3c', '#9b59b6', '#26c6da', '#40a2ed', '#ff9800'], totalsSelector = '#chart-ca-5y-totals', unit = '€') {
    const finalUrl = buildUrlWithNetwork(apiUrl);

    $.getJSON(finalUrl, function (response) {
        const el = destroyExistingChart(selector);
        const totalsEl = destroyExistingChart(totalsSelector);

        if (!el) return;

        const series = Array.isArray(response?.series) ? response.series : [];
        const categories = Array.isArray(response?.categories) ? response.categories : [];

        if (series.length === 0) {
            el.innerHTML = '<p class="text-muted text-center mt-5">Aucune donnée disponible.</p>';
            return;
        }

        // Graphique principal — barres mensuelles par année
        const options = {
            chart: { type: 'bar', height: 310, toolbar: { show: false }, events: {
                legendClick: function(ctx, seriesIndex, config) {
                    const name = config.config.series[seriesIndex]?.name;
                    if (name && totalsEl && totalsEl._chart) {
                        totalsEl._chart.toggleSeries(name);
                    }
                }
            }},
            plotOptions: { bar: { horizontal: false, columnWidth: '60%', borderRadius: 1 } },
            colors: colors,
            dataLabels: { enabled: false },
            series: series,
            xaxis: {
                categories: categories,
                labels: { style: { fontSize: '11px' } },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    formatter: val => {
                        if (Math.abs(val) >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                        if (Math.abs(val) >= 1000) return (val / 1000).toFixed(0) + 'k';
                        return formatNumber(val, 0);
                    }
                }
            },
            legend: {
                show: true,
                position: 'bottom',
                fontSize: '12px',
                onItemClick: { toggleDataSeries: true },
                onItemHover: { highlightDataSeries: true }
            },
            tooltip: { y: { formatter: val => formatNumber(val, 0) + ' ' + unit } },
            grid: { borderColor: '#f1f1f1', strokeDashArray: 3 }
        };

        const chart = new ApexCharts(el, options);
        chart.render();
        el._chart = chart;

        // Graphique totaux par année
        if (totalsEl) {
            const currentMonth = new Date().getMonth(); // 0-based, ex: juillet = 6
            const totalLabels = series.map(s => s.name);
            // Une série par année, chaque série a 2 valeurs : [Total, YTD]
            const totalsSeriesData = series.map((s, i) => ({
                name: s.name,
                data: [
                    Math.round(s.data.reduce((a, b) => a + (b || 0), 0)),
                    Math.round(s.data.slice(0, currentMonth).reduce((a, b) => a + (b || 0), 0))
                ]
            }));

            const totalsOptions = {
                chart: { type: 'bar', height: 310, toolbar: { show: false } },
                plotOptions: { bar: { horizontal: false, columnWidth: '70%', borderRadius: 2 } },
                colors: colors,
                dataLabels: { enabled: false },
                series: totalsSeriesData,
                stroke: { show: true, width: 2, colors: ['transparent'] },
                xaxis: {
                    categories: ['Total année', 'YTD'],
                    labels: { style: { fontSize: '11px' } },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    labels: {
                        formatter: val => {
                            if (Math.abs(val) >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                            if (Math.abs(val) >= 1000) return (val / 1000).toFixed(0) + 'k';
                            return formatNumber(val, 0);
                        }
                    }
                },
                legend: { show: false },
                tooltip: { y: { formatter: val => formatNumber(val, 0) + ' ' + unit } },
                grid: { borderColor: '#f1f1f1', strokeDashArray: 3 }
            };

            const totalsChart = new ApexCharts(totalsEl, totalsOptions);
            totalsChart.render();
            totalsEl._chart = totalsChart;
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

function renderBacklogClientChart(apiUrl, chartSelector, tableSelector, donutColors = ['#E53935', '#FB8C00', '#FDD835', '#43A047', '#1E88E5', '#90A4AE'], height = 220) {
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
        const safeNbClients  = Array.isArray(result.nb_clients) ? result.nb_clients.map(v => Number(v) || 0) : result.labels.map(() => 0);
        const nbClientsTotal = Number(result.nb_clients_total || 0);

        const options = {
            chart: {
                type: 'donut',
                height: height,
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
            colors: donutColors,
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
                        <div class="fw-bold">${formatNumber(safeValues[i], 2)} €</div>
                        <div class="text-muted small">${formatNumber(safeQuantities[i], 0)} pcs</div>
                        <div class="text-muted small">${formatNumber(safeNbClients[i], 0)} clients</div>
                    </td>
                </tr>
            `;
        });

        const totalMontant = safeValues.reduce((s, v) => s + v, 0);
        const totalQty = safeQuantities.reduce((s, v) => s + v, 0);
        const totalClients = nbClientsTotal || safeNbClients.reduce((s, v) => s + v, 0);
        html += `
            <tr class="fw-bold border-top">
                <td class="text-start">Total</td>
                <td class="text-end">
                    <div class="fw-bold">${formatNumber(totalMontant, 2)} €</div>
                    <div class="text-muted small">${formatNumber(totalQty, 0)} pcs</div>
                    <div class="text-muted small">${formatNumber(totalClients, 0)} clients</div>
                </td>
            </tr>
        `;

        html += '</tbody></table>';
        tableEl.innerHTML = html;
    });
}

// Couleurs famille — même palette que le donut répartition famille
const TOP_PRODUIT_COLORS = { FTW: '#775DD0', APL: '#00E396' };

function switchTopProduit(family) {
    const color = TOP_PRODUIT_COLORS[family] || '#775DD0';
    renderTopProductSalesChart(
        window.dashboardRoutes.topProductSales + '?family=' + family,
        '#chart-top-product',
        color
    );
    ['ftw', 'apl'].forEach(f => {
        const btn = document.getElementById('btn-top-' + f);
        if (!btn) return;
        if (f === family.toLowerCase()) {
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-primary', 'active');
        } else {
            btn.classList.remove('btn-primary', 'active');
            btn.classList.add('btn-outline-primary');
        }
    });
}

function loadDashboardData() {
    const network    = getSelectedNetwork();
    const isBoutique = network === 'boutique';
    const isEcom     = network === 'ecom';

    const stdEl = document.getElementById('dashboard-standard');
    const bouEl = document.getElementById('dashboard-boutique');
    const ecoEl = document.getElementById('dashboard-ecom');
    if (stdEl) stdEl.style.display = (!isBoutique && !isEcom) ? '' : 'none';
    if (bouEl) bouEl.style.display = isBoutique ? '' : 'none';
    if (ecoEl) ecoEl.style.display = isEcom     ? '' : 'none';

    if (isBoutique) {
        loadBoutiqueData();
        return;
    }

    if (isEcom) {
        loadEcomData();
        return;
    }

    renderCaVentesBlock(window.dashboardRoutes.caParMois);

    renderSalesMonthChart(
        window.dashboardRoutes.salesCurrentMonth,
        '#ca_nm',
        '#variation_m',
        '#warning-line-chart'
    );

    renderTopClientsChart(window.dashboardRoutes.topClients, '#chart-top-clients');
    renderTopCompanySalesChart(window.dashboardRoutes.topFamilySales, '#chart-top-family-sales', '#table-top-family-sales');
    renderTopProductSalesChart(window.dashboardRoutes.topProductSales + '?family=FTW', '#chart-top-product', TOP_PRODUIT_COLORS.FTW);
    renderSalesEvolutionChart(window.dashboardRoutes.salesEvolution5y, '#userflow', null);
    chargerCaJour(window.dashboardRoutes.caToday);
    renderBacklogClientChart(window.dashboardRoutes.backlogClient + '?mode=mois', '#chart-backlog-client', '#table-backlog-client');
}

function switchBacklogMode(mode) {
    renderBacklogClientChart(
        window.dashboardRoutes.backlogClient + '?mode=' + mode,
        '#chart-backlog-client',
        '#table-backlog-client'
    );
    ['mois', 'collection'].forEach(m => {
        const btn = document.getElementById('btn-backlog-' + m);
        if (!btn) return;
        if (m === mode) {
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-primary', 'active');
        } else {
            btn.classList.remove('btn-primary', 'active');
            btn.classList.add('btn-outline-primary');
        }
    });
}

// ── BOUTIQUE GROUPS ──

function onBoutiqueFilterChange(group) {
    loadBoutiqueGroupData(group);
}

function loadBoutiqueGroupData(group) {
    const selectEl    = document.getElementById('boutique-' + group + '-filter');
    const customer    = selectEl ? selectEl.value : '';
    const customerQs  = customer ? '&customer=' + encodeURIComponent(customer) : '';
    const ventesUrl   = window.dashboardRoutes.boutiqueGroupVentes + '?group=' + group + customerQs;
    const produitsBase = window.dashboardRoutes.boutiqueGroupTopProduits + '?group=' + group + customerQs;

    const boutiqueColors = {
        propres:  ['#1a3767', '#90aad4'],
        affilies: ['#1a7a4a', '#7acc9e'],
        outlet:   ['#c45c1a', '#f0aa7a'],
    };
    const groupColors = boutiqueColors[group] || ['#1a3767', '#90aad4'];

    $.getJSON(ventesUrl, function(data) {
        const variation = Number(data?.variation || 0);
        let varHtml = '';
        if (data.variation !== null && data.variation !== undefined) {
            if (variation > 0) {
                varHtml = `<span class="text-success">${variation.toFixed(1)}% <i class="fa fa-arrow-up"></i></span> vs N-1 YTD`;
            } else if (variation < 0) {
                varHtml = `<span class="text-danger">${Math.abs(variation).toFixed(1)}% <i class="fa fa-arrow-down"></i></span> vs N-1 YTD`;
            } else {
                varHtml = `<span class="text-muted">0% stable</span>`;
            }
        }
        const varEl = document.getElementById('boutique-' + group + '-variation');
        if (varEl) varEl.innerHTML = varHtml;

        renderCaMensuelChart(data, '#boutique-' + group + '-mensuel', 300, groupColors);
        renderCaAnnuelChart(data, '#boutique-' + group + '-annuel', '€', 300, groupColors);
    });

    renderTopProductSalesChart(
        produitsBase + '&family=FTW',
        '#boutique-' + group + '-product',
        TOP_PRODUIT_COLORS.FTW
    );
}

function switchBoutiqueTopProduit(group, family) {
    const color      = TOP_PRODUIT_COLORS[family] || '#775DD0';
    const selectEl   = document.getElementById('boutique-' + group + '-filter');
    const customer   = selectEl ? selectEl.value : '';
    const customerQs = customer ? '&customer=' + encodeURIComponent(customer) : '';
    renderTopProductSalesChart(
        window.dashboardRoutes.boutiqueGroupTopProduits + '?group=' + group + '&family=' + family + customerQs,
        '#boutique-' + group + '-product',
        color
    );
    ['FTW', 'APL'].forEach(f => {
        const btn = document.getElementById('boutique-' + group + '-btn-' + f.toLowerCase());
        if (!btn) return;
        if (f === family) {
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-primary', 'active');
        } else {
            btn.classList.remove('btn-primary', 'active');
            btn.classList.add('btn-outline-primary');
        }
    });
}

function loadBoutiqueData() {
    ['propres', 'affilies', 'outlet'].forEach(group => loadBoutiqueGroupData(group));
}

// ── E-COM GROUPS ──

const ECOM_GROUP_COLORS = {
    lcs_shop:      ['#008FFB', '#69D2E7'],
    amazon_vendor: ['#FF4560', '#F9A8B8'],
    amazon_seller: ['#00E396', '#A8E6CF'],
    autres_mkp:    ['#775DD0', '#C4B5FD'],
    ecom_btb:      ['#0891B2', '#67E8F9'],
};

function renderEcomGlobalAnnuelChart(totals, groups, glabels, year, selector) {
    const el = destroyExistingChart(selector);
    if (!el) return;
    // 8 séries × 2 catégories (Total + YTD) = 16 barres, mêmes couleurs que le mensuel
    const series = [];
    const colors = [];
    groups.forEach((g, i) => {
        const c = ECOM_GROUP_COLORS[g] || ['#888', '#ccc'];
        series.push({ name: glabels[i] + ' ' + (year - 1), data: [Math.round(totals[g].n1), Math.round(totals[g].ytdN1)] });
        colors.push(c[1]);
        series.push({ name: glabels[i] + ' ' + year,       data: [Math.round(totals[g].n),  Math.round(totals[g].ytdN)]  });
        colors.push(c[0]);
    });
    const chart = new ApexCharts(el, {
        chart:       { type: 'bar', height: 195, toolbar: { show: false } },
        series:      series,
        colors:      colors,
        plotOptions: { bar: { columnWidth: '80%', borderRadius: 2 } },
        dataLabels:  { enabled: false },
        legend:      { show: false },
        xaxis:       {
            categories: ['Total année', 'YTD'],
            labels: { style: { fontSize: '11px' } },
            axisBorder: { show: false }, axisTicks: { show: false }
        },
        yaxis: { labels: { formatter: v => {
            if (Math.abs(v) >= 1000000) return (v/1000000).toFixed(1)+'M';
            if (Math.abs(v) >= 1000)    return (v/1000).toFixed(0)+'k';
            return formatNumber(v, 0);
        }, style: { fontSize: '10px' } } },
        tooltip: { shared: false, y: { formatter: v => formatNumber(v, 2) + ' €' } },
    });
    chart.render();
    el._chart = chart;
}

function renderEcomGlobalChart(data, selector) {
    const el = destroyExistingChart(selector);
    if (!el || !data || !data.series) return;

    const year   = data.year;
    const labels = data.labels || [];
    const colors = [];
    const series = data.series.map(s => {
        const g     = s.group;
        const isN   = s.name.includes(String(year));
        const color = ECOM_GROUP_COLORS[g] ? (isN ? ECOM_GROUP_COLORS[g][0] : ECOM_GROUP_COLORS[g][1]) : '#ccc';
        colors.push(color);
        return { name: s.name, data: s.data };
    });

    const chart = new ApexCharts(el, {
        chart:    { type: 'bar', height: 300, toolbar: { show: false }, stacked: false },
        series:   series,
        colors:   colors,
        xaxis:    { categories: labels, labels: { style: { fontSize: '11px' } } },
        yaxis:    { labels: { formatter: v => formatNumber(v / 1000, 0) + 'k' } },
        dataLabels: { enabled: false },
        legend:   { position: 'bottom', fontSize: '11px' },
        tooltip:  { y: { formatter: v => formatNumber(v, 2) + ' €' } },
        plotOptions: { bar: { columnWidth: '80%' } },
    });
    chart.render();
    el._chart = chart;
}

function renderVariationBadge(selector, variation) {
    const el = document.getElementById(selector);
    if (!el) return;
    if (variation === null || variation === undefined) { el.innerHTML = ''; return; }
    const v = Number(variation);
    if (v > 0) {
        el.innerHTML = `<span class="text-success">${v.toFixed(1)}% <i class="fa fa-arrow-up"></i></span> vs N-1 YTD`;
    } else if (v < 0) {
        el.innerHTML = `<span class="text-danger">${Math.abs(v).toFixed(1)}% <i class="fa fa-arrow-down"></i></span> vs N-1 YTD`;
    } else {
        el.innerHTML = `<span class="text-muted">0% stable</span>`;
    }
}

function loadEcomGroupData(group) {
    const ventesUrl    = window.dashboardRoutes.ecomGroupVentes + '?group=' + group;
    const produitsBase = window.dashboardRoutes.ecomGroupTopProduits + '?group=' + group;
    const c            = ECOM_GROUP_COLORS[group] || ['#1a3767', '#90aad4'];
    // N-1 = couleur claire [1], N = couleur foncée [0]
    const groupColors  = [c[1], c[0]];

    $.getJSON(ventesUrl, function(data) {
        renderVariationBadge('ecom-' + group + '-variation', data?.variation);
        renderCaMensuelChart(data, '#ecom-' + group + '-mensuel', 300, groupColors);
        renderCaAnnuelChart(data, '#ecom-' + group + '-annuel', '€', 300, groupColors);
    });

    renderTopProductSalesChart(
        produitsBase + '&family=FTW',
        '#ecom-' + group + '-product',
        TOP_PRODUIT_COLORS.FTW
    );
}

function switchEcomFamilleMode(group, mode) {
    const productEl    = document.getElementById('ecom-' + group + '-product');
    const familleEl    = document.getElementById('ecom-' + group + '-famille');
    const familleTabEl = document.getElementById('ecom-' + group + '-famille-table');
    const btnGroupEl   = document.getElementById('ecom-' + group + '-btn-group-famille');
    const btnFamille   = document.getElementById('ecom-' + group + '-btn-famille');

    const isFamilleMode = familleEl && familleEl.style.display !== 'none';
    const goToTop = mode === 'top' || isFamilleMode;

    const titleTopEl = document.getElementById('ecom-' + group + '-title-top');

    if (goToTop) {
        if (productEl)    productEl.style.display   = '';
        if (familleEl)    familleEl.style.display    = 'none';
        if (familleTabEl) familleTabEl.style.display = 'none';
        if (btnGroupEl)   btnGroupEl.style.display   = '';
        if (titleTopEl) {
            titleTopEl.classList.remove('btn-outline-secondary');
            titleTopEl.classList.add('btn-primary', 'active');
        }
        if (btnFamille) {
            btnFamille.classList.remove('btn-secondary', 'active');
            btnFamille.classList.add('btn-outline-secondary');
        }
    } else {
        if (productEl)    productEl.style.display   = 'none';
        if (familleEl) {
            if (!familleEl._loaded) {
                familleEl.innerHTML = '<div class="text-center text-muted py-3"><i class="fa fa-spinner fa-spin"></i></div>';
            }
            familleEl.style.display = '';
        }
        if (familleTabEl) familleTabEl.style.display = '';
        if (btnGroupEl)   btnGroupEl.style.display   = 'none';
        if (titleTopEl) {
            titleTopEl.classList.remove('btn-primary', 'active');
            titleTopEl.classList.add('btn-outline-secondary');
        }
        if (btnFamille) {
            btnFamille.classList.remove('btn-outline-secondary');
            btnFamille.classList.add('btn-secondary', 'active');
        }
        if (familleEl && !familleEl._loaded) {
            familleEl._loaded = true;
            renderTopCompanySalesChart(
                window.dashboardRoutes.ecomGroupFamilySales + '?group=' + group,
                '#ecom-' + group + '-famille',
                '#ecom-' + group + '-famille-table'
            );
        }
    }
}

function switchEcomTopProduit(group, family) {
    const color = TOP_PRODUIT_COLORS[family] || '#775DD0';
    renderTopProductSalesChart(
        window.dashboardRoutes.ecomGroupTopProduits + '?group=' + group + '&family=' + family,
        '#ecom-' + group + '-product',
        color
    );
    ['FTW', 'APL'].forEach(f => {
        const btn = document.getElementById('ecom-' + group + '-btn-' + f.toLowerCase());
        if (!btn) return;
        if (f === family) {
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-primary', 'active');
        } else {
            btn.classList.remove('btn-primary', 'active');
            btn.classList.add('btn-outline-primary');
        }
    });
}

function loadEcomData() {
    // Global : chart 8 séries
    $.getJSON(window.dashboardRoutes.ecomGlobalVentes, function(data) {
        renderEcomGlobalChart(data, '#ecom-global-mensuel');

        // CA annuel global : 4 groupes × N et N-1 — Total + YTD (16 barres)
        const year     = data.year || new Date().getFullYear();
        const curMonth = new Date().getMonth(); // 0-based : mois en cours non complet
        const groups   = ['lcs_shop', 'amazon_vendor', 'amazon_seller', 'autres_mkp', 'ecom_btb'];
        const glabels  = ['LCS Shop', 'Amazon Vendor', 'Amazon Seller', 'Autres Marketplaces', 'E-commerce BTB'];
        const totals   = {};
        groups.forEach(g => { totals[g] = { n: 0, n1: 0, ytdN: 0, ytdN1: 0 }; });
        (data.series || []).forEach(s => {
            const g   = s.group;
            const isN = s.name.includes(String(year));
            if (!totals[g]) return;
            const sum = (s.data || []).reduce((a, v) => a + (v || 0), 0);
            const ytd = (s.data || []).slice(0, curMonth).reduce((a, v) => a + (v || 0), 0);
            if (isN) { totals[g].n   += sum; totals[g].ytdN  += ytd; }
            else     { totals[g].n1  += sum; totals[g].ytdN1 += ytd; }
        });
        renderEcomGlobalAnnuelChart(totals, groups, glabels, year, '#ecom-global-annuel');
    });
    renderTopProductSalesChart(
        window.dashboardRoutes.ecomGroupTopProduits + '?group=global&family=FTW',
        '#ecom-global-product',
        TOP_PRODUIT_COLORS.FTW
    );

    // Sous-groupes
    ['lcs_shop', 'amazon_vendor', 'amazon_seller', 'autres_mkp', 'ecom_btb'].forEach(g => loadEcomGroupData(g));

    // Blocs synthèse globale E-com
    renderTopClientsChart(window.dashboardRoutes.topClients + '?network=ecom', '#ecom-chart-top-clients');
    renderTopCompanySalesChart(window.dashboardRoutes.topFamilySales + '?network=ecom', '#ecom-chart-top-family-sales', '#ecom-table-top-family-sales');
    renderSalesEvolutionChart(window.dashboardRoutes.salesEvolution5y + '?network=ecom', '#ecom-userflow', null, undefined, '#ecom-chart-ca-5y-totals');
}
