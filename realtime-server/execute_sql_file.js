import fs from 'fs';
import path from 'path';
import mysql from 'mysql2/promise';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const sqlFilePath = path.resolve(__dirname, '../backend/database/repopulate_workshops.sql');

async function executeSql() {
    console.log('Reading SQL file...');
    const sqlContent = fs.readFileSync(sqlFilePath, 'utf8');

    // Configuration options to try
    const configs = [
        { host: '127.0.0.1', user: 'kairos_user', password: 'kairos_pass', database: 'kairos_db', multipleStatements: true },
        { host: '127.0.0.1', user: 'kairos_user', password: 'kairos_secret_password_change_this', database: 'kairos_db', multipleStatements: true },
        { host: '127.0.0.1', user: 'root', password: '', database: 'kairos_db', multipleStatements: true },
        { host: 'localhost', user: 'kairos_user', password: 'kairos_pass', database: 'kairos_db', multipleStatements: true }
    ];

    let connection;
    for (const config of configs) {
        try {
            console.log(`Trying to connect with user: ${config.user} on ${config.host}...`);
            connection = await mysql.createConnection(config);
            console.log('Connected successfully!');
            break;
        } catch (err) {
            console.log(`Failed to connect with config: ${err.message}`);
        }
    }

    if (!connection) {
        console.error('Could not connect to database with any known configuration.');
        process.exit(1);
    }

    try {
        console.log('Executing SQL script...');
        await connection.query(sqlContent);
        console.log('SQL script executed successfully! Database updated.');
    } catch (err) {
        console.error('Error executing SQL script:', err);
    } finally {
        await connection.end();
    }
}

executeSql();
