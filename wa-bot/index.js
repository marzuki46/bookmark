const { makeWASocket, useMultiFileAuthState, DisconnectReason, fetchLatestBaileysVersion } = require('@whiskeysockets/baileys');
const express = require('express');
const pino = require('pino');
const qrcode = require('qrcode-terminal');
const fs = require('fs');
const path = require('path');
const config = require('./config');

let sock = null;
let connected = false;
let lastQR = null;
let lastPairingCode = null;

// ─── Logger ───
const logger = pino({
  level: 'info',
  transport: { target: 'pino-pretty', options: { colorize: true } },
});

// ─── Auth ───
function ensureAuthDir() {
  if (!fs.existsSync(config.authDir)) {
    fs.mkdirSync(config.authDir, { recursive: true });
  }
}

async function startBot() {
  ensureAuthDir();
  const { state, saveCreds } = await useMultiFileAuthState(config.authDir);
  const { version } = await fetchLatestBaileysVersion();

  sock = makeWASocket({
    version,
    logger: pino({ level: 'silent' }),
    printQRInTerminal: false,
    auth: state,
    browser: ['Knowledge Hub', 'Chrome', '3.0'],
    syncFullHistory: false,
    markOnlineOnConnect: true,
  });

  // ─── QR / Pairing ───
  sock.ev.on('connection.update', async (update) => {
    const { connection, lastDisconnect, qr } = update;

    if (qr) {
      lastQR = qr;
      lastPairingCode = null;
      qrcode.generate(qr, { small: true });
      logger.info('📱 Scan QR code above with WhatsApp');
      logger.info('Or set PAIRING_CODE=your_phone in .env for pairing code');
    }

    if (connection === 'open') {
      connected = true;
      logger.info('✅ WhatsApp connected!');
      logger.info('   Number: ' + sock.user?.id?.split(':')[0]);
    }

    if (connection === 'close') {
      connected = false;
      const statusCode = lastDisconnect?.error?.output?.statusCode || lastDisconnect?.error?.httpCode || 500;
      logger.warn('❌ Disconnected. Reason: ' + (statusCode || 'unknown'));

      if (statusCode === 401 || statusCode === 403) {
        logger.error('Session expired. Delete auth/ folder and restart.');
        process.exit(1);
      }

      setTimeout(startBot, 3000);
    }
  });

  // ─── Pairing Code ───
  if (config.pairingCode) {
    setTimeout(async () => {
      try {
        const code = await sock.requestPairingCode(config.pairingCode);
        lastPairingCode = code;
        logger.info(`\n📟 Pairing code: ${code}\n`);
        logger.info('Open WhatsApp → Linked Devices → Link with phone number');
        logger.info(`Enter this code: ${code}\n`);
      } catch (e) {
        logger.error('Pairing code error: ' + e.message);
      }
    }, 2000);
  }

  // ─── Save Credentials ───
  sock.ev.on('creds.update', saveCreds);

  // ─── Messages ───
  sock.ev.on('messages.upsert', async ({ messages }) => {
    for (const msg of messages) {
      if (msg.key.fromMe) continue;

      const text = msg.message?.conversation
        || msg.message?.extendedTextMessage?.text
        || '';
      const sender = msg.key.remoteJid;
      const pushName = msg.pushName || '';

      if (!text || !sender) continue;
      logger.info(`📩 From ${pushName} (${sender}): ${text}`);

      try {
        const resp = await fetch(config.apiUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            sender: sender.split('@')[0],
            message: text,
            id: msg.key.id,
            pushName,
            source: 'baileys',
          }),
        });

        if (resp.ok) {
          const data = await resp.json();
          logger.info(`✅ Forwarded to Laravel: ${data.status}`);

          if (data.reply) {
            await sock.sendMessage(sender, { text: data.reply });
          } else if (data.message) {
            await sock.sendMessage(sender, { text: data.message });
          }
        } else {
          logger.warn(`⚠️ Laravel responded ${resp.status}`);
        }
      } catch (err) {
        logger.error('❌ Failed to forward: ' + err.message);
      }
    }
  });
}

// ─── HTTP Server (for Laravel to send messages) ───
const app = express();
app.use(express.json());

app.get('/health', (req, res) => {
  res.json({ status: connected ? 'connected' : 'disconnected', qr: lastQR, pairingCode: lastPairingCode });
});

app.get('/qr', (req, res) => {
  if (connected) return res.json({ status: 'connected' });
  if (lastQR) return res.json({ status: 'waiting_qr', qr: lastQR });
  if (lastPairingCode) return res.json({ status: 'waiting_pairing', code: lastPairingCode });
  res.json({ status: 'initializing' });
});

app.post('/send', async (req, res) => {
  const { to, message } = req.body;
  if (!to || !message) return res.status(400).json({ error: 'Missing to or message' });
  if (!connected) return res.status(503).json({ error: 'Not connected' });

  try {
    const jid = to.includes('@s.whatsapp.net') ? to : `${to}@s.whatsapp.net`;
    await sock.sendMessage(jid, { text: message });
    res.json({ status: true, sent: true });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.listen(config.httpPort, () => {
  logger.info(`🌐 HTTP server on port ${config.httpPort}`);
});

// ─── Start ───
startBot();
