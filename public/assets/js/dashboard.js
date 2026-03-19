
const variationTextMonth = $('#trans_variation_month').data('text');

// Taux conversion
function chargerConversionChart(path, targetSelectors, chartId, barColors) {

    $.getJSON(path, function (seriesData) {
        // Texte
        $(targetSelectors.text).text(seriesData.taux_courant.toFixed(3));
        $(targetSelectors.value).text(seriesData.taux_courant.toFixed(3));

        // Évolution
        let evolution = seriesData.evolution_pourcent;
        let trendHtml = '';
        if (evolution > 0) {
            trendHtml = `<span class="text-success">${evolution}% <i class="fa fa-arrow-up"></i></span> ${variationTextMonth}`;
        } else if (evolution < 0) {
            trendHtml = `<span class="text-danger">${Math.abs(evolution)}% <i class="fa fa-arrow-down"></i></span> ${variationTextMonth}`;
        } else {
            trendHtml = `<span class="text-muted">0% <i class="fa fa-minus"></i></span> stable`;
        }
        $(targetSelectors.evolution).html(trendHtml);

        // Graphe
        const chartOptions = {
            chart: { height: 114, stacked: true, type: 'bar', toolbar: { show: false }, sparkline: { enabled: true } },
            plotOptions: { bar: { columnWidth: '20%', endingShape: 'rounded' }, distributed: true },
            colors: barColors,
            series: [
                { name: 'Taux en hausse', data: seriesData.positive },
                { name: 'Taux en baisse', data: seriesData.negative }
            ],
            xaxis: {
                categories: seriesData.labels,
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
                        const label = seriesData.labels[opts.dataPointIndex];
                        const [annee, mois] = label.split('-');
                        return `${moisMap[mois]} 20${annee}`;
                    }
                },
                y: {
                    formatter: val => (val / 1000).toFixed(4)
                }
            }
        };

        new ApexCharts(document.querySelector(chartId), chartOptions).render();
    });
}

// === Fonctions de chargement de graphiques ===

function renderSalesYearChart(apiUrl, caSelector, variationSelector, chartSelector) {
    $.getJSON(apiUrl, function (salesData) {
        // Affiche la valeur annuelle
        $(caSelector).html(
            Math.round(salesData.ca_n).toLocaleString('fr-CH', { maximumFractionDigits: 0 })
        );

        // Prépare le texte de variation
        const variation = salesData.variation;
        let variationHtml = '';

        if (variation > 0) {
            variationHtml = `<span class="text-success">${Number(variation).toFixed(1).toLocaleString('fr-CH')}% <i class="fa fa-arrow-up"></i></span> variation annuelle`;
        } else if (variation < 0) {
            variationHtml = `<span class="text-danger">${Math.abs(variation).toFixed(1).toLocaleString('fr-CH')}% <i class="fa fa-arrow-down"></i></span> variation annuelle`;
        } else {
            variationHtml = `<span class="text-muted">0% <i class="fa fa-minus"></i></span> stable`;
        }

        $(variationSelector).html(variationHtml);

        // Configure et affiche le graphique
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
            series: salesData.series,
            xaxis: {
                categories: salesData.labels,
                labels: { show: true },
                axisBorder: { show: false }
            },
            yaxis: { show: false },
            tooltip: {
                y: {
                    formatter: val => val.toLocaleString('fr-CH', { style: 'currency', currency: 'EUR' })
                }
            }
        };

        new ApexCharts(document.querySelector(chartSelector), options).render();
    });
}

function renderSalesMonthChart(apiUrl, caSelector, variationSelector, chartSelector) {
    $.getJSON(apiUrl, function (data) {
        $(caSelector).html(data.ca_n.toLocaleString('fr-CH', { maximumFractionDigits: 0 }));

        const v = data.variation;
        let html = '';
        if (v > 0) {
            html = `<span class="text-success">${Number(Math.abs(v).toFixed(1)).toLocaleString('fr-CH')}% <i class="fa fa-arrow-up"></i></span> variation mensuelle`;
        } else if (v < 0) {
            html = `<span class="text-danger">${Number(Math.abs(v).toFixed(1)).toLocaleString('fr-CH')}% <i class="fa fa-arrow-down"></i></span> variation mensuelle`;
        } else {
            html = `<span class="text-muted">0% <i class="fa fa-minus"></i></span>`;
        }
        $(variationSelector).html(html);

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
            series: data.series,
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    type: "horizontal",
                    gradientToColors: ['#e84a50', '#999999'],
                    opacityFrom: 0,
                    opacityTo: 0.9,
                    stops: [0, 30, 70, 100]
                }
            },
            xaxis: {
                categories: data.labels,
                show: false,
                labels: { show: false },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: { show: false },
            tooltip: {
                x: {
                    formatter: function (val, opts) {
                        return data.labels[opts.dataPointIndex];
                    }
                },
                y: {
                    formatter: val => val.toLocaleString('fr-CH')
                }
            }
        };

        new ApexCharts(document.querySelector(chartSelector), options).render();
    });
}

function renderTopClientsChart(apiUrl, selector) {
    $.getJSON(apiUrl, function (data) {
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
                formatter: val => val.toLocaleString('fr-CH', { maximumFractionDigits: 0 }) + ' €',
                style: { fontSize: '12px', colors: ['#333'] }
            },
            colors: ['#2e62b9'],
            xaxis: { categories: data.labels },
            series: [{ name: 'CA en EUR', data: data.data }],
            tooltip: {
                y: {
                    formatter: val => val.toLocaleString('fr-CH', { minimumFractionDigits: 2 }) + ' €'
                }
            }
        };
        new ApexCharts(document.querySelector(selector), options).render();
    });
}

function renderTopCompanySalesChart(apiUrl, chartSelector, tableSelector) {
    $.getJSON(apiUrl, function (result) {
        const el = document.querySelector(chartSelector);
        if (!el) return;

        el.innerHTML = '';

        if (!result || !Array.isArray(result.labels) || result.labels.length === 0) {
            el.innerHTML = '<p class="text-muted text-center mt-5">Aucune donnée disponible.</p>';
            return;
        }

        // Sécuriser les valeurs avant usage
        const safeValues = result.values.map(v => Number(v) || 0);

        const options = {
            chart: { type: 'donut', height: 240, toolbar: { show: false } },
            labels: result.labels,
            series: safeValues,
            legend: { show: false },
            dataLabels: {
                formatter: (val) => val.toFixed(1) + ' %'
            },
            tooltip: {
                y: {
                    formatter: (val) => val.toFixed(2).toLocaleString('fr-CH') + ' €'
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

        if (el._chart) el._chart.destroy();

        const chart = new ApexCharts(el, options);
        chart.render();
        el._chart = chart;

        // Tableau HTML avec valeurs sûres
        let html = '<table class="table table-sm mb-0" style="font-size: 12px;"><tbody>';
        result.labels.forEach((label, i) => {
            const val = safeValues[i];
            html += `<tr><td class="text-start text-truncate" style="max-width: 140px;">${label}</td>
                     <td class="text-end fw-bold">${val.toLocaleString('fr-CH', { minimumFractionDigits: 2 })} €</td></tr>`;
        });
        html += '</tbody></table>';
        document.querySelector(tableSelector).innerHTML = html;
    });
}

function renderTopProductSalesChart(apiUrl, selector) {

    $.getJSON(apiUrl, function (result) {

        const el = document.querySelector(selector);
        if (!el) return;

        el.innerHTML = '';

        if (!Array.isArray(result) || result.length === 0) {
            el.innerHTML = '<p class="text-muted text-center mt-5">Aucune donnée disponible.</p>';
            return;
        }

        const maxValue = Math.max(...result.map(p => Number(p.value) || 0));

        result.forEach(p => {

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
                        ${p.value.toLocaleString('fr-CH')} €
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
        // Correction pour le texte dupliqué (ex: "Produit AProduit A") parfois généré par ApexCharts
        let item = data.find(p => p.label === text);

        if (!item) {
            // Tentative de correspondance si le texte est dupliqué
            item = data.find(p => text === p.label + p.label);
        }

        // Fallback générique si le texte contient le label (attention aux sous-chaînes)
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
                // Fallback si getBBox échoue (ex: élément non rendu)
            }

            const img = document.createElementNS("http://www.w3.org/2000/svg", "image");

            const x = parseFloat(label.getAttribute('x'));
            const y = parseFloat(label.getAttribute('y'));
            const imgSize = 30;
            const gap = 8;

            // Calcul de la position X (suppose alignement à droite par défaut pour l'axe Y)
            // On place l'image à gauche du texte
            const imgX = x - bbox.width - imgSize - gap;

            // Centrage vertical par rapport à la ligne de base du texte
            const imgY = y - (imgSize / 2) - 5; // -5 ajustement visuel empirique

            img.setAttributeNS(null, "href", item.image);
            img.setAttributeNS(null, "x", imgX.toString());
            img.setAttributeNS(null, "y", imgY.toString());
            img.setAttributeNS(null, "width", imgSize.toString());
            img.setAttributeNS(null, "height", imgSize.toString());
            img.setAttribute("data-label", item.label);
            img.style.pointerEvents = "none";

            label.parentNode.insertBefore(img, label);
        }
    });
}


function renderSalesEvolutionChart(apiUrl, selector, legendSelector) {
    $.getJSON(apiUrl, function (response) {
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
            series: response.series,
            xaxis: {
                categories: response.categories,
                labels: { style: { fontSize: '12px' } }
            },
            yaxis: {
                labels: { formatter: val => val.toLocaleString('fr-CH') }
            },
            legend: { show: false },
            tooltip: {
                y: { formatter: val => val.toLocaleString('fr-CH') + ' €' }
            },
            grid: {
                borderColor: '#f1f1f1',
                xaxis: { lines: { show: true } },
                yaxis: { lines: { show: true } },
                strokeDashArray: 0
            }
        };

        const chart = new ApexCharts(document.querySelector(selector), options);
        chart.render();

        const legendHtml = response.series.map((serie, index) => {
            return `<span class="badge me-1" style="background-color: ${colors[index]}; font-size: 12px;">${serie.name}</span>`;
        }).join(' ');

        document.querySelector(legendSelector).innerHTML = legendHtml;
    });
}

function chargerCaJour(apiUrl) {
    $.getJSON(apiUrl, function (data) {
        console.log(data.ca_n_j_1);
        if (data.ca_n_j_1 !== null) {
            $('#ca-jour-kpi').text(data.ca_n_j_1.toLocaleString('fr-CH', { minimumFractionDigits: 2 }));
        } else {
            $('#ca-jour-kpi').text('—');
        }
    });
}

function renderBacklogClientChart(apiUrl, chartSelector, tableSelector) {
    $.getJSON(apiUrl, function (result) {
        const chartEl = document.querySelector(chartSelector);
        const tableEl = document.querySelector(tableSelector);

        if (!chartEl || !tableEl) return;

        chartEl.innerHTML = '';
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
                    return val.toFixed(1) + ' %';
                }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return Number(val).toLocaleString('fr-CH', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }) + ' €';
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

        if (chartEl._chart) {
            chartEl._chart.destroy();
        }

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
                            ${safeValues[i].toLocaleString('fr-CH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })} €
                        </div>
                        <div class="text-muted small">
                            ${safeQuantities[i].toLocaleString('fr-CH', {
                maximumFractionDigits: 0
            })} pcs
                        </div>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        tableEl.innerHTML = html;
    });
}
