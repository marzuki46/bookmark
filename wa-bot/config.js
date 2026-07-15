module.exports = {
  httpPort: process.env.BOT_HTTP_PORT || 3001,
  apiUrl: process.env.API_URL || 'https://bookmark.juki.eu.org/api/webhook/wa-finance',
  apiToken: process.env.API_TOKEN || '',
  authDir: process.env.BOT_AUTH_DIR || './auth',
  pairingCode: process.env.PAIRING_CODE || '',
  phoneNumber: process.env.BOT_PHONE || '',
};
