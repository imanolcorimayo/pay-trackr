#!/usr/bin/env node
/**
 * Mints a Firebase ID token for an arbitrary test UID.
 * Used by the /test suite. Two-step: createCustomToken (Admin SDK)
 * → exchange via signInWithCustomToken REST endpoint → ID token.
 *
 * Usage: node scripts/get-test-id-token.mjs [uid]
 *   Default uid: test-mangos-001
 *   Prints the ID token to stdout on success.
 */

import dotenv from 'dotenv';
import path from 'node:path';
import fs from 'node:fs';
import { fileURLToPath } from 'node:url';
import admin from 'firebase-admin';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, '../..');

dotenv.config({ path: path.join(repoRoot, 'server/.env') });

const webEnv = dotenv.parse(fs.readFileSync(path.join(repoRoot, 'web/.env'), 'utf8'));
const apiKey = webEnv.FIREBASE_API_KEY;
if (!apiKey) {
    console.error('FIREBASE_API_KEY not found in web/.env');
    process.exit(1);
}

const serviceAccountB64 = process.env.FIREBASE_SERVICE_ACCOUNT;
if (!serviceAccountB64) {
    console.error('FIREBASE_SERVICE_ACCOUNT not found in server/.env');
    process.exit(1);
}

const serviceAccount = JSON.parse(Buffer.from(serviceAccountB64, 'base64').toString());

if (!admin.apps.length) {
    admin.initializeApp({ credential: admin.credential.cert(serviceAccount) });
}

const uid = process.argv[2] || 'test-mangos-001';
const customToken = await admin.auth().createCustomToken(uid);

// The Firebase Web API key has HTTP-referer restrictions, so this fetch must
// send a Referer that matches the allow-list. Defaults to WEB_BASE_URL from
// web/.env (typically http://localhost for dev). Override via TEST_REFERER.
const referer = process.env.TEST_REFERER || webEnv.WEB_BASE_URL || 'http://localhost';

const res = await fetch(
    `https://identitytoolkit.googleapis.com/v1/accounts:signInWithCustomToken?key=${apiKey}`,
    {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Referer': referer,
        },
        body: JSON.stringify({ token: customToken, returnSecureToken: true }),
    }
);

const data = await res.json();
if (!res.ok || !data.idToken) {
    console.error('Failed to exchange custom token for ID token:');
    console.error(JSON.stringify(data, null, 2));
    process.exit(1);
}

process.stdout.write(data.idToken);
process.exit(0);
