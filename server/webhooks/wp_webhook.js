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
// Default category mappings (keywords -> category names)
// ============================================
const CATEGORY_KEYWORDS = {
  'super': 'Supermercado',
  'supermercado': 'Supermercado',
  'mercado': 'Supermercado',
  'comida': 'Supermercado',
  'almuerzo': 'Salidas',
  'cena': 'Salidas',
  'restaurant': 'Salidas',
  'resto': 'Salidas',
  'salida': 'Salidas',
  'cafe': 'Salidas',
  'bar': 'Salidas',
  'uber': 'Transporte',
  'taxi': 'Transporte',
  'nafta': 'Transporte',
  'combustible': 'Transporte',
  'colectivo': 'Transporte',
  'transporte': 'Transporte',
  'alquiler': 'Vivienda y Alquiler',
  'expensas': 'Vivienda y Alquiler',
  'luz': 'Servicios',
  'gas': 'Servicios',
  'agua': 'Servicios',
  'internet': 'Servicios',
  'telefono': 'Servicios',
  'celular': 'Servicios',
  'netflix': 'Suscripciones',
  'spotify': 'Suscripciones',
  'suscripcion': 'Suscripciones',
  'gym': 'Fitness y Deportes',
  'gimnasio': 'Fitness y Deportes',
  'medico': 'Salud',
  'farmacia': 'Salud',
  'remedios': 'Salud',
  'ropa': 'Ropa',
  'zapatillas': 'Ropa',
  'regalo': 'Regalos',
  'mascota': 'Mascotas',
  'veterinario': 'Mascotas',
  'viaje': 'Viajes',
  'vuelo': 'Viajes',
  'hotel': 'Viajes',
  'curso': 'Educacion',
  'libro': 'Educacion',
};

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
      `Cuenta vinculada exitosamente!\n\nAhora podes registrar gastos enviando mensajes como:\n- "$500 super"\n- "1500 almuerzo"\n- "$2000 uber"\n\nEscribi "ayuda" para mas informacion.`
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
  const helpText = `*PayTrackr - Ayuda*

*Registrar un gasto:*
Envia el monto seguido de una descripcion.
Ejemplos:
- "$500 super"
- "1500 almuerzo"
- "$2000 uber"

*Comandos:*
- VINCULAR <codigo> - Vincular este numero a tu cuenta
- DESVINCULAR - Desvincular este numero
- AYUDA - Ver este mensaje

*Categorias automaticas:*
El sistema detecta automaticamente la categoria segun la descripcion (super, almuerzo, uber, netflix, etc.)`;

  await sendWhatsAppMessage(phoneNumber, helpText);
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
      'No pude entender el mensaje. Usa el formato:\n"$500 descripcion" o "1500 descripcion"\n\nEjemplos:\n- "$500 super"\n- "1500 almuerzo"'
    );
    return;
  }

  try {
    // Find category ID for this user
    const categoryId = await findCategoryId(userId, parsed.category);

    // Create the payment
    const paymentData = {
      title: parsed.description,
      description: `Registrado via WhatsApp`,
      amount: parsed.amount,
      category: categoryId,
      isPaid: true,
      paidDate: admin.firestore.FieldValue.serverTimestamp(),
      paymentType: 'one-time',
      userId: userId,
      createdAt: admin.firestore.FieldValue.serverTimestamp(),
      dueDate: admin.firestore.FieldValue.serverTimestamp(),
      recurrentId: null,
      source: 'whatsapp'
    };

    const paymentRef = await db.collection(COLLECTIONS.PAYMENTS).add(paymentData);

    // Format amount for display
    const formattedAmount = new Intl.NumberFormat('es-AR', {
      style: 'currency',
      currency: 'ARS'
    }).format(parsed.amount);

    await sendWhatsAppMessage(
      phoneNumber,
      `Gasto registrado!\n\n*${parsed.description}*\nMonto: ${formattedAmount}\nCategoria: ${parsed.category}`
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
  // Try to extract amount and description
  // Formats supported:
  // - "$500 descripcion"
  // - "500 descripcion"
  // - "$1.500 descripcion"
  // - "$1500,50 descripcion"

  const cleanText = text.trim();

  // Regex to match amount (with optional $ and thousands/decimal separators)
  const amountRegex = /^\$?\s*([\d.,]+)\s+(.+)$/i;
  const match = cleanText.match(amountRegex);

  if (!match) {
    return null;
  }

  let amountStr = match[1];
  const description = match[2].trim();

  // Normalize amount: remove thousand separators (.) and convert decimal (,) to (.)
  // Argentine format: 1.234,56 -> 1234.56
  amountStr = amountStr.replace(/\./g, '').replace(',', '.');

  const amount = parseFloat(amountStr);

  if (isNaN(amount) || amount <= 0) {
    return null;
  }

  // Detect category from description
  const category = detectCategory(description);

  return {
    amount,
    description: capitalizeFirst(description),
    category
  };
}

function detectCategory(description) {
  const normalizedDesc = description.toLowerCase();

  for (const [keyword, category] of Object.entries(CATEGORY_KEYWORDS)) {
    if (normalizedDesc.includes(keyword)) {
      return category;
    }
  }

  return 'Otros';
}

function capitalizeFirst(str) {
  return str.charAt(0).toUpperCase() + str.slice(1);
}

async function findCategoryId(userId, categoryName) {
  // Try to find the category by name for this user
  const categoriesSnapshot = await db
    .collection(COLLECTIONS.CATEGORIES)
    .where('userId', '==', userId)
    .where('name', '==', categoryName)
    .limit(1)
    .get();

  if (!categoriesSnapshot.empty) {
    return categoriesSnapshot.docs[0].id;
  }

  // Try to find "Otros" category
  const otrosSnapshot = await db
    .collection(COLLECTIONS.CATEGORIES)
    .where('userId', '==', userId)
    .where('name', '==', 'Otros')
    .limit(1)
    .get();

  if (!otrosSnapshot.empty) {
    return otrosSnapshot.docs[0].id;
  }

  // Return empty string if no category found
  return '';
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
