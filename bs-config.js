module.exports = {
    proxy: "https://dev.franguxo.app.br/", // URL do site WordPress
    files: [
        "*.{php,css,js}", // Monitora arquivos PHP, CSS e JS na pasta myd-delivery-pro
        "**/*.{php,css,js}" // Monitora subpastas
    ],
    open: true, // Abre o navegador automaticamente
    notify: false, // Desativa notificações no navegador
    browser: ["chrome"], // Usa o Chrome (ou mude para "firefox", etc.)
    reloadDebounce: 500, // Evita recarregamentos múltiplos rápidos
    https: {
        rejectUnauthorized: false
}
    
};