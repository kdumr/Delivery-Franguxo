const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');
const iconv = require('iconv-lite');
const fs = require('fs');
const path = require('path');
const os = require('os');
const { execFile } = require('child_process');
// obter versão do app a partir do package.json
let APP_VERSION = '';
try {
  var pkg = require('./package.json');
  APP_VERSION = pkg && pkg.version ? String(pkg.version) : '';
} catch (e) { APP_VERSION = ''; }

let printer = null;
try {
  printer = require('@niick555/node-printer');
  console.log('[print-server] Native module @niick555/node-printer carregado com sucesso.');
} catch (err) {
  printer = null;
  console.warn('[print-server] Aviso: módulo nativo @niick555/node-printer indisponível. Tentarei usar o fallback PowerShell (Out-Printer).', err && err.message ? err.message : err);
}

const app = express();
app.use(cors());
app.use(bodyParser.json({ limit: '1mb' }));

function normalizeText(text) {
  if (!text) return '';
  return String(text)
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^\x00-\x7F]/g, '');
}

function replace8211(text) {
  if (!text) return '';
  return String(text).replace(/&#8211;/g, '-');
}

function canUseNativePrinter() {
  return !!(printer && typeof printer.printDirect === 'function');
}

function printViaPowerShell(normalizedText, targetPrinter) {
  return new Promise((resolve, reject) => {
    try {
      const tmpFile = path.join(os.tmpdir(), `franguxo_print_${Date.now()}_${Math.random().toString(16).slice(2)}.txt`);
      fs.writeFileSync(tmpFile, normalizedText, { encoding: 'utf8' });

      const escapedFile = tmpFile.replace(/'/g, "''");
      const hasPrinter = !!(targetPrinter && String(targetPrinter).trim());
      const escapedPrinter = hasPrinter ? String(targetPrinter).replace(/'/g, "''") : '';
      const command = hasPrinter
        ? `& { $ErrorActionPreference='Stop'; Get-Content -Raw -LiteralPath '${escapedFile}' | Out-Printer -Name '${escapedPrinter}' }`
        : `& { $ErrorActionPreference='Stop'; Get-Content -Raw -LiteralPath '${escapedFile}' | Out-Printer }`;

      execFile('powershell', ['-NoProfile', '-Command', command], { windowsHide: true }, (execErr, stdout, stderr) => {
        if (execErr) {
          console.error('[print-server] PowerShell Out-Printer fallback falhou', execErr, stderr);
          return reject(execErr);
        }
        console.log('[print-server] PowerShell Out-Printer fallback enviado (file):', tmpFile);
        resolve({ filePath: tmpFile, stdout, stderr });
      });
    } catch (err) {
      reject(err);
    }
  });
}

function centerText(text, width = 32) {
  const s = String(text || '');
  const padding = Math.max(0, width - s.length);
  const leftPad = Math.floor(padding / 2);
  const rightPad = padding - leftPad;
  return ' '.repeat(leftPad) + s + ' '.repeat(rightPad);
}

function parseMoney(v) {
  if (v === undefined || v === null) return 0;
  if (typeof v === 'number') return v;
  var s = String(v).trim();
  if (s === '') return 0;
  // accept '12,34' or '12.34' or 'R$ 12,34'
  s = s.replace(/[^0-9,.-]/g, '');
  // if contains comma and not dot, replace comma with dot
  if (s.indexOf(',') !== -1 && s.indexOf('.') === -1) s = s.replace(',', '.');
  s = s.replace(',', ''); // remove thousands if any
  var n = parseFloat(s);
  return isNaN(n) ? 0 : n;
}

function formatMoney(n) {
  return Number(n || 0).toFixed(2).replace('.', ',');
}

function formatReceipt(o, width = 32) {
  // Builds the receipt exactly in the user's requested layout
  const lines = [];
  function push(line) { lines.push(line); }
  function pushSep() { push('--------------------------------'); }

  // *CARDÁPIO PRÓPRIO*
  push(centerText('* CARDÁPIO PRÓPRIO *', width));
  // Nome da loja
  if (o.store_name) push(centerText(o.store_name, width));
  pushSep();

  // PEDIDO: {numero pedido}
  push(centerText('PEDIDO: #' + (o.id || '(sem id)'), width));
  pushSep();

  // Data: {data do pedido} {hora do pedido}
  var dateStr = '';
  if (o.date) {
    try {
      // Parse DD-MM-YYYY HH:MM format
      var parts = String(o.date).split(' ');
      if (parts.length === 2) {
        var datePart = parts[0].split('-');
        var timePart = parts[1].split(':');
        if (datePart.length === 3 && timePart.length >= 2) {
          // Create Date object: year, month-1, day, hour, minute
          var d = new Date(parseInt(datePart[2]), parseInt(datePart[1]) - 1, parseInt(datePart[0]), parseInt(timePart[0]), parseInt(timePart[1]));
          if (!isNaN(d.getTime())) {
            dateStr = d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
          } else {
            dateStr = String(o.date);
          }
        } else {
          dateStr = String(o.date);
        }
      } else {
        // Fallback to standard Date parsing
        var d = new Date(o.date);
        if (!isNaN(d.getTime())) {
          dateStr = d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        } else {
          dateStr = String(o.date);
        }
      }
    } catch (e) {
      dateStr = String(o.date);
    }
  }
  push('Data: ' + dateStr);

  // Localizador: {localizador do pedido}
  var locator = o.localizador || o.localizador_pedido || o.tracking || o.hash || o.order_key || '';
  if (locator) {
    // Format locator with spaces every 4 characters
    var formattedLocator = String(locator).replace(/\s/g, ''); // remove existing spaces
    var parts = [];
    for (var i = 0; i < formattedLocator.length; i += 4) {
      parts.push(formattedLocator.slice(i, i + 4));
    }
    push('Localizador: ' + parts.join(' '));
  }

  // {Nome cliente}
  push('')
  if (o.customer_name) push(o.customer_name);
  push('')

  // Tel. {telefone registrado no post do pedido}
  if (o.customer_phone) push('Tel. ' + o.customer_phone);

  // Endereço: {Rua do cliente, N° cliente}
  if (o.address) {
    var addr = o.address + (o.address_number ? ', ' + o.address_number : '');
    push('Endereço: ' + addr);
    var comp = o.address_comp || o.address_complement || o.complemento || '';
    if (comp && String(comp).trim() !== '') {
      push('Comp: ' + comp);
    }
  }

  // Bairro: {bairro cliente}
  if (o.neighborhood) push('Bairro: ' + o.neighborhood + (o.real_neighborhood ? ' ***' : ''));

  // Ref: {Ponto de referência se tiver} (Só exiba ref se tiver ponto de referência)
  if (o.reference && String(o.reference).trim() !== '') push('Ref: ' + o.reference);

  // Cidade: {cidade cliente}, {Estado cliente}
  if (o.city || o.state) push('Cidade: ' + (o.city || '') + (o.city && o.state ? ', ' : '') + (o.state || ''));

  // CEP: {cep cliente}
  if (o.zipcode) push('CEP: ' + o.zipcode);

  pushSep();

  // ITENS DO PEDIDO (quantidade)
  var totalItems = 0;
  if (o.items && o.items.length) totalItems = o.items.reduce(function (acc, it) { var q = parseInt(it.quantity || it.qty || '1') || 0; return acc + q; }, 0);
  push('ITENS DO PEDIDO (' + totalItems + ')');

  // helper for aligned price at right with name wrapping
  function formatItemLines(name, price) {
    var p = 'R$ ' + formatMoney(price);
    name = String(name || '');
    var maxNameLen = 22; // reserve 10 chars for values
    var valueSpace = 10;
    var lines = [];
    if (name.length <= maxNameLen) {
      var space = Math.max(1, width - name.length - p.length);
      lines.push(name + ' '.repeat(space) + p);
    } else {
      // first line with price
      var firstPart = name.substring(0, maxNameLen);
      var space = Math.max(1, width - maxNameLen - p.length);
      lines.push(firstPart + ' '.repeat(space) + p);
      // subsequent lines
      var remaining = name.substring(maxNameLen);
      var indent = name.startsWith('  ') ? '  ' : ''; // for extras, keep indent
      while (remaining.length > 0) {
        var partLen = maxNameLen - indent.length;
        var part = remaining.substring(0, partLen);
        var line = indent + part + ' '.repeat(valueSpace);
        lines.push(line);
        remaining = remaining.substring(part.length);
      }
    }
    return lines;
  }

  if (o.items && o.items.length) {
    o.items.forEach(function (it) {
      var rawProductName = String(it.product_name || it.name || '').trim();
      var fixedProductName = replace8211(rawProductName);

      // Tenta capturar quantidade se o nome começar com padrões como "1 x ", "1x ", etc.
      var match = fixedProductName.match(/^(\d+)\s*x\s*(.*)$/i) || fixedProductName.match(/^\((\d+)x\)\s*(.*)$/i);

      var qty = match ? match[1] : (it.quantity || it.qty || '1');
      var cleanName = match ? match[2] : fixedProductName;

      var lineName = '(' + qty + 'x) ' + cleanName;
      var unitPrice = parseMoney(it.product_price || it.price || it.total || 0);
      // if item has total price field, use it; else unitPrice * qty
      var itemTotal = it.total ? parseMoney(it.total) : (unitPrice * qty);
      formatItemLines(lineName, itemTotal).forEach(push);
      // extras if any
      if (it.extras && it.extras.groups && it.extras.groups.length > 0) {
        push('');
        it.extras.groups.forEach(group => {
          // Nome do grupo: exibir se houver pelo menos 1 extra no grupo
          if (group.group && group.group.trim() !== '' && group.items && group.items.length > 0) {
            push('    ' + group.group.trim());
          }
          // Itens do grupo (se houver)
          if (group.items && group.items.length > 0) {
            group.items.forEach(extraItem => {
              if (parseInt(extraItem.quantity) > 0) {
                const extraTotal = parseFloat(extraItem.price) * parseInt(extraItem.quantity);
                let extraNome = extraItem.quantity + 'x ' + extraItem.name;
                let extraPreco = 'R$' + formatMoney(extraTotal);
                let extraEspacos = width - 4 - extraNome.length - extraPreco.length; // 4 = indent
                push('    ' + extraNome + ' '.repeat(Math.max(1, extraEspacos)) + extraPreco);
              }
            });
          }
        });
      }
      // No notes as per user spec
    });
  }
  push('')
  pushSep();

  // Payment status messaging
  var ps = (o.payment_status || o.order_payment_status || '').toString().toLowerCase();
  if (ps === 'paid') {
    push(centerText('*Pago realizado online*', width));
  } else if (ps === 'waiting') {
    push(centerText('***PAGAMENTO NA ENTREGA***', width));
  } else if (ps === 'failed') {
    push(centerText('*Pagamento falhou*', width));
  }
  // Exibir método de pagamento se disponível (campo order_payment_method)
  try {
    var payMethod = o.order_payment_method || o.payment_method || '';
    var cobrar = 0;
    if (payMethod) {
      var pmLabel = String(payMethod);
      // Mapeamento simples para nomes amigáveis quando possível
      var pmMap = { 'CRD': 'CRÉDITO', 'DEB': 'DÉBITO', 'VRF': 'VALE-REFEIÇÃO', 'DIN': 'DINHEIRO', 'PIX': 'PIX' };
      if (pmMap[pmLabel]) pmLabel = pmMap[pmLabel];
      push(centerText(pmLabel, width));
    }
  } catch (e) { /* noop */ }
  // Se o método de pagamento for Dinheiro (DIN), mostrar valores relacionados ao troco
  try {
    var payMethodForTroco = (o.order_payment_method || o.payment_method || '').toString().toUpperCase();
    if (payMethodForTroco === 'DIN') {
      var orderChangeVal = parseMoney(o.order_change || o.payment_change || 0);
      if (orderChangeVal > 0) {
        // calcular devolva como order_change menos o valor total do pedido
        var orderTotalForTroco = parseMoney(o.total || o.order_total || 0);
        var devolver = orderChangeVal - orderTotalForTroco;
        push(centerText('Receber: R$ ' + formatMoney(orderChangeVal), width));
        push(centerText('Devolva: R$ ' + formatMoney(devolver), width));
      }
    }
  } catch (e) { /* noop */ }

  pushSep();

  // Totals
  var subtotal = parseMoney(o.subtotal || o.sub_total || 0);
  var delivery = parseMoney(o.delivery_price || o.shipping || o.shipping_total || 0);
  var couponDiscount = parseMoney(o.coupon_discount || o.coupon_discount_value || 0);
  var total = parseMoney(o.total || o.order_total || (subtotal + delivery - couponDiscount));

  push('Valor total do'.padEnd(24) + 'R$ ' + formatMoney(subtotal));
  push('pedido:');
  push('Taxa de entrega:'.padEnd(24) + 'R$ ' + formatMoney(delivery));
  if (o.coupon_name) { push('Cupom:'.padEnd(22) + (o.coupon_name || '')); }
  if (couponDiscount > 0) { push('Desconto cupom'.padEnd(22) + 'R$ ' + formatMoney(couponDiscount)); }
  pushSep();

  // Cobrar do cliente logic

  if (ps === 'paid') cobrar = 0;
  else cobrar = total;
  push('Cobrar do cliente:'.padEnd(24) + 'R$ ' + formatMoney(cobrar));
  pushSep();
  // Versão do aplicativo (no final do recibo)
  try {
    if (APP_VERSION && APP_VERSION.length) push(centerText('Versão: ' + APP_VERSION, width));
  } catch (e) { }
  push('')
  push('')
  push('')


  return lines.join('\n') + '\n';
}

app.post('/print', (req, res) => {
  try {
    const payload = req.body || {};
    console.log('[print-server] Received print request:', { hasOrderData: !!payload.orderData, hasText: !!payload.text, printer: payload.printer });
    // se vier orderData (mesmo formato do main.js), construir recibo detalhado
    let text = payload.text || '';
    if (payload.orderData) {
      text = formatReceipt(payload.orderData, 32);
    }
    const normalized = normalizeText(text);
    console.log('DEBUG: normalized length=', (normalized || '').length, 'preview=', JSON.stringify((normalized || '').slice(0, 200)));


    const targetPrinter = payload.printer || process.env.PRINT_PRINTER || undefined;

    // detecta impressora POS (nome contém pos/star/epson) ou flag escpos no payload
    const isPosPrinter = (targetPrinter && /pos|star|epson|thermal|tm-/i.test(String(targetPrinter))) || payload.escpos === true;

    // preparar dados: para POS enviamos Buffer com comandos ESC/POS (init + texto + corte)
    let dataToSend = normalized;
    if (isPosPrinter) {
      try {
        // init escpos
        const init = Buffer.from([0x1B, 0x40]); // ESC @
        // tentar codificar em CP850 (muitas térmicas usam), senão CP437
        let textBuf;
        try {
          textBuf = iconv.encode(normalized + '\n\n', 'cp850');
        } catch (e) {
          console.warn('CP850 encode failed, trying CP437', e);
          textBuf = iconv.encode(normalized + '\n\n', 'cp437');
        }
        const cut = Buffer.from([0x1D, 0x56, 0x00]); // GS V 0 (full cut)
        dataToSend = Buffer.concat([init, textBuf, cut]);
        console.log('Using ESC/POS mode for printer:', targetPrinter, 'bufferLength:', dataToSend.length);
      } catch (e) {
        console.warn('Failed to build ESC/POS buffer, falling back to text', e);
        dataToSend = normalized;
      }
    }

    const usesNativePrinter = canUseNativePrinter();

    const respondWithPowerShellFallback = (primaryErr, secondaryErr) => {
      if (primaryErr || secondaryErr) {
        console.warn('Attempting PowerShell fallback. Reasons:', {
          primary: primaryErr ? String(primaryErr) : null,
          secondary: secondaryErr ? String(secondaryErr) : null
        });
      }
      return printViaPowerShell(normalized, targetPrinter).then((result) => {
        return res.json({ ok: true, fallback: 'powershell-out-printer', file: result.filePath });
      }).catch((execErr) => {
        console.error('PowerShell fallback failed', execErr);
        return res.status(500).json({ ok: false, error: [primaryErr, secondaryErr, execErr].filter(Boolean).map(String).join(' | ') });
      });
    };

    if (!usesNativePrinter) {
      console.warn('Módulo @niick555/node-printer indisponível. Usando somente fallback PowerShell.');
      return respondWithPowerShellFallback();
    }

    function doPrint(type, data, cb) {
      printer.printDirect({
        data: data,
        type: type,
        printer: targetPrinter,
        success: function (jobID) { cb(null, jobID); },
        error: function (err) { cb(err); }
      });
    }

    // Primeiro tenta RAW (com nome da impressora se enviado), senão tenta TEXT
    doPrint('RAW', dataToSend, function (err, jobID) {
      if (!err) {
        console.log('Impressão enviada (RAW), JobID:', jobID, 'printer:', targetPrinter);
        return res.json({ ok: true, jobID, printer: targetPrinter, type: 'RAW' });
      }
      console.warn('RAW print failed, trying TEXT fallback', err);
      // se dataToSend é Buffer e falhar, tentar enviar texto simples
      const fallbackData = Buffer.isBuffer(dataToSend) ? Buffer.from(normalized + '\n\n', 'utf8') : normalized;
      doPrint('TEXT', fallbackData, function (err2, jobID2) {
        if (!err2) {
          console.log('Impressão enviada (TEXT), JobID:', jobID2, 'printer:', targetPrinter);
          return res.json({ ok: true, jobID: jobID2, printer: targetPrinter, type: 'TEXT' });
        }
        console.error('Both RAW and TEXT printing failed', err, err2);
        return respondWithPowerShellFallback(err, err2);
      });
    });
  } catch (err) {
    console.error('Erro no endpoint /print', err);
    res.status(500).json({ ok: false, error: String(err) });
  }
});

const PORT = process.env.PORT || 3420;
app.listen(PORT, () => {
  console.log(`Franguxo local print server listening on http://localhost:${PORT}`);
});

// Add global handlers to capture unexpected exits and rejections for debugging
process.on('uncaughtException', (err) => {
  console.error('UNCAUGHT EXCEPTION:', err && err.stack ? err.stack : String(err));
  try { fs.appendFileSync('server-error.log', `[${new Date().toISOString()}] UNCAUGHT EXCEPTION: ${err && err.stack ? err.stack : String(err)}\n`); } catch (e) { }
});

process.on('unhandledRejection', (reason, p) => {
  console.error('UNHANDLED REJECTION at Promise', p, 'reason:', reason);
  try { fs.appendFileSync('server-error.log', `[${new Date().toISOString()}] UNHANDLED REJECTION: ${String(reason)}\n`); } catch (e) { }
});

process.on('exit', (code) => {
  console.log('PROCESS EXIT with code', code);
  try { fs.appendFileSync('server-error.log', `[${new Date().toISOString()}] PROCESS EXIT code=${code}\n`); } catch (e) { }
});

// Heartbeat so we can see the process is alive in logs
setInterval(() => {
  try { console.log('HEARTBEAT', new Date().toISOString()); } catch (e) { }
}, 30000);

// Diagnostic: list printers
app.get('/printers', (req, res) => {
  try {
    if (!canUseNativePrinter()) {
      console.warn('printer.getPrinters() indisponível: módulo nativo não carregado.');
      return res.json({ ok: false, printers: [], warning: 'native printer module unavailable' });
    }
    const list = printer.getPrinters();
    return res.json({ ok: true, printers: list });
  } catch (e) {
    console.warn('printer.getPrinters() failed', e);
    return res.status(500).json({ ok: false, error: String(e) });
  }
});

// Diagnostic: deterministic test-print route for debugging encoding and buffer
app.post('/test-print', (req, res) => {
  try {
    const payload = req.body || {};
    const sample = payload.text || 'Teste Franguxo - Linha 1\nLinha 2 \u00E7 \n1234567890\n';
    const printerName = payload.printer || process.env.PRINT_PRINTER || undefined;
    const forceEscPos = payload.escpos === true || (printerName && /pos|star|epson|thermal|tm-/i.test(String(printerName)));

    if (!canUseNativePrinter()) {
      console.warn('TEST-PRINT: módulo nativo indisponível. Utilize /print-ps ou configure o módulo.');
      return res.status(503).json({ ok: false, error: 'native printer module unavailable; PowerShell fallback only' });
    }

    const normalized = normalizeText(sample + '\n---END---\n');
    console.log('TEST-PRINT: normalized length=', normalized.length, 'preview=', JSON.stringify(normalized.slice(0, 200)));

    // build buffer if escpos
    let dataToSend = normalized;
    let usedEncoding = 'utf8';
    let bufferHex = '';
    if (forceEscPos) {
      const init = Buffer.from([0x1B, 0x40]);
      let textBuf;
      try {
        textBuf = iconv.encode(normalized, 'cp850');
        usedEncoding = 'cp850';
      } catch (e) {
        textBuf = iconv.encode(normalized, 'cp437');
        usedEncoding = 'cp437';
      }
      const cut = Buffer.from([0x1D, 0x56, 0x00]);
      const full = Buffer.concat([init, textBuf, cut]);
      dataToSend = full;
      bufferHex = full.toString('hex');
      console.log('TEST-PRINT: built ESC/POS buffer len=', full.length, 'encoding=', usedEncoding);
    } else {
      const b = Buffer.from(normalized, 'utf8');
      dataToSend = b;
      bufferHex = b.toString('hex');
      usedEncoding = 'utf8';
      console.log('TEST-PRINT: using plain text utf8 buffer len=', b.length);
    }

    console.log('TEST-PRINT: bufferHex (first 200 chars)=', bufferHex.slice(0, 200));

    // attempt RAW print
    printer.printDirect({
      data: dataToSend, type: Buffer.isBuffer(dataToSend) ? 'RAW' : 'RAW', printer: printerName, success: function (jobID) {
        console.log('TEST-PRINT: RAW print success, jobID=', jobID, 'printer=', printerName);
        return res.json({ ok: true, jobID, printer: printerName, encoding: usedEncoding, bufferLength: dataToSend.length, bufferHex: bufferHex.slice(0, 1000) });
      }, error: function (err) {
        console.error('TEST-PRINT: RAW print error', err);
        return res.status(500).json({ ok: false, error: String(err), encoding: usedEncoding, bufferLength: dataToSend.length, bufferHex: bufferHex.slice(0, 1000) });
      }
    });
  } catch (err) {
    console.error('TEST-PRINT failed', err);
    return res.status(500).json({ ok: false, error: String(err) });
  }
});

// Force sending as TEXT (not RAW) to test driver text handling
app.post('/print-text', (req, res) => {
  try {
    const payload = req.body || {};
    const sample = payload.text || 'Teste TEXT Franguxo\nLinha 2\n';
    const printerName = payload.printer || process.env.PRINT_PRINTER || undefined;
    const normalized = normalizeText(sample + '\n--END--\n');
    const buf = Buffer.from(normalized, 'utf8');
    if (!canUseNativePrinter()) {
      console.warn('PRINT-TEXT: módulo nativo indisponível. Utilize /print-ps ou configure o módulo.');
      return res.status(503).json({ ok: false, error: 'native printer module unavailable; PowerShell fallback only' });
    }
    console.log('PRINT-TEXT: sending TEXT length=', buf.length, 'printer=', printerName);
    printer.printDirect({
      data: buf, type: 'TEXT', printer: printerName, success: function (jobID) {
        console.log('PRINT-TEXT: success jobID=', jobID);
        return res.json({ ok: true, jobID, printer: printerName, type: 'TEXT' });
      }, error: function (err) {
        console.error('PRINT-TEXT: error', err);
        return res.status(500).json({ ok: false, error: String(err) });
      }
    });
  } catch (e) { console.error('PRINT-TEXT failed', e); return res.status(500).json({ ok: false, error: String(e) }); }
});

// Force PowerShell Out-Printer fallback (write file and use Out-Printer)
app.post('/print-ps', (req, res) => {
  try {
    const payload = req.body || {};
    const sample = payload.text || 'Teste PS Franguxo\nLinha 2\n';
    const printerName = payload.printer || process.env.PRINT_PRINTER || undefined;
    const normalized = normalizeText(sample + '\n--END--\n');
    console.log('PRINT-PS: fallback solicitado para impressora', printerName || '<default>');
    printViaPowerShell(normalized, printerName).then((result) => {
      return res.json({ ok: true, fallback: 'powershell-out-printer', file: result.filePath });
    }).catch((err) => {
      console.error('PRINT-PS: execErr', err);
      return res.status(500).json({ ok: false, error: String(err) });
    });
  } catch (e) { console.error('PRINT-PS failed', e); return res.status(500).json({ ok: false, error: String(e) }); }
});
