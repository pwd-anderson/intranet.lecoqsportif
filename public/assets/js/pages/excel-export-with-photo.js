/**
 * Export Excel avec photos via ExcelJS
 * Utilisable depuis n'importe quel template Twig
 *
 * Usage :
 *   ExcelExportWithPhoto.export(gridOptions, columnApi, {
 *       fileName: 'mon_fichier.xlsx',
 *       photoColumn: 'PHOTO',
 *       articleColumn: 'ARTICLE',
 *       imageBaseUrl: '/fr/sales/best_demand_per_style_image_base64',
 *       rowHeight: 70,
 *       headerColor: '024185',
 *   });
 */
const ExcelExportWithPhoto = (function () {

    function showLoader() {
        const overlay = document.getElementById('excelLoaderOverlay');
        const percent = document.getElementById('excelLoaderPercent');
        const fill = document.getElementById('excelLoaderBarFill');
        const sub = document.getElementById('excelLoaderSub');

        if (!overlay) return;
        if (percent) percent.textContent = '0%';
        if (fill) fill.style.width = '0%';
        if (sub) sub.textContent = 'Chargement des images...';
        overlay.classList.add('active');
    }

    function hideLoader() {
        const overlay = document.getElementById('excelLoaderOverlay');
        if (overlay) overlay.classList.remove('active');
    }

    function updateLoader(p) {
        const percent = document.getElementById('excelLoaderPercent');
        const fill = document.getElementById('excelLoaderBarFill');
        const sub = document.getElementById('excelLoaderSub');

        if (percent) percent.textContent = p + '%';
        if (fill) fill.style.width = p + '%';
        if (sub && p === 100) sub.textContent = 'Génération du fichier...';
    }

    async function fetchImage(imageBaseUrl, article) {
        if (!article) return null;

        try {
            const response = await fetch(`${imageBaseUrl}/${encodeURIComponent(article)}`);
            const data = await response.json();

            if (!data.success || !data.base64) return null;

            const clean = data.base64
                .replace(/^data:image\/[a-zA-Z0-9.+-]+;base64,/, '')
                .replace(/[\r\n\t\s]/g, '');

            const padded = clean.padEnd(Math.ceil(clean.length / 4) * 4, '=');

            try {
                atob(padded);
            } catch (e) {
                console.error('Base64 invalide pour article', article, e);
                return null;
            }

            return {
                id: 'img_' + article,
                base64: padded,
                imageType: data.extension || 'jpg'
            };

        } catch (e) {
            console.error('Erreur fetch image pour', article, e);
            return null;
        }
    }

    async function exportExcel(gridOptions, columnApi, options = {}) {
        const {
            fileName = 'export.xlsx',
            photoColumn = 'PHOTO',
            articleColumn = 'ARTICLE',
            imageBaseUrl = '',
            rowHeight = 70,
            headerColor = '024185',
            headerFontColor = 'FFFFFF',
            onProgress = null,
        } = options;

        // Afficher le loader
        showLoader();

        try {
            const workbook = new ExcelJS.Workbook();
            const worksheet = workbook.addWorksheet('Export');

            const displayedCols = columnApi.getAllDisplayedColumns();
            const colIds = displayedCols.map(c => c.getColId());

            worksheet.columns = displayedCols.map(col => ({
                header: col.getColDef().headerName || col.getColId(),
                key: col.getColId(),
                width: col.getColId() === photoColumn
                    ? 18
                    : Math.max(10, Math.round(col.getActualWidth() / 7))
            }));

            // Style header
            worksheet.getRow(1).eachCell(cell => {
                cell.fill = {
                    type: 'pattern',
                    pattern: 'solid',
                    fgColor: { argb: 'FF' + headerColor }
                };
                cell.font = { bold: true, color: { argb: 'FF' + headerFontColor } };
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
            });
            worksheet.getRow(1).height = 20;

            // Collecter les nodes
            const rowNodes = [];
            gridOptions.api.forEachNodeAfterFilterAndSort(node => {
                if (!node.group) rowNodes.push(node);
            });

            // Dédupliquer les articles pour ne pas charger 2x la même image
            const uniqueArticles = [...new Set(
                rowNodes.map(n => n.data?.[articleColumn]).filter(Boolean)
            )];
            const total = uniqueArticles.length;

            // Pré-charger toutes les images avec progression
            const imageCache = {};
            for (let i = 0; i < uniqueArticles.length; i++) {
                const article = uniqueArticles[i];
                imageCache[article] = await fetchImage(imageBaseUrl, article);

                const p = Math.round(((i + 1) / total) * 100);
                updateLoader(p);
                if (onProgress) onProgress(p);
            }

            // Ajouter toutes les lignes SANS image d'abord
            for (let i = 0; i < rowNodes.length; i++) {
                const node = rowNodes[i];
                const rowData = {};

                colIds.forEach(colId => {
                    rowData[colId] = colId === photoColumn ? '' : (node.data?.[colId] ?? '');
                });

                const excelRow = worksheet.addRow(rowData);
                excelRow.height = rowHeight;
                excelRow.eachCell(cell => {
                    cell.alignment = { vertical: 'middle' };
                });
            }

            // Insérer les images APRÈS toutes les lignes
            const photoColIndex = colIds.indexOf(photoColumn);

            if (photoColIndex !== -1 && imageBaseUrl) {
                for (let i = 0; i < rowNodes.length; i++) {
                    const article = rowNodes[i].data?.[articleColumn];
                    const img = article ? imageCache[article] : null;

                    if (!img?.base64) {
                        console.warn('Pas d\'image pour article', article);
                        continue;
                    }

                    try {
                        const imageId = workbook.addImage({
                            base64: img.base64,
                            extension: img.imageType || 'jpeg',
                        });

                        worksheet.addImage(imageId, {
                            tl: { col: photoColIndex,     row: i + 1 },
                            br: { col: photoColIndex + 1, row: i + 2 },
                            editAs: 'oneCell'
                        });
                    } catch (e) {
                        console.error('Erreur insertion image pour', article, e);
                    }
                }
            }

            // Téléchargement
            const buffer = await workbook.xlsx.writeBuffer();
            const blob = new Blob([buffer], {
                type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            });
            saveAs(blob, fileName);

        } finally {
            // Ferme le loader dans tous les cas (succès ou erreur)
            hideLoader();
        }
    }

    return { export: exportExcel };

})();
