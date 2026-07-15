#!/bin/bash
# Start the Baileys WhatsApp Bot
# Usage: ./start.sh [phone_number]

BOT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$BOT_DIR"

# Install dependencies if needed
if [ ! -d "node_modules" ]; then
  echo "Installing dependencies..."
  npm install
fi

# Set pairing code if phone number provided
if [ -n "$1" ]; then
  export PAIRING_CODE="$1"
fi

# Start with PM2 if available, otherwise directly
if command -v pm2 &> /dev/null; then
  pm2 start index.js --name knowledge-hub-wa --restart-delay 3000
  pm2 save
  echo "✅ Started with PM2. Use 'pm2 logs knowledge-hub-wa' to see logs."
else
  echo "Starting in foreground. Press Ctrl+C to stop."
  node index.js
fi
