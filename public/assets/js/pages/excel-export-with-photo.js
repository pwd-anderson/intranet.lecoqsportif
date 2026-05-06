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

    // ➕ État partagé pour l'annulation
    let _currentAbortController = null;
    let _isCancelled = false;

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

    // ➕ Annulation : déclenchée par la croix
    function cancelExport() {
        _isCancelled = true;
        if (_currentAbortController) {
            _currentAbortController.abort();
        }
        hideLoader();
    }

    // ➕ Bind du bouton fermer (une seule fois, dès le chargement du script)
    function bindCloseButton() {
        const btn = document.getElementById('excelLoaderCloseBtn');
        if (btn && !btn.dataset.bound) {
            btn.dataset.bound = '1';
            btn.addEventListener('click', cancelExport);
        }
    }

    // ✏️ MODIFIÉ : prend un signal AbortController
    async function fetchImage(imageBaseUrl, article, signal) {
        if (!article) return null;

        try {
            const response = await fetch(
                `${imageBaseUrl}/${encodeURIComponent(article)}`,
                { signal }   // ← AJOUT
            );
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
            // ➕ Si annulation, on remonte silencieusement
            if (e.name === 'AbortError') {
                throw e;
            }
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
            customCellValue = {},
            articleTransform = null,
        } = options;

        // ➕ Réinitialisation de l'état d'annulation
        _isCancelled = false;
        _currentAbortController = new AbortController();
        const signal = _currentAbortController.signal;

        // ➕ Bind la croix (au cas où le DOM n'était pas prêt avant)
        bindCloseButton();

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

            const rowNodes = [];
            gridOptions.api.forEachNodeAfterFilterAndSort(node => {
                if (!node.group) rowNodes.push(node);
            });

            const uniqueArticles = [...new Set(
                rowNodes
                    .map(n => {
                        const raw = n.data?.[articleColumn];
                        if (!raw) return null;
                        return typeof articleTransform === 'function' ? articleTransform(raw) : raw;
                    })
                    .filter(Boolean)
            )];
            const total = uniqueArticles.length;

            const imageCache = {};
            for (let i = 0; i < uniqueArticles.length; i++) {
                // ➕ Vérification d'annulation à chaque itération
                if (_isCancelled) {
                    return;
                }

                const article = uniqueArticles[i];
                imageCache[article] = await fetchImage(imageBaseUrl, article, signal);

                const p = Math.round(((i + 1) / total) * 100);
                updateLoader(p);
                if (onProgress) onProgress(p);
            }

            // ➕ Vérification après la phase 1 (avant ExcelJS)
            if (_isCancelled) {
                return;
            }

            // Ajouter toutes les lignes SANS image d'abord
            for (let i = 0; i < rowNodes.length; i++) {
                const node = rowNodes[i];
                const rowData = {};

                colIds.forEach(colId => {
                    if (colId === photoColumn) {
                        rowData[colId] = '';
                    } else if (typeof customCellValue[colId] === 'function') {
                        rowData[colId] = customCellValue[colId](node.data ?? {}) ?? '';
                    } else {
                        rowData[colId] = node.data?.[colId] ?? '';
                    }
                });

                const excelRow = worksheet.addRow(rowData);
                excelRow.height = rowHeight;
                excelRow.eachCell(cell => {
                    cell.alignment = { vertical: 'middle', wrapText: true };
                });
            }

            // Insérer les images APRÈS toutes les lignes
            const photoColIndex = colIds.indexOf(photoColumn);

            if (photoColIndex !== -1 && imageBaseUrl) {
                for (let i = 0; i < rowNodes.length; i++) {
                    // ➕ Vérification d'annulation pendant insertion images
                    if (_isCancelled) {
                        return;
                    }

                    const rawArticle = rowNodes[i].data?.[articleColumn];
                    const article = rawArticle && typeof articleTransform === 'function'
                        ? articleTransform(rawArticle)
                        : rawArticle;
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

            // ➕ Vérification finale avant écriture du fichier
            if (_isCancelled) {
                return;
            }

            const buffer = await workbook.xlsx.writeBuffer();
            const blob = new Blob([buffer], {
                type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            });
            saveAs(blob, fileName);

        } catch (e) {
            // ➕ Annulation = silence. Autres erreurs = log
            if (e.name === 'AbortError' || _isCancelled) {
                return;
            }
            console.error('Erreur exportExcel:', e);
        } finally {
            hideLoader();
            _currentAbortController = null;
        }
    }

    return { export: exportExcel, cancel: cancelExport };

})();
