<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DOCX document preview</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { margin: 0; background: #f8fafc; color: #0f172a; font-family: Inter, system-ui, sans-serif; }
        main { box-sizing: border-box; min-height: 100vh; padding: 1rem; }
        #docx-preview { min-height: calc(100vh - 2rem); }
        #docx-preview .docx-wrapper { background: transparent; padding: 0; }
        #docx-preview .docx { margin: 0 auto 1rem; }
    </style>
</head>
<body>
    <main>
        <div id="docx-preview" aria-label="DOCX document preview"></div>
    </main>
    <script>
        const preview = document.getElementById('docx-preview');

        const renderDocument = () => {
            fetch(@json($documentUrl), { credentials: 'same-origin' })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('The document could not be loaded.');
                    }

                    return response.arrayBuffer();
                })
                .then((documentData) => window.renderDocxPreview(documentData, preview, null, {
                    inWrapper: true,
                    renderHeaders: true,
                    renderFooters: true,
                    useBase64URL: true,
                }))
                .catch(() => {
                    preview.textContent = 'The document could not be previewed. Download it to review the original file.';
                });
        };

        if (typeof window.renderDocxPreview === 'function') {
            renderDocument();
        } else {
            window.addEventListener('docx-preview-ready', renderDocument, { once: true });
        }
    </script>
</body>
</html>
