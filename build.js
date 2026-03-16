const esbuild = require('esbuild');
const fs = require('fs');
const path = require('path');

const distPath = path.join(__dirname, 'dist');

// Arquivos e pastas a serem ignorados
const ignoreList = [
    'node_modules',
    '.git',
    '.vscode',
    'dist',
    'package-lock.json',
    'build.js',
    'minify-all.js',
    '.antigravityignore'
];

/**
 * Deleta recursivamente uma pasta
 */
function cleanDist(target) {
    if (fs.existsSync(target)) {
        fs.rmSync(target, { recursive: true, force: true });
    }
}

/**
 * Percorre e copia/minifica arquivos
 */
async function processDirectory(src, dest) {
    if (!fs.existsSync(dest)) {
        fs.mkdirSync(dest, { recursive: true });
    }

    const files = fs.readdirSync(src);

    for (const file of files) {
        if (ignoreList.includes(file)) continue;

        const srcPath = path.join(src, file);
        const destPath = path.join(dest, file);
        const stat = fs.statSync(srcPath);

        if (stat.isDirectory()) {
            await processDirectory(srcPath, destPath);
        } else {
            const ext = path.extname(file);
            if (ext === '.js' || ext === '.css') {
                try {
                    await esbuild.build({
                        entryPoints: [srcPath],
                        bundle: false,
                        minify: true,
                        outfile: destPath,
                    });
                    console.log(`✔ Minificado & Copiado: ${path.relative(__dirname, srcPath)}`);
                } catch (e) {
                    console.error(`✘ Erro ao minificar ${srcPath}:`, e.message);
                    fs.copyFileSync(srcPath, destPath); // Fallback copy
                }
            } else {
                try {
                    const destDir = path.dirname(destPath);
                    if (!fs.existsSync(destDir)) {
                        fs.mkdirSync(destDir, { recursive: true });
                    }
                    fs.copyFileSync(srcPath, destPath);
                } catch (e) {
                    console.error(`✘ Erro ao copiar ${srcPath}:`, e.message);
                }
            }
        }
    }
}

async function build() {
    console.log('--- Iniciando Build para Produção (/dist) ---');

    console.log('Limpiando pasta dist...');
    cleanDist(distPath);

    console.log('Copiando e processando arquivos...');
    await processDirectory(__dirname, distPath);

    console.log('--- Build Concluído com Sucesso! ---');
    console.log(`Pasta de saída: ${distPath}`);
}

build().catch(err => {
    console.error('Falha no Build:', err);
    process.exit(1);
});
