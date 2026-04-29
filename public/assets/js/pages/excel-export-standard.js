/**
 * Export Excel standard avec loader de progression simulée
 * Utilisable depuis n'importe quel template Twig
 *
 * Usage :
 *   await ExcelExportStandard.export(gridOptions, fileName, options);
 *
 * Options disponibles :
 *   - processCellCallback : function(params) → valeur personnalisée par cellule
 *   - processHeaderCallback : function(params) → nom de colonne personnalisé
 */
const ExcelExportStandard = (function () {

    function showLoader(title) {
        const overlay = document.getElementById('excelLoaderOverlay');
        const percent = document.getElementById('excelLoaderPercent');
        const fill = document.getElementById('excelLoaderBarFill');
        const sub = document.getElementById('excelLoaderSub');

        if (!overlay) return;

        percent.textContent = '0%';
        fill.style.width = '0%';
        sub.textContent = title || 'Génération du fichier Excel...';
        overlay.classList.add('active');
    }

    function hideLoader() {
        const overlay = document.getElementById('excelLoaderOverlay');
        if (!overlay) return;
        overlay.classList.remove('active');
    }

    function updateLoader(percent) {
        const percentEl = document.getElementById('excelLoaderPercent');
        const fill = document.getElementById('excelLoaderBarFill');

        if (percentEl) percentEl.textContent = percent + '%';
        if (fill) fill.style.width = percent + '%';
    }

    function updateSub(text) {
        const sub = document.getElementById('excelLoaderSub');
        if (sub) sub.textContent = text;
    }

    async function startSimulatedProgress() {
        return new Promise(resolve => {
            let current = 0;

            const timer = setInterval(() => {
                if (current < 85) {
                    current = Math.min(current + Math.random() * 15, 85);
                    updateLoader(Math.round(current));
                } else {
                    clearInterval(timer);
                    resolve(timer);
                }
            }, 200);

            // On expose le timer pour pouvoir l'annuler depuis l'extérieur
            resolve._timer = timer;
        });
    }

    async function exportExcel(gridOptions, fileName, options = {}) {
        const {
            processCellCallback = null,
            processHeaderCallback = p => p.column.getColDef().headerName,
        } = options;

        showLoader('Génération du fichier Excel...');

        // Timer de progression simulée
        let progressTimer = null;
        let current = 0;

        progressTimer = setInterval(() => {
            if (current < 85) {
                current = Math.min(current + Math.random() * 15, 85);
                updateLoader(Math.round(current));
            }
        }, 200);

        // Laisse le DOM afficher le loader avant de bloquer le thread
        await new Promise(resolve => setTimeout(resolve, 50));

        try {
            const exportParams = {
                fileName: fileName,
                processHeaderCallback: processHeaderCallback,
            };

            if (processCellCallback) {
                exportParams.processCellCallback = processCellCallback;
            }

            gridOptions.api.exportDataAsExcel(exportParams);

        } finally {
            clearInterval(progressTimer);

            updateLoader(100);
            updateSub('Téléchargement en cours...');

            setTimeout(() => {
                hideLoader();
            }, 600);
        }
    }

    return { export: exportExcel };

})();
