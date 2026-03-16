const esbuild = require('esbuild');
const fs = require('fs');
const path = require('path');

const foldersToProcess = [
    path.join(__dirname, 'assets', 'js'),
    path.join(__dirname, 'assets', 'css')
];

function getAllFiles(dirPath, arrayOfFiles) {
    const files = fs.readdirSync(dirPath);

    arrayOfFiles = arrayOfFiles || [];

    files.forEach(function (file) {
        if (fs.statSync(dirPath + "/" + file).isDirectory()) {
            arrayOfFiles = getAllFiles(dirPath + "/" + file, arrayOfFiles);
        } else {
            arrayOfFiles.push(path.join(dirPath, "/", file));
        }
    });

    return arrayOfFiles;
}

async function minifyAll() {
    console.log('--- Iniciando Minificação Global ---');

    let files = [];
    foldersToProcess.forEach(folder => {
        if (fs.existsSync(folder)) {
            files = files.concat(getAllFiles(folder));
        }
    });

    const tasks = files.filter(file => {
        const ext = path.extname(file);
        return (ext === '.js' || ext === '.css');
    }).map(async (file) => {
        const ext = path.extname(file);
        const isMin = file.endsWith('.min' + ext);
        const outfile = isMin ? file : file.replace(ext, '.min' + ext);

        try {
            await esbuild.build({
                entryPoints: [file],
                bundle: false,
                minify: true,
                outfile: outfile,
            });
            console.log(`✔ Minificado: ${path.relative(__dirname, file)} -> ${path.relative(__dirname, outfile)}`);
        } catch (e) {
            console.error(`✘ Erro em ${path.relative(__dirname, file)}:`, e.message);
        }
    });

    await Promise.all(tasks);
    console.log('--- Minificação Concluída ---');
}

minifyAll();
