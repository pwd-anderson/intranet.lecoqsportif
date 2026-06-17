// Fonctions partagées disponibles via dashboard.js (chargé avant) :
// formatNumber, destroyExistingChart, chargerConversionChart,
// renderTopClientsChart, renderTopCompanySalesChart,
// renderTopProductSalesChart, renderBacklogClientChart, renderSalesEvolutionChart

function renderSellOutWeeklyChart(apiUrl, caSelector, variationSelector, chartSelector) {
    $.getJSON(apiUrl, function (data) {
        const caN       = Number(data?.ca_n  || 0);
        const variation = Number(data?.variation || 0);

        $(caSelector).html(formatNumber(caN, 0));

        let varHtml = '';
        if (variation > 0) {
            varHtml = `<span class="text-success">${variation.toFixed(1)}% <i class="fa fa-arrow-up"></i></span> vs N-1`;
        } else if (variation < 0) {
            varHtml = `<span class="text-danger">${Math.abs(variation).toFixed(1)}% <i class="fa fa-arrow-down"></i></span> vs N-1`;
        } else {
            varHtml = `<span class="text-muted">0% <i class="fa fa-minus"></i></span> stable`;
        }
        $(variationSelector).html(varHtml);

        const el = destroyExistingChart(chartSelector);
        if (!el) return;

        const labels = Array.isArray(data?.labels) ? data.labels : [];
        const series = Array.isArray(data?.series) ? data.series : [];

        if (series.length === 0) {
            el.innerHTML = '<p class="text-muted text-center mt-5">Aucune donnée disponible.</p>';
            return;
        }

        const options = {
            chart: { height: 130, type: 'line', toolbar: { show: false } },
            colors: ['#0f9b8e', '#ffb6a3'],
            dataLabels: { enabled: false },
            stroke: { width: 2, curve: 'smooth' },
            series: series,
            xaxis: {
                categories: labels,
                labels: { show: false },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: { show: false },
            legend: { show: false },
            grid: { show: false },
            tooltip: {
                x: { formatter: (_, opts) => labels[opts.dataPointIndex] || '' },
                y: { formatter: val => formatNumber(val, 0) + ' €' }
            }
        };

        const chart = new ApexCharts(el, options);
        chart.render();
        el._chart = chart;
    });
}

function renderSellOutSemaineChart(apiUrl, caSelector, variationSelector, semaineSelector, chartSelector) {
    $.getJSON(apiUrl, function (data) {
        const caN       = Number(data?.ca_n  || 0);
        const variation = Number(data?.variation || 0);
        const semaine   = data?.semaine || '—';

        $(caSelector).html(formatNumber(caN, 0));
        $(semaineSelector).html('S' + String(semaine).padStart(2, '0'));

        let varHtml = '';
        if (variation > 0) {
            varHtml = `<span class="text-success">${variation.toFixed(1)}% <i class="fa fa-arrow-up"></i></span> vs S-1 N-1`;
        } else if (variation < 0) {
            varHtml = `<span class="text-danger">${Math.abs(variation).toFixed(1)}% <i class="fa fa-arrow-down"></i></span> vs S-1 N-1`;
        } else {
            varHtml = `<span class="text-muted">0% <i class="fa fa-minus"></i></span>`;
        }
        $(variationSelector).html(varHtml);

        const el = destroyExistingChart(chartSelector);
        if (!el) return;

        const labels = Array.isArray(data?.labels) ? data.labels : [];
        const series = Array.isArray(data?.series) ? data.series : [];

        if (series.length === 0) {
            el.innerHTML = '<p class="text-muted text-center mt-5">Aucune donnée disponible.</p>';
            return;
        }

        const options = {
            chart: { height: 130, type: 'bar', toolbar: { show: false } },
            plotOptions: { bar: { horizontal: false, columnWidth: '65%', borderRadius: 3, distributed: true } },
            colors: ['#ffb6a3', '#0f9b8e'],
            dataLabels: { enabled: false },
            series: series,
            xaxis: {
                categories: labels,
                labels: {
                    show: true,
                    style: { fontSize: '11px', colors: ['#aaa', '#aaa'] }
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: { show: false },
            legend: { show: false },
            grid: { show: false },
            tooltip: {
                x: { formatter: (_, opts) => labels[opts.dataPointIndex] || '' },
                y: { formatter: val => formatNumber(val, 0) + ' €' }
            }
        };

        const chart = new ApexCharts(el, options);
        chart.render();
        el._chart = chart;
    });
}

function loadSellOutDashboardData() {
    const r = window.sellOutRoutes;

    chargerConversionChart(
        r.exchangeRate,
        { text: '#taux-eur-usd-text', value: '#taux-eur-usd-val', evolution: '#evolution-rate-eur-usd-text' },
        '#bar-negative-chart-eur-usd',
        ['#0f9b8e', '#f4b942']
    );

    renderSellOutWeeklyChart(r.ventesAnnuelles, '#so-ca-ytd', '#so-variation-ytd', '#so-chart-weekly');

    renderSellOutSemaineChart(
        r.ventesSemaine,
        '#so-ca-semaine', '#so-variation-semaine', '#so-semaine-num',
        '#so-chart-semaine'
    );

    renderTopClientsChart(r.topClients, '#so-chart-top-clients', '#0f9b8e');
    renderTopCompanySalesChart(
        r.familleCa, '#so-chart-famille', '#so-table-famille',
        ['#0f9b8e', '#ff6f59', '#f4b942', '#6c5b7b', '#f67280', '#2a9d8f', '#e76f51', '#e9c46a']
    );
    renderTopProductSalesChart(r.topProduits, '#so-chart-top-produits');

    renderBacklogClientChart(
        r.backlog, '#so-chart-backlog', '#so-table-backlog',
        ['#0f9b8e', '#f4b942', '#ff6f59']
    );

    // Barres verticales identiques au dashboard home — renderSalesEvolutionChart de dashboard.js
    renderSalesEvolutionChart(
        r.evolutionSemaines, '#so-chart-evolution', '#so-legend-evolution',
        ['#ffb6a3', '#0f9b8e']
    );
}
