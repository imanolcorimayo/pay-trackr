import 'dotenv/config';
import express from 'express';
import admin from 'firebase-admin';

// ============================================
// Configuration
// ============================================
const app = express();
const PORT = process.env.PORT || 4000;
const VERIFY_TOKEN = process.env.WP_VERIFY_TOKEN || 'myself_testing';
const WP_PHONE_NUMBER_ID = process.env.IDENTIFIER_WP_NUMBER;
const WP_ACCESS_TOKEN = process.env.ACCESS_TOKEN_WP_BUSINESS;

// ============================================
// Firebase Admin Initialization
// ============================================
// Initialize with service account if available, otherwise use default
if (!admin.apps.length) {
  const firebaseConfig = {
    projectId: process.env.FIREBASE_PROJECT_ID || 'pay-tracker-7a5a6',
  };

  // If service account JSON is provided as env var (base64 encoded)
  if (process.env.FIREBASE_SERVICE_ACCOUNT) {
    const serviceAccount = JSON.parse(
      Buffer.from(process.env.FIREBASE_SERVICE_ACCOUNT, 'base64').toString()
    );
    firebaseConfig.credential = admin.credential.cert(serviceAccount);
  }

  admin.initializeApp(firebaseConfig);
  console.log('Firebase initialized successfully');
}

const db = admin.firestore();
console.log('Firestore connection established');

// ============================================
// Collections
// ============================================
const COLLECTIONS = {
  WHATSAPP_LINKS: 'whatsappLinks', // Single collection for both pending codes and linked accounts
  PAYMENTS: 'payment2',
  CATEGORIES: 'categories'
};

// ============================================
// Common categories for help message
// ============================================
const COMMON_CATEGORIES = ['supermercado', 'salidas', 'transporte', 'servicios', 'suscripciones'];

// ============================================
// Middleware
// ============================================
app.use(express.json());

// ============================================
// Routes
// ============================================

// GET - Webhook verification
app.get('/webhook', (req, res) => {
  const mode = req.query['hub.mode'];
  const token = req.query['hub.verify_token'];
  const challenge = req.query['hub.challenge'];

  console.log('Verification request received:', { mode, token, challenge });

  if (mode === 'subscribe' && token === VERIFY_TOKEN) {
    console.log('Webhook verified successfully');
    return res.status(200).send(challenge);
  }

  console.log('Webhook verification failed');
  return res.sendStatus(403);
});

// POST - Receive incoming messages
app.post('/webhook', async (req, res) => {
  console.log('Incoming webhook:', JSON.stringify(req.body, null, 2));

  // Always respond 200 quickly to acknowledge receipt
  res.sendStatus(200);

  try {
    const body = req.body;

    if (body.object !== 'whatsapp_business_account') {
      return;
    }

    const entry = body.entry?.[0];
    const changes = entry?.changes?.[0];
    const value = changes?.value;

    if (!value?.messages?.[0]) {
      return;
    }

    const message = value.messages[0];
    const from = message.from; // Phone number
    const messageText = message.text?.body || '';
    const contactName = value.contacts?.[0]?.profile?.name || 'Usuario';

    console.log(`Message from ${from} (${contactName}): ${messageText}`);

    // Process the message
    await processMessage(from, messageText, contactName);
  } catch (error) {
    console.error('Error processing webhook:', error);
  }
});

// ============================================
// Message Processing
// ============================================

async function processMessage(phoneNumber, text, contactName) {
  const normalizedText = text.trim().toLowerCase();

  // Check for VINCULAR command
  if (normalizedText.startsWith('vincular ')) {
    const code = text.trim().split(' ')[1]?.toUpperCase();
    await handleLinkCommand(phoneNumber, code, contactName);
    return;
  }

  // Check for DESVINCULAR command
  if (normalizedText === 'desvincular') {
    await handleUnlinkCommand(phoneNumber);
    return;
  }

  // Check for AYUDA command
  if (normalizedText === 'ayuda' || normalizedText === 'help') {
    await sendHelpMessage(phoneNumber);
    return;
  }

  // Check for CATEGORIAS command
  if (normalizedText === 'categorias') {
    await handleCategoriesCommand(phoneNumber);
    return;
  }

  // Try to parse as expense
  await handleExpenseMessage(phoneNumber, text);
}

// ============================================
// Command Handlers
// ============================================

async function handleLinkCommand(phoneNumber, code, contactName) {
  if (!code) {
    await sendWhatsAppMessage(
      phoneNumber,
      'Formato incorrecto. Usa: VINCULAR <codigo>\n\nEjemplo: VINCULAR ABC123'
    );
    return;
  }

  try {
    // Look for the code in whatsapp_links (pending codes use code as doc ID)
    const codeDoc = await db.collection(COLLECTIONS.WHATSAPP_LINKS).doc(code).get();

    if (!codeDoc.exists) {
      await sendWhatsAppMessage(
        phoneNumber,
        'Codigo no encontrado o expirado. Genera un nuevo codigo desde la app.'
      );
      return;
    }

    const codeData = codeDoc.data();

    // Check if this is a pending code (not an already linked account)
    if (codeData.status !== 'pending') {
      await sendWhatsAppMessage(
        phoneNumber,
        'Codigo no valido. Genera un nuevo codigo desde la app.'
      );
      return;
    }

    // Check if expired (10 minutes)
    const createdAt = codeData.createdAt?.toDate() || new Date(0);
    const now = new Date();
    const diffMinutes = (now - createdAt) / (1000 * 60);

    if (diffMinutes > 10) {
      await db.collection(COLLECTIONS.WHATSAPP_LINKS).doc(code).delete();
      await sendWhatsAppMessage(
        phoneNumber,
        'El codigo ha expirado. Genera un nuevo codigo desde la app.'
      );
      return;
    }

    // Delete the pending code document
    await db.collection(COLLECTIONS.WHATSAPP_LINKS).doc(code).delete();

    // Create the linked account document (phone number as doc ID)
    await db.collection(COLLECTIONS.WHATSAPP_LINKS).doc(phoneNumber).set({
      status: 'linked',
      userId: codeData.userId,
      phoneNumber: phoneNumber,
      contactName: contactName,
      linkedAt: admin.firestore.FieldValue.serverTimestamp()
    });

    await sendWhatsAppMessage(
      phoneNumber,
      `Cuenta vinculada exitosamente!\n\nAhora podes registrar gastos enviando mensajes como:\n- "$500 Super #supermercado"\n- "$1500 Cena #salidas d:Cumple de Juan"\n\nEscribi "ayuda" para mas informacion.`
    );
  } catch (error) {
    console.error('Error linking account:', error);
    await sendWhatsAppMessage(
      phoneNumber,
      'Error al vincular la cuenta. Intenta nuevamente.'
    );
  }
}

async function handleUnlinkCommand(phoneNumber) {
  try {
    const linkDoc = await db.collection(COLLECTIONS.WHATSAPP_LINKS).doc(phoneNumber).get();

    if (!linkDoc.exists || linkDoc.data()?.status !== 'linked') {
      await sendWhatsAppMessage(
        phoneNumber,
        'Este numero no esta vinculado a ninguna cuenta.'
      );
      return;
    }

    await db.collection(COLLECTIONS.WHATSAPP_LINKS).doc(phoneNumber).delete();

    await sendWhatsAppMessage(
      phoneNumber,
      'Cuenta desvinculada exitosamente. Ya no se registraran gastos desde este numero.'
    );
  } catch (error) {
    console.error('Error unlinking account:', error);
    await sendWhatsAppMessage(
      phoneNumber,
      'Error al desvincular la cuenta. Intenta nuevamente.'
    );
  }
}

async function sendHelpMessage(phoneNumber) {
  const commonCats = COMMON_CATEGORIES.map(c => `#${c}`).join(', ');
  const helpText = `*PayTrackr - Ayuda*

*Registrar un gasto:*
$<monto> <titulo> #<categoria> d:<descripcion>

Ejemplos:
- "$500 Super #supermercado"
- "$1500 Cena con amigos #salidas d:Cumple de Juan"
- "$2000 Uber" (sin categoria = Otros)

*Categorias frecuentes:*
${commonCats}

*Comandos:*
- VINCULAR <codigo> - Vincular este numero
- DESVINCULAR - Desvincular este numero
- CATEGORIAS - Ver todas las categorias
- AYUDA - Ver este mensaje`;

  await sendWhatsAppMessage(phoneNumber, helpText);
}

async function handleCategoriesCommand(phoneNumber) {
  // Check if phone is linked
  const linkDoc = await db.collection(COLLECTIONS.WHATSAPP_LINKS).doc(phoneNumber).get();

  if (!linkDoc.exists || linkDoc.data()?.status !== 'linked') {
    await sendWhatsAppMessage(
      phoneNumber,
      'Este numero no esta vinculado a ninguna cuenta de PayTrackr.'
    );
    return;
  }

  const linkData = linkDoc.data();
  const userId = linkData.userId;

  try {
    const categoriesSnapshot = await db
      .collection(COLLECTIONS.CATEGORIES)
      .where('userId', '==', userId)
      .get();

    if (categoriesSnapshot.empty) {
      await sendWhatsAppMessage(phoneNumber, 'No tenes categorias configuradas en tu cuenta.');
      return;
    }

    const categoryNames = categoriesSnapshot.docs
      .map(doc => doc.data().name)
      .filter(name => name)
      .sort()
      .map(name => `#${name.toLowerCase()}`);

    await sendWhatsAppMessage(
      phoneNumber,
      `*Tus categorias:*\n${categoryNames.join(', ')}`
    );
  } catch (error) {
    console.error('Error fetching categories:', error);
    await sendWhatsAppMessage(phoneNumber, 'Error al obtener las categorias.');
  }
}

async function handleExpenseMessage(phoneNumber, text) {
  // Check if phone is linked
  const linkDoc = await db.collection(COLLECTIONS.WHATSAPP_LINKS).doc(phoneNumber).get();

  if (!linkDoc.exists || linkDoc.data()?.status !== 'linked') {
    await sendWhatsAppMessage(
      phoneNumber,
      'Este numero no esta vinculado a ninguna cuenta de PayTrackr.\n\nPara vincular tu cuenta:\n1. Ingresa a la app\n2. Ve a Configuracion > WhatsApp\n3. Genera un codigo de vinculacion\n4. Envialo aqui: VINCULAR <codigo>'
    );
    return;
  }

  const linkData = linkDoc.data();
  const userId = linkData.userId;

  // Parse the expense message
  const parsed = parseExpenseMessage(text);

  if (!parsed) {
    await sendWhatsAppMessage(
      phoneNumber,
      'No pude entender el mensaje. Usa el formato:\n$<monto> <titulo> #<categoria> d:<descripcion>\n\nEjemplos:\n- "$500 Super #supermercado"\n- "$1500 Cena #salidas d:Cumple de Juan"'
    );
    return;
  }

  try {
    // Find category ID for this user (case-insensitive, partial match fallback)
    const categoryResult = await findCategoryId(userId, parsed.category);

    // Create the payment
    const paymentData = {
      title: parsed.title,
      description: parsed.description,
      amount: parsed.amount,
      category: categoryResult.id,
      isPaid: true,
      paidDate: admin.firestore.FieldValue.serverTimestamp(),
      paymentType: 'one-time',
      userId: userId,
      createdAt: admin.firestore.FieldValue.serverTimestamp(),
      dueDate: admin.firestore.FieldValue.serverTimestamp(),
      recurrentId: null,
      isWhatsapp: true,
      status: 'pending'
    };

    await db.collection(COLLECTIONS.PAYMENTS).add(paymentData);

    // Format amount for display
    const formattedAmount = new Intl.NumberFormat('es-AR', {
      style: 'currency',
      currency: 'ARS'
    }).format(parsed.amount);

    await sendWhatsAppMessage(
      phoneNumber,
      `Gasto registrado!\n\n*${parsed.title}*\nMonto: ${formattedAmount}\nCategoria: ${categoryResult.name}${parsed.description ? `\nDescripcion: ${parsed.description}` : ''}`
    );
  } catch (error) {
    console.error('Error creating payment:', error);
    await sendWhatsAppMessage(
      phoneNumber,
      'Error al registrar el gasto. Intenta nuevamente.'
    );
  }
}

// ============================================
// Helper Functions
// ============================================

function parseExpenseMessage(text) {
  // Format: $<amount> <title> #<category> d:<description>
  // Category and description are optional, order is flexible
  // Examples:
  // - "$500 Super #supermercado d:Compras del finde"
  // - "$1500 Cena #salidas"
  // - "$2000 Uber d:Viaje al centro #transporte"
  // - "$300 Cafe"

  const cleanText = text.trim();

  // Extract amount and the rest
  const amountRegex = /^\$?\s*([\d.,]+)\s+(.+)$/i;
  const match = cleanText.match(amountRegex);

  if (!match) {
    return null;
  }

  let amountStr = match[1];
  let rest = match[2].trim();

  // Normalize amount: remove thousand separators (.) and convert decimal (,) to (.)
  // Argentine format: 1.234,56 -> 1234.56
  amountStr = amountStr.replace(/\./g, '').replace(',', '.');
  const amount = parseFloat(amountStr);

  if (isNaN(amount) || amount <= 0) {
    return null;
  }

  // Extract category (hashtag)
  let category = null;
  const categoryMatch = rest.match(/#(\S+)/);
  if (categoryMatch) {
    category = categoryMatch[1].toLowerCase();
    rest = rest.replace(/#\S+/, '').trim();
  }

  // Extract description (d:)
  let description = '';
  const descMatch = rest.match(/d:(.+?)(?=#|$)/i);
  if (descMatch) {
    description = descMatch[1].trim();
    rest = rest.replace(/d:.+?(?=#|$)/i, '').trim();
  }

  // What remains is the title
  const title = capitalizeFirst(rest.trim());

  if (!title) {
    return null;
  }

  return {
    amount,
    title,
    category,
    description
  };
}

function capitalizeFirst(str) {
  return str.charAt(0).toUpperCase() + str.slice(1);
}

async function findCategoryId(userId, categoryInput) {
  // If no category provided, return "Otros"
  if (!categoryInput) {
    return await findOtrosCategory(userId);
  }

  const normalizedInput = categoryInput.toLowerCase();

  // Fetch all categories for this user
  const categoriesSnapshot = await db
    .collection(COLLECTIONS.CATEGORIES)
    .where('userId', '==', userId)
    .get();

  if (categoriesSnapshot.empty) {
    return { id: '', name: 'Otros' };
  }

  const categories = categoriesSnapshot.docs.map(doc => ({
    id: doc.id,
    name: doc.data().name || ''
  }));

  // 1. Try exact match (case-insensitive)
  const exactMatch = categories.find(c => c.name.toLowerCase() === normalizedInput);
  if (exactMatch) {
    return exactMatch;
  }

  // 2. Try partial match (category name starts with input)
  const partialMatch = categories.find(c => c.name.toLowerCase().startsWith(normalizedInput));
  if (partialMatch) {
    return partialMatch;
  }

  // 3. Try partial match (input is contained in category name)
  const containsMatch = categories.find(c => c.name.toLowerCase().includes(normalizedInput));
  if (containsMatch) {
    return containsMatch;
  }

  // 4. Fallback to "Otros"
  return await findOtrosCategory(userId);
}

async function findOtrosCategory(userId) {
  const otrosSnapshot = await db
    .collection(COLLECTIONS.CATEGORIES)
    .where('userId', '==', userId)
    .where('name', '==', 'Otros')
    .limit(1)
    .get();

  if (!otrosSnapshot.empty) {
    return { id: otrosSnapshot.docs[0].id, name: 'Otros' };
  }

  return { id: '', name: 'Otros' };
}

// Normalize Argentine phone numbers (remove the 9 after country code)
function normalizePhoneNumber(phone) {
  // Argentine mobile numbers come as 5493513467739 but API expects 543513467739
  if (phone.startsWith('549') && phone.length === 13) {
    return '54' + phone.slice(3);
  }
  return phone;
}

async function sendWhatsAppMessage(to, message) {
  // Normalize the phone number for the API
  const normalizedTo = normalizePhoneNumber(to);

  if (!WP_PHONE_NUMBER_ID || !WP_ACCESS_TOKEN) {
    console.log('WhatsApp credentials not configured, skipping message send');
    console.log(`Would send to ${normalizedTo}: ${message}`);
    return;
  }

  try {
    const response = await fetch(
      `https://graph.facebook.com/v21.0/${WP_PHONE_NUMBER_ID}/messages`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${WP_ACCESS_TOKEN}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          messaging_product: 'whatsapp',
          recipient_type: 'individual',
          to: normalizedTo,
          type: 'text',
          text: {
            preview_url: false,
            body: message
          }
        })
      }
    );

    const result = await response.json();

    if (!response.ok) {
      console.error('Error sending WhatsApp message:', result);
    } else {
      console.log('WhatsApp message sent successfully:', result);
    }
  } catch (error) {
    console.error('Error sending WhatsApp message:', error);
  }
}

// ============================================
// Start Server
// ============================================
app.listen(PORT, () => {
  console.log(`WhatsApp webhook server running on port ${PORT}`);
  console.log(`Verify token: ${VERIFY_TOKEN}`);
  console.log(`Webhook URL: http://localhost:${PORT}/webhook`);
});
