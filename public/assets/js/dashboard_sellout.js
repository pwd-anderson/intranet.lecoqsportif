// Fonctions partagées disponibles via dashboard.js (chargé avant) :
// formatNumber, destroyExistingChart, renderCaAnnuelChart,
// renderTopClientsChart, renderTopCompanySalesChart,
// renderTopProductSalesChart, renderBacklogClientChart, renderSalesEvolutionChart

function renderSellOutVentesBlock(apiUrl, weeklySelector, annuelSelector, variationSelector) {
    $.getJSON(apiUrl, function (data) {
        const variation = Number(data?.variation || 0);
        const series    = Array.isArray(data?.series) ? data.series : [];
        const labels    = Array.isArray(data?.labels)  ? data.labels  : [];

        let varHtml = '';
        if (variation > 0) {
            varHtml = `<span class="text-success">${variation.toFixed(1)}% <i class="fa fa-arrow-up"></i></span> vs N-1`;
        } else if (variation < 0) {
            varHtml = `<span class="text-danger">${Math.abs(variation).toFixed(1)}% <i class="fa fa-arrow-down"></i></span> vs N-1`;
        } else {
            varHtml = `<span class="text-muted">0% <i class="fa fa-minus"></i></span> stable`;
        }
        $(variationSelector).html(varHtml);

        // Barres hebdomadaires groupées — N-1 avant N
        const el = destroyExistingChart(weeklySelector);
        if (el && series.length > 0) {
            const reversed = [...series].reverse();
            const chart = new ApexCharts(el, {
                chart: { type: 'bar', height: 195, toolbar: { show: false } },
                plotOptions: { bar: { horizontal: false, columnWidth: '80%', borderRadius: 1 } },
                colors: ['#1a3767', '#90aad4'],
                dataLabels: { enabled: false },
                series: reversed,
                xaxis: {
                    categories: labels,
                    labels: {
                        show: true,
                        rotate: -45,
                        style: { fontSize: '10px', colors: Array(labels.length).fill('#aaa') }
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: { show: false },
                legend: { show: false },
                grid: { show: false },
                tooltip: { y: { formatter: val => formatNumber(val, 0) + ' pcs' } }
            });
            chart.render();
            el._chart = chart;
        }

        // Companion annuel
        if (series.length >= 2) {
            const dataN  = series[0]?.data || [];
            const dataN1 = series[1]?.data || [];
            const year   = new Date().getFullYear();
            renderCaAnnuelChart({
                year:      year,
                ca_n:      Math.round(dataN.reduce((a, b)  => a + (b || 0), 0)),  // YTD N = Total N
                ca_ytd_n1: Math.round(dataN1.reduce((a, b) => a + (b || 0), 0)),  // YTD N-1 (mêmes semaines)
                ca_n_1:    Math.round((data.ca_n1_full || 0))                      // Total complet N-1
            }, annuelSelector, 'pcs');
        }
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

        const chart = new ApexCharts(el, {
            chart: { height: 130, type: 'bar', toolbar: { show: false } },
            plotOptions: { bar: { horizontal: false, columnWidth: '65%', borderRadius: 3, distributed: true } },
            colors: ['#1a3767', '#90aad4'],
            dataLabels: { enabled: false },
            series: series,
            xaxis: {
                categories: labels,
                labels: { show: true, style: { fontSize: '11px', colors: ['#aaa', '#aaa'] } },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: { show: false },
            legend: { show: false },
            grid: { show: false },
            tooltip: {
                x: { formatter: (_, opts) => labels[opts.dataPointIndex] || '' },
                y: { formatter: val => formatNumber(val, 0) + ' pcs' }
            }
        });
        chart.render();
        el._chart = chart;
    });
}

const SO_TOP_PRODUIT_COLORS = { FTW: '#0f9b8e', APL: '#ff6f59' };

function soSwitchTopProduit(family) {
    const color = SO_TOP_PRODUIT_COLORS[family] || '#775DD0';
    renderTopProductSalesChart(
        window.sellOutRoutes.topProduits + '?family=' + family,
        '#so-chart-top-product',
        color,
        'pcs'
    );
    ['ftw', 'apl'].forEach(f => {
        const btn = document.getElementById('so-btn-top-' + f);
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

function loadSellOutDashboardData() {
    const r = window.sellOutRoutes;

    renderSellOutVentesBlock(r.ventesAnnuelles, '#so-chart-weekly', '#so-chart-annuel', '#so-variation-ytd');

    renderSellOutSemaineChart(
        r.ventesSemaine,
        '#so-ca-semaine', '#so-variation-semaine', '#so-semaine-num',
        '#so-chart-semaine'
    );

    renderTopClientsChart(r.topClients, '#so-chart-top-clients', '#0f9b8e', 'pcs');

    renderTopCompanySalesChart(
        r.familleCa, '#so-chart-famille', '#so-table-famille',
        ['#0f9b8e', '#ff6f59', '#f4b942', '#6c5b7b', '#f67280', '#2a9d8f', '#e76f51', '#e9c46a'],
        240, 'pcs'
    );

    renderTopProductSalesChart(r.topProduits + '?family=FTW', '#so-chart-top-product', SO_TOP_PRODUIT_COLORS.FTW, 'pcs');

    renderBacklogClientChart(
        r.backlog, '#so-chart-backlog', '#so-table-backlog',
        ['#2196F3', '#FF9800', '#e53935']
    );

    renderSalesEvolutionChart(
        r.evolutionSemaines, '#so-chart-evolution', null,
        ['#1a3767', '#90aad4'],
        '#so-chart-evolution-totals',
        'pcs'
    );
}
