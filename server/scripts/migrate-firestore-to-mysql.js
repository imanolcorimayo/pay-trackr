/**
 * One-time migration: Firestore → MySQL (mangos)
 *
 * Usage: node scripts/migrate-firestore-to-mysql.js
 *
 * Safe to re-run (uses INSERT IGNORE).
 * Migration order respects FK dependencies:
 *   1. user (extracted from all collections)
 *   2. expense_category
 *   3. recurrent
 *   4. payment + payment_recipient
 *   5. fcm_token
 */

import 'dotenv/config';
import admin from 'firebase-admin';
import mysql from 'mysql2/promise';

// ── Firebase Init ────────────────────────────────
if (!admin.apps.length) {
  const firebaseConfig = {
    projectId: process.env.FIREBASE_PROJECT_ID || 'pay-tracker-7a5a6',
  };

  if (process.env.FIREBASE_SERVICE_ACCOUNT) {
    const serviceAccount = JSON.parse(
      Buffer.from(process.env.FIREBASE_SERVICE_ACCOUNT, 'base64').toString()
    );
    firebaseConfig.credential = admin.credential.cert(serviceAccount);
  }

  admin.initializeApp(firebaseConfig);
}

const db = admin.firestore();

// ── MySQL Init ───────────────────────────────────
const pool = await mysql.createPool({
  host: 'localhost',
  database: 'mangos',
  user: 'imanol',
  password: '1234',
  waitForConnections: true,
});

// ── Helpers ──────────────────────────────────────
function toISO(ts) {
  if (!ts) return null;
  if (ts.toDate) return ts.toDate().toISOString().slice(0, 19).replace('T', ' ');
  if (ts instanceof Date) return ts.toISOString().slice(0, 19).replace('T', ' ');
  return null;
}

function log(msg) {
  console.log(`[${new Date().toISOString().slice(11, 19)}] ${msg}`);
}

async function fetchAll(collection) {
  const snap = await db.collection(collection).get();
  return snap.docs.map(doc => ({ id: doc.id, ...doc.data() }));
}

// ── Step 1: Collect unique userIds & create users ─
log('Fetching all collections from Firestore...');

const [categories, recurrents, payments, fcmTokens] = await Promise.all([
  fetchAll('expenseCategories'),
  fetchAll('recurrent'),
  fetchAll('payment2'),
  fetchAll('fcmTokens'),
]);

log(`Firestore: ${categories.length} categories, ${recurrents.length} recurrents, ${payments.length} payments, ${fcmTokens.length} fcmTokens`);

// Extract all unique userIds
const userIds = new Set();
for (const doc of [...categories, ...recurrents, ...payments, ...fcmTokens]) {
  if (doc.userId) userIds.add(doc.userId);
}

log(`Found ${userIds.size} unique user(s)`);

// Insert users with placeholder data (updated on first real login)
let usersInserted = 0;
for (const uid of userIds) {
  const [result] = await pool.execute(
    `INSERT IGNORE INTO \`user\` (id, email, name, google_id) VALUES (?, ?, ?, ?)`,
    [uid, `${uid}@placeholder.local`, uid, uid]
  );
  if (result.affectedRows > 0) usersInserted++;
}
log(`Users: ${usersInserted} inserted (${userIds.size - usersInserted} already existed)`);

// ── Step 2: Migrate expense categories ───────────
let catCount = 0;
for (const c of categories) {
  if (!c.userId) continue;

  const [result] = await pool.execute(
    `INSERT IGNORE INTO expense_category (id, user_id, name, color, deleted_ts, created_ts)
     VALUES (?, ?, ?, ?, ?, ?)`,
    [
      c.id,
      c.userId,
      c.name || 'Sin nombre',
      c.color || '#808080',
      toISO(c.deletedAt),
      toISO(c.createdAt),
    ]
  );
  if (result.affectedRows > 0) catCount++;
}
log(`Categories: ${catCount} inserted (${categories.length - catCount} skipped/existed)`);

// ── Step 3: Migrate recurrents ───────────────────
// Build a set of valid category IDs to avoid FK violations
const [existingCats] = await pool.execute('SELECT id FROM expense_category');
const validCatIds = new Set(existingCats.map(r => r.id));

let recCount = 0;
for (const r of recurrents) {
  if (!r.userId) continue;

  const expenseCategoryId = r.categoryId && validCatIds.has(r.categoryId) ? r.categoryId : null;

  const [result] = await pool.execute(
    `INSERT IGNORE INTO recurrent (id, user_id, title, description, amount, start_date,
     due_date_day, end_date, time_period, expense_category_id, created_ts)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      r.id,
      r.userId,
      r.title || 'Sin título',
      r.description || '',
      r.amount || 0,
      r.startDate || null,
      parseInt(r.dueDateDay, 10) || 1,
      r.endDate || null,
      r.timePeriod || 'monthly',
      expenseCategoryId,
      toISO(r.createdAt),
    ]
  );
  if (result.affectedRows > 0) recCount++;
}
log(`Recurrents: ${recCount} inserted (${recurrents.length - recCount} skipped/existed)`);

// ── Step 4: Migrate payments + recipients ────────
// Build set of valid recurrent IDs to avoid FK violations
const [existingRecs] = await pool.execute('SELECT id FROM recurrent');
const validRecIds = new Set(existingRecs.map(r => r.id));

let payCount = 0;
let recipientCount = 0;

for (const p of payments) {
  if (!p.userId) continue;

  const expenseCategoryId = p.categoryId && validCatIds.has(p.categoryId) ? p.categoryId : null;
  const recurrentId = p.recurrentId && validRecIds.has(p.recurrentId) ? p.recurrentId : null;

  // Determine valid enum values
  const paymentType = ['one-time', 'recurrent'].includes(p.paymentType) ? p.paymentType : 'one-time';
  const source = ['manual', 'whatsapp-text', 'whatsapp-audio', 'whatsapp-image', 'whatsapp-pdf'].includes(p.source)
    ? p.source : 'manual';
  const status = ['pending', 'reviewed'].includes(p.status) ? p.status : 'reviewed';

  const [result] = await pool.execute(
    `INSERT IGNORE INTO payment (id, user_id, title, description, amount, expense_category_id,
     is_paid, paid_ts, recurrent_id, payment_type, due_ts, source, status,
     needs_revision, is_whatsapp, audio_transcription, created_ts)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      p.id,
      p.userId,
      p.title || 'Sin título',
      p.description || '',
      p.amount || 0,
      expenseCategoryId,
      p.isPaid ? 1 : 0,
      toISO(p.paidDate),
      recurrentId,
      paymentType,
      // Fall back to paidDate or createdAt so the API's due_ts-based date
      // filter doesn't hide one-time entries that never had a due date set.
      toISO(p.dueDate) || toISO(p.paidDate) || toISO(p.createdAt),
      source,
      status,
      p.needsRevision ? 1 : 0,
      p.isWhatsapp ? 1 : 0,
      p.audioTranscription || null,
      toISO(p.createdAt),
    ]
  );

  if (result.affectedRows > 0) {
    payCount++;

    // Insert recipient if present
    if (p.recipient && p.recipient.name) {
      const [rResult] = await pool.execute(
        `INSERT IGNORE INTO payment_recipient (payment_id, name, cbu, alias, bank)
         VALUES (?, ?, ?, ?, ?)`,
        [
          p.id,
          p.recipient.name,
          p.recipient.cbu || null,
          p.recipient.alias || null,
          p.recipient.bank || null,
        ]
      );
      if (rResult.affectedRows > 0) recipientCount++;
    }
  }
}
log(`Payments: ${payCount} inserted (${payments.length - payCount} skipped/existed)`);
log(`Recipients: ${recipientCount} inserted`);

// ── Step 5: Migrate FCM tokens ───────────────────
let tokenCount = 0;
for (const t of fcmTokens) {
  if (!t.userId || !t.token) continue;

  const [result] = await pool.execute(
    `INSERT IGNORE INTO fcm_token (id, user_id, token, device_id, notifications_enabled, created_ts)
     VALUES (?, ?, ?, ?, ?, ?)`,
    [
      t.id,
      t.userId,
      t.token,
      t.deviceId || null,
      t.notificationsEnabled !== false ? 1 : 0,
      toISO(t.createdAt),
    ]
  );
  if (result.affectedRows > 0) tokenCount++;
}
log(`FCM Tokens: ${tokenCount} inserted (${fcmTokens.length - tokenCount} skipped/existed)`);

// ── Done ─────────────────────────────────────────
log('');
log('=== Migration Complete ===');

const [counts] = await pool.execute(`
  SELECT 'user' AS tbl, COUNT(*) AS cnt FROM \`user\`
  UNION ALL SELECT 'expense_category', COUNT(*) FROM expense_category
  UNION ALL SELECT 'recurrent', COUNT(*) FROM recurrent
  UNION ALL SELECT 'payment', COUNT(*) FROM payment
  UNION ALL SELECT 'payment_recipient', COUNT(*) FROM payment_recipient
  UNION ALL SELECT 'fcm_token', COUNT(*) FROM fcm_token
`);

for (const row of counts) {
  log(`  ${row.tbl}: ${row.cnt} rows`);
}

await pool.end();
process.exit(0);
