// Função para substituir apenas &#8211; por '-'
function replace8211(text) {
  if (!text) return '';
  return text.replace(/&#8211;/g, '-');
}
const { app, BrowserWindow, Menu, shell, ipcMain, Notification, dialog, session, globalShortcut } = require('electron');

// Permite autoplay sem gesto do usuário no Electron (desativa a política do Chromium)
// Nota: esta alteração torna o aplicativo capaz de reproduzir áudio automaticamente
// sem necessidade de interação do usuário. Use com cautela.
try {
  app.commandLine.appendSwitch('autoplay-policy', 'no-user-gesture-required');
} catch (e) {
  console.warn('Não foi possível aplicar autoplay-policy:', e && e.message ? e.message : e);
}
const util = require('util');

const MAX_LOG_ENTRIES = 500;
const logHistory = [];
const logSubscribers = new Map();

const originalConsole = {
  log: console.log.bind(console),
  info: console.info.bind(console),
  warn: console.warn.bind(console),
  error: console.error.bind(console)
};

function formatLogArg(arg) {
  if (typeof arg === 'string') return arg;
  if (arg instanceof Error) return arg.stack || arg.message || String(arg);
  return util.inspect(arg, { depth: 4, colors: false, breakLength: 120 });
}

function pushLogEntry(level, args) {
  try {
    const message = args.map(formatLogArg).join(' ');
    const entry = {
      timestamp: new Date().toLocaleString('pt-BR', { hour12: false }),
      level,
      message
    };
    logHistory.push(entry);
    if (logHistory.length > MAX_LOG_ENTRIES) {
      logHistory.splice(0, logHistory.length - MAX_LOG_ENTRIES);
    }

    for (const [id, target] of Array.from(logSubscribers.entries())) {
      if (!target || target.isDestroyed()) {
        logSubscribers.delete(id);
        continue;
      }
      try {
        target.send('logs:new-entry', entry);
      } catch (err) {
        logSubscribers.delete(id);
        originalConsole.error('Failed to deliver log entry to subscriber:', err);
      }
    }
  } catch (err) {
    originalConsole.error('Failed to register log entry:', err);
  }
}

['log', 'info', 'warn', 'error'].forEach((level) => {
  console[level] = (...args) => {
    originalConsole[level](...args);
    pushLogEntry(level, args);
  };
});

ipcMain.handle('logs:get-history', () => logHistory.slice());
ipcMain.handle('logs:subscribe', (event) => {
  const wc = event.sender;
  logSubscribers.set(wc.id, wc);
  wc.once('destroyed', () => {
    logSubscribers.delete(wc.id);
  });
  return true;
});
ipcMain.handle('logs:unsubscribe', (event) => {
  logSubscribers.delete(event.sender.id);
  return true;
});

if (process.platform === 'win32') {
  try {
    app.setAppUserModelId('Franguxo Gestor de Pedidos');
  } catch (e) {
    console.warn('Não foi possível definir AppUserModelId:', e && e.message ? e.message : e);
  }
}

// Habilita recarregamento automático apenas em desenvolvimento
if (!app.isPackaged) {
  try {
    require('electron-reload')(__dirname);
  } catch (e) {
    console.warn('electron-reload indisponível em modo dev:', e && e.message ? e.message : e);
  }
}

const path = require('path');
const { autoUpdater } = require('electron-updater');
const configPath = path.join(app.getPath('userData'), 'config.json');
console.log('Config path:', configPath);
const fs = require('fs');

// Carregar configuração do aplicativo
let appConfig = {};
try {
  const appConfigPath = path.join(__dirname, 'app-config.json');
  appConfig = JSON.parse(fs.readFileSync(appConfigPath, 'utf8'));
  console.log('App config loaded:', appConfig);
} catch (e) {
  console.error('Failed to load app config:', e);
  // Fallback para valores padrão
  appConfig = {
    wordpress: { url: "https://franguxo.app.br", loginPath: "/pedido" },
    auth: { serviceName: "franguxo-gestor", accountName: "refreshToken" },
    printServer: { port: 3420 }
  };
}

// Constantes derivadas da configuração
const WP_URL = appConfig.wordpress.url;
const WP_HOSTNAME = new URL(WP_URL).hostname;
function getSavedPrinter() {
  try {
    const config = JSON.parse(fs.readFileSync(configPath, 'utf8'));
    return config.printer || null;
  } catch (e) {
    return null;
  }
}
let printer = null;
try {
  // native module may not be compatible with Electron's Node ABI; load if available
  printer = require('@niick555/node-printer');
} catch (e) {
  console.warn('Aviso: módulo nativo @niick555/node-printer não pôde ser carregado no processo Electron. Irei encaminhar impressões ao servidor local via HTTP.', e && e.message ? e.message : e);
}
const http = require('http');
const { spawn } = require('child_process');

// Função para centralizar texto
function centerText(text, width = 32) {
  const padding = Math.max(0, width - text.length);
  const leftPad = Math.floor(padding / 2);
  const rightPad = padding - leftPad;
  return ' '.repeat(leftPad) + text + ' '.repeat(rightPad);
}

// Função para remover acentos e caracteres especiais
function normalizeText(text) {
  return text
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^\x00-\x7F]/g, '');
}

// Função para imprimir recibo de teste
function printTestReceipt() {
  const texto = normalizeText('Recibo de Teste\nValor: R$ 123,45\nObrigado pela preferência!');
  const savedPrinter = getSavedPrinter();
  if (typeof mainWindow !== 'undefined' && mainWindow.webContents) {
    mainWindow.webContents.executeJavaScript('window.showPrintLoading && window.showPrintLoading();');
  }
  if (printer) {
    printer.printDirect({
      data: texto,
      type: 'RAW',
      printer: savedPrinter,
      success: function (jobID) {
        console.log('Impressão enviada, JobID:', jobID);
        if (typeof mainWindow !== 'undefined' && mainWindow.webContents) {
          mainWindow.webContents.executeJavaScript('window.hidePrintLoading && window.hidePrintLoading();');
        }
      },
      error: function (err) {
        console.error('Erro ao imprimir:', err);
        if (typeof mainWindow !== 'undefined' && mainWindow.webContents) {
          mainWindow.webContents.executeJavaScript('window.hidePrintLoading && window.hidePrintLoading();');
        }
      }
    });
  } else {
    // forward to local print server
    if (typeof mainWindow !== 'undefined' && mainWindow.webContents) {
      mainWindow.webContents.executeJavaScript('window.showPrintLoading && window.showPrintLoading();');
    }
    sendToLocalPrintServer({ text: texto, printer: savedPrinter })
      .catch(err => console.error('Erro encaminhando para print-server:', err))
      .finally(() => {
        if (typeof mainWindow !== 'undefined' && mainWindow.webContents) {
          mainWindow.webContents.executeJavaScript('window.hidePrintLoading && window.hidePrintLoading();');
        }
      });
  }
}

// Função para formatar linha com preço na direita
function formatItemLine(name, price, width = 32) {
  const priceStr = 'R$ ' + price;
  const nameLength = name.length;
  const priceLength = priceStr.length;
  const spaces = Math.max(1, width - nameLength - priceLength);
  return name + ' '.repeat(spaces) + priceStr;
}

// Função para imprimir recibo de pedido específico
function printOrderReceipt(orderData) {
  console.log('=== INICIANDO IMPRESSÃO DE PEDIDO ===');
  console.log('Dados do pedido recebidos:', JSON.stringify(orderData, null, 2));

  let texto = '';
  if (orderData.store_name) {
    texto += centerText(orderData.store_name) + '\n';
  }
  texto += ' -------------------------------\n';
  texto += centerText('Pedido #' + (orderData.id || 'N/A')) + '\n';
  texto += ' -------------------------------\n';
  texto += 'Cliente: ' + (orderData.customer_name || 'N/A') + '\n';
  texto += 'Telefone: ' + (orderData.customer_phone || 'N/A') + '\n';
  texto += 'Data: ' + (orderData.date || new Date().toLocaleString()) + '\n';

  // Endereço de entrega
  if (orderData.address) {
    texto += 'Endereço: ' + orderData.address;
    if (orderData.address_number) texto += ', ' + orderData.address_number;
    texto += '\n';
    if (orderData.neighborhood) {
      texto += 'Bairro: ' + orderData.neighborhood + (orderData.real_neighborhood ? ' ***' : '') + '\n';
    }
    const compValue = orderData.address_comp || orderData.address_complement || '';
    if (compValue && String(compValue).trim() !== '') {
      texto += 'Comp: ' + compValue + '\n';
    }
    if (orderData.zipcode) {
      texto += 'CEP: ' + orderData.zipcode + '\n';
    }
  }

  texto += ' -------------------------------\n';
  texto += ' ITENS DO PEDIDO:\n';

  if (orderData.items && orderData.items.length > 0) {
    console.log('Número de itens:', orderData.items.length);
    orderData.items.forEach((item, index) => {
      console.log('Item ' + index + ':', item);
      // Extrair quantidade e nome limpo (evitar duplicar caso o nome já venha com "1 x ...")
      let fixedProductName = replace8211(item.product_name);

      // Tenta capturar quantidade se o nome começar com padrões como "1 x ", "1x ", etc.
      let match = fixedProductName.match(/^(\d+)\s*x\s*(.*)$/i) || fixedProductName.match(/^\((\d+)x\)\s*(.*)$/i);

      let quantidade = match ? match[1] : (item.quantity || '1');
      let cleanName = match ? match[2] : fixedProductName;

      // Linha principal: quantidade, nome, preço alinhados
      const colPreco = 9; // ex: 'R$61,99'
      const width = 32;
      let precoStr = 'R$' + (typeof item.product_price === 'string' ? item.product_price : Number(item.product_price).toFixed(2).replace('.', ','));
      let nomeCol = '(' + quantidade + 'x) ' + cleanName;
      if (nomeCol.length + precoStr.length > width) {
        // Nome muito longo: quebra em linha extra
        texto += nomeCol + '\n';
        texto += ' '.repeat(Math.max(1, width - precoStr.length)) + precoStr + '\n';
      } else {
        let espacos = width - nomeCol.length - precoStr.length;
        texto += nomeCol + ' '.repeat(Math.max(1, espacos)) + precoStr + '\n';
      }



      // Extras agrupados
      if (item.extras && item.extras.groups && item.extras.groups.length > 0) {
        texto += '\n';
        item.extras.groups.forEach(group => {
          // Nome do grupo: exibir se houver pelo menos 1 extra no grupo
          if (group.group && group.group.trim() !== '' && group.items && group.items.length > 0) {
            texto += '    ' + group.group.trim() + '\n';
          }
          // Itens do grupo (se houver)
          if (group.items && group.items.length > 0) {
            group.items.forEach(extraItem => {
              if (parseInt(extraItem.quantity) > 0) {
                const extraTotal = parseFloat(extraItem.price) * parseInt(extraItem.quantity);
                let extraNome = extraItem.quantity + 'x ' + extraItem.name;
                let extraPreco = 'R$' + extraTotal.toFixed(2).replace('.', ',');
                let extraEspacos = width - 4 - extraNome.length - extraPreco.length; // 4 = indent
                texto += '    ' + extraNome + ' '.repeat(Math.max(1, extraEspacos)) + extraPreco + '\n';
              }
            });
          }
        });
      }

      // Nota/observação
      if (item.product_note && item.product_note.trim() !== '') {
        texto += '    *Obs: ' + item.product_note.trim() + '\n';
      }
    });
  } else {
    console.log('Nenhum item encontrado');
    texto += ' Nenhum item encontrado\n';
  }

  // Mapeamento especial para status SumUp
  let paymentStatusDisplay = orderData.payment_status || 'N/A';
  if (typeof paymentStatusDisplay === 'string') {
    if (paymentStatusDisplay.toLowerCase() === 'sumup - pix') {
      paymentStatusDisplay = 'pix';
    } else if (paymentStatusDisplay.toLowerCase() === 'sumup - cartão de crédito' || paymentStatusDisplay.toLowerCase() === 'sumup - cartao de credito') {
      paymentStatusDisplay = 'Cartão de Crédito';
    } else if (paymentStatusDisplay.toLowerCase() === 'sumup - cartão de débito' || paymentStatusDisplay.toLowerCase() === 'sumup - cartao de debito') {
      paymentStatusDisplay = 'Cartão de Débito';
    }
  }

  // Aviso de cobrança se pagamento pendente
  if (orderData.payment_status && orderData.payment_status.toLowerCase() === 'waiting') {
    texto += centerText('-------------------------------') + '\n';
    texto += centerText('*COBRAR DO CLIENTE*') + '\n';
    texto += centerText(orderData.payment_method || '') + '\n';
    texto += centerText('-------------------------------') + '\n';
  }
  // Aviso de cobrança se pagamento realizado
  if (orderData.payment_status && orderData.payment_status.toLowerCase() === 'paid') {
    texto += centerText('-------------------------------') + '\n';
    texto += centerText('*PAGO ONLINE*') + '\n';
    texto += centerText(orderData.payment_method || '') + '\n';
    texto += centerText('-------------------------------') + '\n';
  }

  // Valor total do pedido sem desconto
  if (orderData.subtotal && parseFloat(orderData.subtotal) > 0) {
    texto += 'Valor total do:'.padEnd(22) + 'R$ ' + orderData.subtotal + '\n';
    texto += 'pedido\n';
  }

  // Taxa de entrega
  if (orderData.delivery_price && parseFloat(orderData.delivery_price) > 0) {
    texto += 'Taxa de entrega:'.padEnd(22) + 'R$ ' + orderData.delivery_price + '\n';
  }

  // Cupom e desconto
  if (orderData.coupon_name) {
    texto += 'Cupom: ' + orderData.coupon_name + '\n';
  }
  if (orderData.coupon_discount && parseFloat(orderData.coupon_discount) > 0) {
    texto += 'Desconto cupom: '.padEnd(21) + '-R$ ' + orderData.coupon_discount + '\n';
  }

  // Total final
  texto += 'Total:'.padEnd(22) + 'R$ ' + (orderData.total || '0,00') + '\n';

  // Forma de pagamento
  if (orderData.payment_method) {
    var changeVal = parseFloat(orderData.payment_change || 0);
    if (changeVal > 0) {
      texto += ' Valor para levar de troco: R$ ' + orderData.payment_change + '\n';
    }
  }

  // Aviso de cobrança se pagamento pendente
  if (orderData.payment_status && orderData.payment_status.toLowerCase() === 'waiting') {
    texto += centerText('-------------------------------') + '\n';
    texto += 'Cobrar do cliente: *' + 'R$ ' + (orderData.total || '0,00') + '\n';
    texto += centerText('-------------------------------') + '\n';
  }




  // Comando ESC/POS para corte de papel (total cut)
  const CUT_PAPER = '\x1D\x56\x00';

  const textoNormalizado = normalizeText(texto);
  const savedPrinter = getSavedPrinter();
  if (printer) {
    printer.printDirect({ data: textoNormalizado, type: 'RAW', printer: savedPrinter, success: function (jobID) { console.log('Impressão do pedido enviada, JobID:', jobID); console.log('ADICIONAR CORTE DE PAPEL NO FINAL') }, error: function (err) { console.error('Erro ao imprimir pedido:', err); } });
  } else {
    // forward the structured order to the local print server (server will format)
    sendToLocalPrintServer({ orderData: orderData, escpos: true, printer: savedPrinter }).then(res => {
      console.log('Encaminhado para print-server:', res);
    }).catch(err => {
      console.error('Erro ao encaminhar pedido para print-server:', err);
    });
  }
}

function sendToLocalPrintServer(payload) {
  return new Promise((resolve, reject) => {
    try {
      const data = JSON.stringify(payload);
      const opts = {
        hostname: '127.0.0.1',
        port: 3420,
        path: '/print',
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Content-Length': Buffer.byteLength(data)
        }
      };
      const req = http.request(opts, (res) => {
        let body = '';
        res.setEncoding('utf8');
        res.on('data', (chunk) => body += chunk);
        res.on('end', () => {
          try { const parsed = JSON.parse(body || '{}'); resolve(parsed); } catch (e) { resolve(body); }
        });
      });
      req.on('error', (e) => reject(e));
      req.write(data);
      req.end();
    } catch (e) { reject(e); }
  });
}

// Handler IPC para impressão
ipcMain.handle('impressao:printTestReceipt', async () => {
  printTestReceipt();
});

ipcMain.handle('impressao:printOrderReceipt', async (event, orderData) => {
  printOrderReceipt(orderData);
});
// Expor logout via IPC para eventual botão no renderer e linha de comando
ipcMain.handle('logout', async () => {
  await logoutUser();
  return { success: true };
});
ipcMain.handle('logout-user', async () => {
  await logoutUser();
  return { success: true };
});
function openImpressaoWindow() {
  const printWindow = new BrowserWindow({
    width: 500,
    height: 350,
    resizable: false,
    minimizable: true,
    maximizable: false,
    title: 'Configurar Impressão',
    webPreferences: {
      nodeIntegration: false,
      contextIsolation: true,
      preload: path.join(__dirname, 'preload.js')
    }
  });
  printWindow.setMenuBarVisibility(true);
  printWindow.loadFile('impressao.html');
}

function openLogWindow() {
  if (logWindow && !logWindow.isDestroyed()) {
    logWindow.focus();
    return;
  }

  logWindow = new BrowserWindow({
    width: 900,
    height: 600,
    minWidth: 600,
    minHeight: 400,
    title: 'Console de Logs',
    webPreferences: {
      nodeIntegration: false,
      contextIsolation: true,
      preload: path.join(__dirname, 'preload.js')
    },
    autoHideMenuBar: true,
    show: true
  });

  logWindow.on('closed', () => {
    logWindow = null;
  });

  logWindow.loadFile('logs.html').catch((err) => {
    console.error('Erro ao carregar logs.html:', err);
  });
}


let mainWindow;
let logWindow = null;
let manualUpdateCheck = false;

function createWindow() {
  mainWindow = new BrowserWindow({
    width: 1200,
    height: 800,
    minWidth: 800,
    minHeight: 600,
    icon: path.join(__dirname, 'assets', 'icon.png'),
    webPreferences: {
      nodeIntegration: false,
      contextIsolation: true,
      enableRemoteModule: false,
      webSecurity: true,
      autoplayPolicy: 'no-user-gesture-required',
      preload: path.join(__dirname, 'preload.js')
    },
    show: false,
    titleBarStyle: 'default',
    autoHideMenuBar: false
  });

  mainWindow.loadURL(WP_URL + appConfig.wordpress.loginPath);



  mainWindow.once('ready-to-show', async () => {
    // Verificar cookies antes de mostrar a janela
    const cookies = await session.defaultSession.cookies.get({ url: WP_URL });
    console.log('=== Window ready to show ===');
    console.log('Cookies available for', WP_URL + ':', cookies.map(c => ({
      name: c.name,
      domain: c.domain,
      path: c.path,
      secure: c.secure,
      httpOnly: c.httpOnly,
      sameSite: c.sameSite
    })));

    mainWindow.maximize();
    mainWindow.show();
    if (process.platform === 'darwin') {
      app.dock.show();
    }
  });

  mainWindow.webContents.setWindowOpenHandler(({ url }) => {
    shell.openExternal(url);
    return { action: 'deny' };
  });

  mainWindow.webContents.on('will-navigate', (event, navigationUrl) => {
    console.log('[DEBUG] will-navigate:', navigationUrl);
    const parsedUrl = new URL(navigationUrl);

    // Abrir links externos no navegador padrão se não pertencer ao domínio franguxo
    if (!parsedUrl.hostname.includes('franguxo.app.br')) {
      event.preventDefault();
      shell.openExternal(navigationUrl);
    }
  });

  mainWindow.webContents.on('will-redirect', (event, navigationUrl) => {
    console.log('[DEBUG] will-redirect:', navigationUrl);
  });

  // Garantir que não vá para o wp-admin após o login
  mainWindow.webContents.on('did-navigate', (event, navigationUrl) => {
    console.log('[DEBUG] did-navigate:', navigationUrl);
  });

  mainWindow.webContents.on('did-fail-load', (event, errorCode, errorDescription, validatedURL) => {
    console.log('[DEBUG] did-fail-load:', validatedURL, errorCode, errorDescription);
  });

  // Forçar retenção dos cookies de autenticação do WordPress
  mainWindow.webContents.session.webRequest.onHeadersReceived((details, callback) => {
    let responseHeaders = details.responseHeaders;
    if (responseHeaders['set-cookie'] || responseHeaders['Set-Cookie']) {
      const cookies = responseHeaders['set-cookie'] || responseHeaders['Set-Cookie'];
      const updatedCookies = cookies.map(cookie => {
        // Se for cookie do wordpress, injetamos max-age de 1 ano para ele nunca se perder no Electron
        if (cookie.includes('wordpress_') || cookie.includes('PHPSESSID')) {
          if (!cookie.toLowerCase().includes('expires=') && !cookie.toLowerCase().includes('max-age=')) {
            return `${cookie}; Max-Age=31536000`; // Expira daqui um ano
          }
        }
        return cookie;
      });
      if (responseHeaders['set-cookie']) responseHeaders['set-cookie'] = updatedCookies;
      if (responseHeaders['Set-Cookie']) responseHeaders['Set-Cookie'] = updatedCookies;
    }
    callback({ responseHeaders });
  });

  mainWindow.webContents.on('did-finish-load', () => {
    // Garantir que a janela seja sempre mostrada quando o carregamento for concluído,
    // pois a página de wp-login às vezes omite o ready-to-show se não for acionado corretamente.
    if (!mainWindow.isVisible()) {
      mainWindow.maximize();
      mainWindow.show();
    }
  });

  // Salvar cookies no disco quando a janela for fechada
  mainWindow.on('close', async () => {
    try {
      await mainWindow.webContents.session.flushStorageData();
    } catch (e) {
      console.log('Erro ao salvar storage data no fechamento:', e);
    }
  });

  createMenu();

  // Atalho para abrir DevTools com Ctrl+Shift+L
  globalShortcut.register('Ctrl+Shift+L', () => {
    if (mainWindow) {
      mainWindow.webContents.openDevTools();
    }
  });

  mainWindow.on('closed', () => {
    mainWindow = null;
  });

  ipcMain.on('restore-window-on-notification', () => {
    if (!mainWindow) return;
    try {
      if (mainWindow.isMinimized && mainWindow.isMinimized()) mainWindow.restore();
      // Ensure window is visible and focused
      mainWindow.show();
      mainWindow.focus();

      // Audio playback disabled: do not attempt to play sound when restoring window.
      // Previous implementation executed page script to create/play audio; removed.
    } catch (e) {
      console.error('Error handling restore-window-on-notification:', e);
    }
  });

  // Handlers expostos no preload
  ipcMain.handle('get-app-version', () => app.getVersion());
  ipcMain.handle('open-external', (_e, url) => shell.openExternal(url));
  ipcMain.handle('show-notification', (_e, { title, body }) => {
    const notif = new Notification({ title, body });
    notif.show();
  });
  ipcMain.handle('savePrinter', (event, payload) => {
    let config = {};
    try { config = JSON.parse(fs.readFileSync(configPath, 'utf8')); } catch (e) { }
    // payload can be a string (printer) or an object { printer, copies }
    try {
      if (payload && typeof payload === 'object') {
        if (Object.prototype.hasOwnProperty.call(payload, 'printer')) {
          if (payload.printer === null || payload.printer === undefined || payload.printer === '') {
            delete config.printer;
          } else {
            config.printer = payload.printer;
          }
        }
        if (Object.prototype.hasOwnProperty.call(payload, 'copies')) {
          if (payload.copies === null || payload.copies === undefined || payload.copies === '') {
            delete config.copies;
          } else {
            config.copies = payload.copies;
          }
        }
      } else {
        // legacy string payload
        if (payload === null || payload === undefined || payload === '') delete config.printer; else config.printer = payload;
      }
      fs.writeFileSync(configPath, JSON.stringify(config, null, 2));
      return { success: true };
    } catch (e) {
      console.error('Failed to save printer config:', e);
      return { success: false, error: e && e.message };
    }
  });
  ipcMain.handle('loadConfig', () => {
    try { return JSON.parse(fs.readFileSync(configPath, 'utf8')); } catch (e) { return {}; }
  });
}

function createMenu() {
  const template = [
    {
      label: 'Arquivo',
      submenu: [
        {
          label: 'Recarregar',
          accelerator: 'CmdOrCtrl+R',
          click: () => mainWindow && mainWindow.reload()
        },
        {
          label: 'Forçar Recarregar',
          accelerator: 'CmdOrCtrl+Shift+R',
          click: () => mainWindow && mainWindow.reload()
        },
        { type: 'separator' },
        {
          label: 'Deslogar',
          accelerator: 'CmdOrCtrl+L',
          click: () => logoutUser()
        },
        { type: 'separator' },
        {
          label: 'Iniciar com o Windows',
          type: 'checkbox',
          checked: app.getLoginItemSettings().openAtLogin,
          click: (menuItem) => {
            app.setLoginItemSettings({ openAtLogin: menuItem.checked });
          }
        },
        { type: 'separator' },
        {
          label: 'Sair',
          accelerator: process.platform === 'darwin' ? 'Cmd+Q' : 'Ctrl+Q',
          click: () => app.quit()
        }
      ]
    },
    {
      label: 'Configurar impressão',
      click: () => {
        openImpressaoWindow();
      }
    },
    {
      label: 'Editar',
      submenu: [
        { label: 'Desfazer', accelerator: 'CmdOrCtrl+Z', role: 'undo' },
        { label: 'Refazer', accelerator: 'Shift+CmdOrCtrl+Z', role: 'redo' },
        { type: 'separator' },
        { label: 'Cortar', accelerator: 'CmdOrCtrl+X', role: 'cut' },
        { label: 'Copiar', accelerator: 'CmdOrCtrl+C', role: 'copy' },
        { label: 'Colar', accelerator: 'CmdOrCtrl+V', role: 'paste' }
      ]
    },
    {
      label: 'Visualizar',
      submenu: [
        { label: 'Tela Cheia', accelerator: 'F11', role: 'togglefullscreen' },
        { type: 'separator' },
        { label: 'Aumentar Zoom', accelerator: 'CmdOrCtrl+Plus', role: 'zoomin' },
        { label: 'Diminuir Zoom', accelerator: 'CmdOrCtrl+-', role: 'zoomout' },
        { label: 'Zoom Padrão', accelerator: 'CmdOrCtrl+0', role: 'resetzoom' },
        { type: 'separator' },
        {
          label: 'Ferramentas do Desenvolvedor',
          accelerator: 'F12',
          click: () => mainWindow && mainWindow.webContents.toggleDevTools()
        },
        { type: 'separator' },
        {
          label: 'Console de Logs',
          accelerator: 'CmdOrCtrl+Shift+L',
          click: () => openLogWindow()
        }
      ]
    },
    {
      label: 'Ajuda',
      submenu: [
        {
          label: 'Buscar Atualizações…',
          click: () => {
            if (app.isPackaged) {
              manualUpdateCheck = true;
              checkForUpdates();
              // Feedback leve (notificação) sem bloquear a UI
              const notif = new Notification({
                title: 'Atualizações',
                body: 'Verificando atualizações…'
              });
              notif.show();
            } else {
              dialog.showMessageBox(mainWindow, {
                type: 'info',
                title: 'Atualizações',
                message: 'Atualizações automáticas só funcionam no app empacotado.',
                buttons: ['OK']
              });
            }
          }
        },
        {
          label: 'Sobre',
          click: () => {
            dialog.showMessageBox(mainWindow, {
              type: 'info',
              title: 'Sobre',
              message: 'Franguxo Gestor de Pedidos',
              detail: `Versão ${app.getVersion()}\n\nAplicativo para acessar o sistema Franguxo de gestão de pedidos.`,
              buttons: ['OK']
            });
          }
        }
      ]
    }
  ];

  const menu = Menu.buildFromTemplate(template);
  Menu.setApplicationMenu(menu);
}

// --- Funções de atualização automática ---
function registerAutoUpdaterEvents() {
  autoUpdater.on('update-available', (info) => {
    const title = 'Atualização disponível';
    const body = `Versão ${info.version} encontrada. Baixando atualização...`;
    if (manualUpdateCheck) {
      dialog.showMessageBox(mainWindow, {
        type: 'info', title, message: body, buttons: ['OK']
      });
      manualUpdateCheck = false;
    } else {
      const notif = new Notification({ title, body });
      notif.show();
    }
  });

  autoUpdater.on('update-not-available', () => {
    if (manualUpdateCheck) {
      dialog.showMessageBox(mainWindow, {
        type: 'info',
        title: 'Atualizações',
        message: 'Você já está na última versão.',
        buttons: ['OK']
      });
      manualUpdateCheck = false;
    } else {
      console.log('Nenhuma atualização disponível.');
    }
  });

  autoUpdater.on('update-downloaded', (info) => {
    dialog.showMessageBox(mainWindow, {
      type: 'question',
      buttons: ['Reiniciar agora', 'Mais tarde'],
      defaultId: 0,
      cancelId: 1,
      message: `A nova versão ${info.version} foi baixada. Deseja reiniciar e aplicar agora?`
    }).then(({ response }) => {
      if (response === 0) autoUpdater.quitAndInstall();
    });
  });

  autoUpdater.on('error', (err) => {
    console.error('Erro no autoUpdater:', err);
    if (manualUpdateCheck) {
      dialog.showMessageBox(mainWindow, {
        type: 'error',
        title: 'Erro nas atualizações',
        message: 'Ocorreu um erro ao verificar por atualizações.',
        detail: String(err?.message || err),
        buttons: ['OK']
      });
      manualUpdateCheck = false;
    }
  });
}

function checkForUpdates() {
  // logger básico para depuração
  try { autoUpdater.logger = console; } catch { }
  autoUpdater.autoDownload = true;
  autoUpdater.autoInstallOnAppQuit = true;
  autoUpdater.checkForUpdates();
}

// --- Local print server management ---
let _printServerProc = null;

// Checa se a porta está aberta (retorna true se há um servidor respondendo)
function isPortOpen(port, host = '127.0.0.1', timeout = 500) {
  return new Promise((resolve) => {
    try {
      const net = require('net');
      const socket = new net.Socket();
      let done = false;
      socket.setTimeout(timeout);
      socket.on('connect', () => { done = true; socket.destroy(); resolve(true); });
      socket.on('timeout', () => { if (!done) { done = true; socket.destroy(); resolve(false); } });
      socket.on('error', () => { if (!done) { done = true; resolve(false); } });
      socket.connect(port, host);
    } catch (e) { resolve(false); }
  });
}

function getPrintServerDir() {
  if (app.isPackaged) {
    return path.join(process.resourcesPath, 'app.asar.unpacked');
  }
  return __dirname;
}

async function startLocalPrintServer() {
  const serverDir = getPrintServerDir();
  const serverScript = path.join(serverDir, 'server.js');
  const nodeModulesDir = path.join(serverDir, 'node_modules');
  const resourcesNodeModules = path.join(process.resourcesPath, 'node_modules');
  const port = (appConfig && appConfig.printServer && appConfig.printServer.port) ? appConfig.printServer.port : 3420;

  if (!fs.existsSync(serverScript)) {
    console.error('Print-server script não encontrado em', serverScript);
    return;
  }

  try {
    const open = await isPortOpen(port);
    if (open) {
      console.log(`Local print-server já está escutando em http://127.0.0.1:${port} — não irei spawnar uma nova instância.`);
      return;
    }

    // Usar o executável do próprio Electron em modo Node para evitar depender do Node instalado no sistema
    const execPath = process.execPath;
    const env = { ...process.env, ELECTRON_RUN_AS_NODE: '1' };
    const extraNodePaths = [];
    if (fs.existsSync(nodeModulesDir)) extraNodePaths.push(nodeModulesDir);
    if (fs.existsSync(resourcesNodeModules)) extraNodePaths.push(resourcesNodeModules);
    if (extraNodePaths.length) {
      const combinedPaths = extraNodePaths.join(path.delimiter);
      env.NODE_PATH = env.NODE_PATH ? `${combinedPaths}${path.delimiter}${env.NODE_PATH}` : combinedPaths;
    }
    _printServerProc = spawn(execPath, [serverScript], { cwd: serverDir, env, detached: false, stdio: ['ignore', 'pipe', 'pipe'] });

    _printServerProc.on('error', (err) => {
      console.error('Failed to start print-server process:', err);
    });

    // Forward child stdout/stderr to the Electron console instead of writing to server.log
    if (_printServerProc.stdout) {
      _printServerProc.stdout.on('data', (chunk) => {
        try { console.log('[print-server]', String(chunk).trim()); } catch (e) { }
      });
    }
    if (_printServerProc.stderr) {
      _printServerProc.stderr.on('data', (chunk) => {
        try { console.error('[print-server][ERR]', String(chunk).trim()); } catch (e) { }
      });
    }
    _printServerProc.on('close', (code) => {
      console.log('Local print-server exited with code', code);
    });
    console.log('Started local print-server (no file logging), pid=', _printServerProc && _printServerProc.pid);
  } catch (e) {
    console.error('Error starting local print server:', e);
  }
}

function stopLocalPrintServer() {
  try {
    if (_printServerProc && !_printServerProc.killed) {
      _printServerProc.kill();
      console.log('Stopped local print-server pid=', _printServerProc.pid);
    }
  } catch (e) { console.warn('Failed stopping print server', e); }
}

// --- Logout (deslogar) ---
async function logoutUser() {
  console.log('Iniciando logout do usuário...');
  try {
    const ses = session.defaultSession;
    await ses.clearStorageData({
      origin: WP_URL,
      storages: ['cookies', 'localstorage', 'cachestorage', 'indexdb', 'serviceworkers', 'websql']
    });
    console.log('Storage e cookies limpos para a origem:', WP_URL);

    // Parar o print-server local, se ativo
    try { stopLocalPrintServer(); } catch (e) { console.warn('Erro ao parar print-server no logout:', e); }

    // Redirecionar para WP login
    if (mainWindow && !mainWindow.isDestroyed()) {
      mainWindow.loadURL(WP_URL + appConfig.wordpress.loginPath);
      mainWindow.show();
      mainWindow.focus();
    }
  } catch (e) {
    console.error('Erro inesperado no logout:', e);
  }
}



function proceedToMainApp() {
  createWindow();

  if (app.isPackaged) {
    registerAutoUpdaterEvents();
    checkForUpdates();

    // Checar atualizações a cada 10 minutos
    setInterval(() => {
      checkForUpdates();
    }, 1000 * 60 * 10);
  }

  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) {
      createWindow();
    }
  });
}



// --- Inicialização ---
app.whenReady().then(async () => {
  // Atalhos globais para DevTools funcionarem em qualquer janela
  try {
    globalShortcut.register('F12', () => {
      const win = BrowserWindow.getFocusedWindow();
      if (win) win.webContents.toggleDevTools();
    });
    globalShortcut.register('CommandOrControl+Shift+I', () => {
      const win = BrowserWindow.getFocusedWindow();
      if (win) win.webContents.toggleDevTools();
    });
    globalShortcut.register('CommandOrControl+Shift+L', () => {
      openLogWindow();
    });
  } catch (e) {
    console.warn('Falha ao registrar atalhos globais de DevTools:', e);
  }

  // Garantir que o print-server local suba assim que o app estiver pronto
  try {
    await startLocalPrintServer();
  } catch (e) {
    console.warn('Failed to start local print server:', e);
  }

  // Abrir janela de logs apenas se configurado explicitamente
  try {
    if (appConfig && appConfig.openLogsOnStartup) {
      openLogWindow();
    }
  } catch (e) {
    console.warn('Falha ao abrir janela de logs:', e);
  }

  proceedToMainApp();
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

app.on('web-contents-created', (event, contents) => {
  contents.setWindowOpenHandler(({ navigationUrl }) => {
    shell.openExternal(navigationUrl);
    return { action: 'deny' };
  });

  contents.on('will-navigate', (event, navigationUrl) => {
    const parsedUrl = new URL(navigationUrl);
    // Abrir links externos no navegador padrão se não pertencer ao domínio franguxo
    if (!parsedUrl.hostname.includes('franguxo.app.br')) {
      event.preventDefault();
      shell.openExternal(navigationUrl);
    }
  });
});

app.on('before-quit', async (event) => {
  // Garantir que todos os dados de navegação/sessão sejam descarregados para o disco imediatamente
  try {
    await session.defaultSession.flushStorageData();
    console.log('Storage e cookies descarregados no disco com sucesso.');
  } catch (e) {
    console.error('Erro ao forçar gravação no disco:', e);
  }

  // Limpeza se necessário
  try { stopLocalPrintServer(); } catch (e) { }
  try { globalShortcut.unregisterAll(); } catch (e) { }
});
