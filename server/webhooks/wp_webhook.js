import express from 'express';

const app = express();
const PORT = process.env.PORT || 3000;

// Your verify token - set this to whatever you want, then use the same value in Meta dashboard
const VERIFY_TOKEN = process.env.WP_VERIFY_TOKEN || 'myself_testing';

app.use(express.json());

// GET - Webhook verification (Meta sends this to verify your endpoint)
app.get('/webhook', (req, res) => {
  const mode = req.query['hub.mode'];
  const token = req.query['hub.verify_token'];
  const challenge = req.query['hub.challenge'];

  console.log('Verification request received:', { mode, token, challenge });

  if (mode === 'subscribe' && token === VERIFY_TOKEN) {
    console.log('✅ Webhook verified successfully');
    return res.status(200).send(challenge);
  }

  console.log('❌ Webhook verification failed');
  return res.sendStatus(403);
});

// POST - Receive incoming messages
app.post('/webhook', (req, res) => {
  console.log('📩 Incoming webhook:', JSON.stringify(req.body, null, 2));

  // Always respond 200 quickly to acknowledge receipt
  res.sendStatus(200);

  // TODO: Process the message and create expense
});

app.listen(PORT, () => {
  console.log(`🚀 WhatsApp webhook server running on port ${PORT}`);
  console.log(`   Verify token: ${VERIFY_TOKEN}`);
  console.log(`   Webhook URL: http://localhost:${PORT}/webhook`);
});
