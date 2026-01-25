import mysql from 'mysql2/promise';
import dotenv from 'dotenv';

dotenv.config();

const defaultDbConfig = {
    host: process.env.DB_HOST || '127.0.0.1',
    user: process.env.DB_USER || 'kairos_user',
    password: process.env.DB_PASSWORD || 'kairos_pass',
    multipleStatements: true
};

const fallbackPassword = 'kairos_secret_password_change_this';

// Need to retry with fallback password if initial connection fails
async function createConnectionWithRetry() {
    const credentials = [
        { user: process.env.DB_USER || 'kairos_user', password: process.env.DB_PASSWORD || 'kairos_pass' },
        { user: 'kairos_user', password: 'kairos_pass' },
        { user: 'kairos_user', password: 'kairos_secret_password_change_this' },
        { user: 'root', password: '' },
        { user: 'root', password: 'root' },
        { user: 'root', password: 'kairos_root_password' }
    ];

    for (const cred of credentials) {
        try {
            console.log(`Trying user: ${cred.user}, pass: ${cred.password ? '***' : '(empty)'}`);
            const cfg = {
                ...defaultDbConfig,
                user: cred.user,
                password: cred.password
            };
            const conn = await mysql.createConnection(cfg);
            console.log('Success!');
            return conn;
        } catch (err) {
            console.log(`Failed: ${err.message}`);
            if (err.code !== 'ER_ACCESS_DENIED_ERROR') throw err; // Re-throw non-auth errors
        }
    }
    throw new Error('All credentials failed.');
}

console.log('DB Config:', { ...defaultDbConfig, password: '***' });

const csvData = `Circ i oficis de les arts escèniques,"Aproximació a les tècniques de circ (acrobàcia, equilibris, trapezi) com a eina social i valors de cooperació.",A,Escènic,16,20,10,2,Dilluns,16:00-18:00,26/01/2026,06/04/2026,Ateneu Popular 9 Barris,Laia Riera,Sí
Fusta,"Exploració de la fusta per construir productes i conèixer sortides professionals com l'artesania i la construcció.",A,Indústria-manufactura,16,20,10,2,Dimarts i Dijous,10:00-12:00,03/02/2026,05/03/2026,Gremi de Fusters BCN,Marc Soler,Sí
Cuina comunitària,"Tastet d'oficis d'hostaleria i indústries alimentàries amb pràctiques en obradors professionals.",A,Hoteleria - Alimentació,16,30,10,3,Dimecres,09:00-12:00,14/01/2026,18/03/2026,Fundació Alícia,Jordi Roca,Sí
Metall i artesania,"Disseny de peces de joieria i objectes metàl·lics per conèixer tècniques artesanes i industrials.",A,Indústria 4.0,16,20,10,2,Divendres,15:00-17:00,30/01/2026,10/04/2026,Tallers Artístics del Metall,Sònia Puig,Sí
Serigrafia,"Tècniques per tenyir i personalitzar teixits (tote bags, cortines) mitjançant serigrafia.",A,Indústria-manufactura,16,20,10,2,Dilluns i Dimecres,11:30-13:30,09/02/2026,11/03/2026,Estudi Print,Carles Font,Sí
Oficis gastronòmics,"Coneixement d'ingredients, tècniques culinàries i tipus de serveis (càtering, restaurant).",A,Oci i benestar,16,20,10,2,Dijous,12:00-14:00,16/04/2026,18/06/2026,Escola d'Hostaleria,Maria López,Sí
TMB es mou per l'educació,"Coneixement de l'empresa TMB, gestió de xarxa de transport i mobilitat sostenible.",A,Indústria 4.0 - Mobilitat,16,20,10,2,Dimarts,09:30-11:30,20/01/2026,24/03/2026,TMB Educació,Pere Martí,Sí
Vela,"Iniciació a la navegació a vela, entenent el vent com a força i la vela com a motor.",A,Esportiu oci i benestar,16,30,10,3,Dilluns,10:00-13:00,13/04/2026,15/06/2026,Escola Municipal de Vela,Anna Valls,Sí
SmArt: descobriment de professions creatives,"Visites a professionals creatius (dissenyadors, artistes, fotògrafs) per entendre el valor de la creativitat.",A,Indústries creatives,16,20,10,2,Dimecres,15:30-17:30,08/04/2026,10/06/2026,FAD Barcelona,Roger Pou,Sí
Imatge personal per a tothom,"Tècniques d'atenció al client i aspectes pràctics per millorar el benestar personal i la imatge.",B,Oci i benestar,20,20,10,2,Divendres,11:00-13:00,23/01/2026,03/04/2026,Institut de la Imatge,Laura Gómez,Sí
Vinyeta a vinyeta fem un còmic,"Creació d'una obra conjunta de còmic per conèixer el món de la creació artística i narrativa.",B,Artístic-Creatiu,20,20,10,2,Dimarts i Dijous,16:00-18:00,14/04/2026,14/05/2026,Escola Joso,Pau Mir,Sí
Projectes d'Internet de les coses,"Introducció a la robòtica i programació IoT (sensors, intercanvi de dades).",B,Tecnològic,20,20,10,2,Dilluns,14:00-16:00,02/02/2026,13/04/2026,TechnoLab BCN,David Vila,Sí
Tecnologia digital per al disseny de producte,"Disseny 2D i 3D, impressió 3D i tall làser mitjançant treball per reptes.",B,Digital-Tecnològic,20,20,10,2,Dimecres,10:00-12:00,04/02/2026,08/04/2026,FabLab Kids,Marta Rovira,Sí
Fem jocs a l'aula (gamificació),"Exploració de professions de creació de jocs (autoria, il·lustració) per desenvolupar competències transversals.",B,Indústries creatives,20,20,10,2,Dijous,15:00-17:00,29/01/2026,09/04/2026,Associació Jugar i Aprendre,Enric Mas,Sí
Descobrim la nostra història,"Arqueologia pràctica per entendre el passat i connectar-lo amb la realitat actual de l'alumne.",B,Científic-Humanístic,20,20,10,2,Divendres,09:00-11:00,17/04/2026,19/06/2026,Museu d'Història,Clara Sant,Sí
Taller de cinema,"Descoberta pràctica d'oficis del cinema (guió, càmera, muntatge) creant una obra per a una entitat social.",B,Indústries creatives,20,20,10,2,Dilluns i Dimecres,16:30-18:30,20/04/2026,20/05/2026,Cinema en Curs,Pol Ribas,Sí
Interpretació teatral,"Eines de vida a partir del teatre, treballant l'autoestima i descobrint l'ofici d'actor/actriu.",B,Arts escèniques,20,20,10,2,Dimarts,15:30-17:30,27/01/2026,31/03/2026,Teatre Jove,Júlia Canals,Sí
"Percussió, moviment, música","Aprenentatge d'instruments de percussió i moviment corporal per millorar la motricitat i socialització.",B,Artístic-musical,20,20,10,2,Dijous,11:00-13:00,05/02/2026,16/04/2026,Ritmes del Món,Xavi Percu,Sí
Fem moda sostenible,"Disseny de suprarreciclatge tèxtil per reduir l'impacte de les deixalles i conscienciar sobre la contaminació.",B,Moda-sostenibilitat,20,20,10,2,Dilluns,10:00-12:00,26/01/2026,06/04/2026,Moda Ètica BCN,Elena Fil,Sí
El rap i hip Hop en català,"Creació de rimes, melodies simples i freestyle per millorar l'expressió i el català.",B,Música-ritmes urbans,20,20,10,2,Dimecres,15:00-17:00,15/04/2026,17/06/2026,Taller de Músics,MC Joan,Sí
Tecnologia per millorar el món,"Programació amb Scratch i placa Micro:bit per solucionar reptes vinculats als ODS.",B,Tecnològic,20,20,10,2,Divendres,12:00-14:00,30/01/2026,10/04/2026,CodeLearn,Sergi Tech,Sí
Mur-Art,"Realització d'un mural al centre educatiu per fomentar el treball en equip i l'empoderament del grup.",B,Artístic - social,20,20,10,2,Dimarts,10:00-12:00,07/04/2026,09/06/2026,Art Urbà,Mònica Pint,Sí
Retratistes de la ciutat (B),"Projecte fotogràfic vinculat al patrimoni de l'entorn, acompanyat per un fotògraf professional.",B,Digital-artístic,20,20,10,2,Dijous,16:00-18:00,22/01/2026,26/03/2026,Institut Fotogràfic,Lluís Click,Sí
Intervencions artístiques al centre (IAC),"Transformació d'espais del centre (passadissos, patis) amb accions artístiques i materials reutilitzats.",B,Construcció-Artístic,20,20,10,2,Dilluns i Dimecres,09:00-11:00,16/02/2026,18/03/2026,Col·lectiu Arrel,Núria Bosch,Sí
Energies Renovables,"Instal·lació de sistemes fotovoltaics i projectes solars per conèixer perfils de sostenibilitat.",C,Indústria avançada,16,30,10,3,Dijous,09:00-12:00,29/01/2026,02/04/2026,Solar Energy School,Albert Sol,Sí
Talent femení a l'àmbit tecnològic,"Taller per a noies sobre muntatge d'equips, programació i seguretat informàtica per trencar estereotips.",C,Digital,16,30,10,3,Dilluns,15:00-18:00,16/02/2026,27/04/2026,Dones Tech,Mireia Codi,Sí
Estètica,"Formació en manicura, maquillatge i atenció al client en entorn professionalitzador.",C,Oci i benestar,16,30,10,3,Dimarts,09:30-12:30,03/02/2026,07/04/2026,Gremi d'Estètica,Carme Bell,Sí
Circ i oficis de les arts escèniques (C),"Tècniques de circ com a espai de convivència i millora d'habilitats personals (versió intensiva).",C,Escènic,16,30,10,3,Dimecres,16:00-19:00,08/04/2026,10/06/2026,Ateneu Popular 9 Barris,Laia Riera,Sí
Cuina comunitària (C),"Pràctiques en obradors professionals construint una hoteleria diferent (versió intensiva).",C,Hoteleria,16,30,10,3,Divendres,09:00-12:00,23/01/2026,27/03/2026,Fundació Alícia,Jordi Roca,Sí
Taller de costura,"Ús de màquines de cosir i operacions de muntatge i confecció tèxtil.",C,Moda-Manufactura,16,30,10,3,Dijous,15:00-18:00,05/02/2026,16/04/2026,Moda i Confecció,Rosa Fil,Sí
Instal·lacions domèstiques,"Muntatge d'instal·lacions elèctriques i canonades, treballant la precisió i hàbits laborals.",C,Indústria avançada,16,30,10,3,Dilluns,09:00-12:00,13/04/2026,15/06/2026,Gremi d'Instal·ladors,Manel Tub,Sí
Oficis gastronòmics (C),"Elaboració de receptes en subgrups sota normes sanitàries en un context real de restauració.",C,Oci i benestar,16,30,10,3,Dimarts,10:00-13:00,27/01/2026,31/03/2026,Escola d'Hostaleria,Maria López,Sí
Perruqueria,"Tècniques de rentat, assecat, marcat i tractament capil·lar per definir trajectòria professional.",C,Oci i benestar,16,30,10,3,Dimecres,15:00-18:00,11/02/2026,22/04/2026,Acadèmia Hair,Josep Pèl,Sí
Metall i artesania (C),"Treball del metall (cera, modelatge) amb constància i paciència en entorn real.",C,Indústria 4.0,16,30,10,3,Dijous,09:30-12:30,16/04/2026,18/06/2026,Tallers Artístics del Metall,Sònia Puig,Sí
Mecànica bàsica de la bicicleta,"Diagnòstic i manteniment de bicicletes, fomentant la mecànica i prevenció de residus.",C,Indústria 4.0,16,30,10,3,Divendres,16:00-19:00,30/01/2026,10/04/2026,Biciclot,Andreu Roda,Sí
Vela (C),"Navegació a vela intensiva per promoure l'esport i l'estil de vida nàutic.",C,"Esportiu, oci",16,30,10,3,Dilluns,10:00-13:00,20/04/2026,22/06/2026,Escola Municipal de Vela,Anna Valls,Sí
Xarxes d'aigua potable i sanejament,"Cicle de l'aigua, muntatge de canonades i instal·lacions d'aigua potable.",C,Indústria avançada,16,30,10,3,Dimarts,09:00-12:00,10/02/2026,21/04/2026,Aigües de Barcelona,Pere Aigua,Sí
Retratistes de la ciutat (C),"Projecte fotogràfic sobre patrimoni amb sessions al centre i a l'entorn (versió intensiva).",C,Digital-artístic,16,30,10,3,Dimecres,15:30-18:30,04/02/2026,08/04/2026,Institut Fotogràfic,Lluís Click,Sí
Acompanyament a les persones,"Tècniques d'atenció a la infància i gent gran, dinamització i primers auxilis sanitaris.",C,Sanitari-Social,16,30,10,3,Dijous,10:00-13:00,12/03/2026,21/05/2026,Creu Roja,Marta Ajuda,Sí
Jardineria,"Ús de màquines de jardineria, tècniques de reg, plantació i manteniment.",C,Medi ambient,16,30,10,3,Divendres,09:00-12:00,17/04/2026,19/06/2026,Parcs i Jardins,Joan Verd,Sí
Oficis de la mar,"Visites pràctiques a vaixells i llotges per conèixer oficis com pescador o patró.",C,Medi ambient,16,30,10,3,Dilluns,08:30-11:30,26/01/2026,06/04/2026,Confraria de Pescadors,Toni Mar,Sí
Fusta (C),"Construcció i fusteria (plànols, muntatge) en context real de treball.",C,Indústria-manufactura,16,30,10,3,Dimarts,15:00-18:00,14/04/2026,16/06/2026,Gremi de Fusters BCN,Marc Soler,Sí
Serigrafia (C),"Disseny, creació de pantalles i tintat de teixits en context professional.",C,Indústria-manufactura,16,30,10,3,Dimecres,10:00-13:00,28/01/2026,01/04/2026,Estudi Print,Carles Font,Sí
Prepara el teu PC,"Desmuntatge de hardware, software lliure i creació de centre multimèdia.",C,Digital-Indústria,16,30,10,3,Dijous,16:00-19:00,19/02/2026,30/04/2026,Reutilitza PC,Jordi Xip,Sí
TMB es mou per l'educació (C),"Gestió i control de xarxa de transport públic i sensibilització ambiental (versió intensiva).",C,Indústria 4.0 - Mobilitat,16,30,10,3,Divendres,09:30-12:30,06/02/2026,17/04/2026,TMB Educació,Pere Martí,Sí
Fem cinema (C),"Aprenentatge servei creant un audiovisual per a una ONG, aprenent oficis de cinema.",C,Indústries creatives,16,30,10,3,Dilluns,15:30-18:30,09/03/2026,18/05/2026,Cinema en Curs,Pol Ribas,Sí
Murals del barri,"Creació d'un mural a l'espai públic com a servei a la comunitat.",C,Indústries creatives,16,30,10,3,Dimarts,10:00-13:00,21/04/2026,23/06/2026,Art Urbà,Mònica Pint,Sí
Mobilitat i transport amb bicicleta,"Formació en conducció segura urbana (mètode Una bici més) i disseny de rutes.",C,Esportiu-social,16,30,10,3,Dimecres,16:00-19:00,25/02/2026,06/05/2026,Biciclot,Andreu Roda,Sí
De Picasso al Manga,"Recursos gràfics i narratius al Museu Picasso aplicats a còmic, animació o videojocs.",C,Creatiu-artístic,16,30,10,3,Dijous,10:00-13:00,15/01/2026,19/03/2026,Museu Picasso,Clara Art,Sí`;

// Image keywords mapping
const imageKeywords = {
    'Circ': 'circus,acrobat',
    'Fusta': 'woodworking,carpentry',
    'Cuina': 'cooking,kitchen,chef',
    'Metall': 'metalwork,welding',
    'Serigrafia': 'screenprinting',
    'Oficis gastronòmics': 'gastronomy,food',
    'TMB': 'bus,public transport',
    'Vela': 'sailing,yacht',
    'SmArt': 'creative,artist',
    'Imatge personal': 'personal care,spa',
    'Vinyeta': 'comic,drawing',
    'IoT': 'iot,electronics,robot',
    'Tecnologia digital': '3d printing,design',
    'Fem jocs': 'board game,playing',
    'història': 'history,museum,archaeology',
    'cinema': 'cinema,filmmaking,camera',
    'teatral': 'theatre,stage,acting',
    'Percussió': 'percussion,drums',
    'moda': 'fashion,sewing,clothes',
    'rap': 'hiphop,music,microphone',
    'Tecnologia': 'coding,programming,technology',
    'Mur-Art': 'mural,graffiti,street art',
    'Retratistes': 'photography,camera,portrait',
    'Intervencions': 'art,sculpture',
    'Energies': 'solar panel,renewable energy',
    'Talent femení': 'women in tech,programming',
    'Estètica': 'beauty,makeup',
    'Costura': 'sewing machine,tailor',
    'Instal·lacions': 'electrician,wiring',
    'Perruqueria': 'hairdresser,salon',
    'Mecànica': 'bicycle,mechanic',
    'Xarxes': 'water pipe,plumbing',
    'Acompanyament': 'caregiver,elderly',
    'Jardineria': 'gardening,flowers',
    'Oficis de la mar': 'fishing,fisherman',
    'PC': 'computer,hardware',
    'Mobilitat': 'cycling,bicycle',
    'Picasso': 'art,painting,museum'
};

function getImageUrl(name, ambit) {
    let keywords = 'workshop';
    for (const key in imageKeywords) {
        if (name.includes(key)) {
            keywords = imageKeywords[key];
            break;
        }
    }
    // Use loremflickr
    const firstKeyword = keywords.split(',')[0];
    return `https://loremflickr.com/800/600/${firstKeyword}`;
}

// Convert DD/MM/YYYY to YYYY-MM-DD
function parseDate(dateStr) {
    const [day, month, year] = dateStr.split('/');
    return `${year}-${month}-${day}`;
}

// Parse CSV manually respecting quotes
function parseCSV(text) {
    const lines = text.split('\n');
    const result = [];

    for (const line of lines) {
        if (!line.trim()) continue;

        const row = [];
        let current = '';
        let inQuotes = false;

        for (let i = 0; i < line.length; i++) {
            const char = line[i];

            if (char === '"') {
                inQuotes = !inQuotes;
            } else if (char === ',' && !inQuotes) {
                row.push(current.trim());
                current = '';
            } else {
                current += char;
            }
        }
        row.push(current.trim()); // Last field

        // Clean quotes from description if present
        if (row[1] && row[1].startsWith('"') && row[1].endsWith('"')) {
            row[1] = row[1].substring(1, row[1].length - 1);
        }
        // Also first column might need cleaning if quote weirdness
        // but description is the main one with commas.

        result.push(row);
    }
    return result;
}

async function main() {
    const connection = await createConnectionWithRetry();

    try {
        console.log('Connected to server.');
        const [dbs] = await connection.query('SHOW DATABASES');
        console.log('Databases:', dbs.map(d => d.Database));

        // Try to select kairos_db or find likely candidate
        let dbName = process.env.DB_NAME || 'kairos_db';
        if (!dbs.find(d => d.Database === dbName)) {
            console.log(`Database ${dbName} not found.`);
            // Fallback to searching
            const match = dbs.find(d => d.Database.includes('kairos'));
            if (match) {
                dbName = match.Database;
                console.log(`Found similar database: ${dbName}`);
            } else {
                throw new Error('No suitable database found.');
            }
        }

        await connection.query(`USE ${dbName}`);
        console.log(`Using database: ${dbName}`);

        // Disable FK checks and truncate
        await connection.query('SET FOREIGN_KEY_CHECKS = 0');
        // We check if table exists
        await connection.query('TRUNCATE TABLE workshops');
        console.log('Truncated workshops table.');
        await connection.query('SET FOREIGN_KEY_CHECKS = 1');

        // Parse data
        const rows = parseCSV(csvData);
        console.log(`Parsed ${rows.length} rows.`);

        // Prepare insert
        // Columns mapping based on CSV order:
        // 0: Nom
        // 1: Descripció (cleaned)
        // 2: Modalitat
        // 3: Àmbit (category/ambit)
        // 4: Capacitat
        // 5: Durada Hores
        // 6: Durada Dies
        // 7: Hores/dia
        // 8: Dies Setmana -> allowed_days JSON ["..."]
        // 9: Horari -> time_slots JSON ["..."]
        // 10: Data Inici -> start_date
        // 11: Data Fi -> end_date
        // 12: Proveïdor
        // 13: Contacte
        // 14: Actiu

        for (const row of rows) {
            if (row.length < 14) continue;

            const name = row[0];
            const description = row[1];
            const modality = row[2];
            const ambit = row[3];
            const capacity = parseInt(row[4]);
            const duration_hours = parseInt(row[5]);
            const duration_days = parseInt(row[6]);
            const hours_per_day = parseInt(row[7]);

            // parse days
            let daysStr = row[8];
            let days = [];
            if (daysStr.includes(' i ')) {
                days = daysStr.split(' i ');
            } else {
                days = [daysStr];
            }
            const allowed_days = JSON.stringify(days);

            // time
            const time_slots = JSON.stringify([row[9]]);

            const start_date = parseDate(row[10]);
            const end_date = parseDate(row[11]);

            const provider_name = row[12];
            const provider_contact = row[13];
            const is_active = row[14] === 'Sí' ? 1 : 0;

            const imageUrl = getImageUrl(name, ambit);
            const images = JSON.stringify([imageUrl]);

            // Insert
            const sql = `INSERT INTO workshops (
        name, description, modality, capacity, max_capacity, ambit, 
        duration_hours, duration_days, hours_per_day, 
        allowed_days, time_slots, start_date, end_date, 
        provider_name, provider_contact, is_active, 
        images, image
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`;

            const values = [
                name, description, modality, capacity, capacity, ambit,
                duration_hours, duration_days, hours_per_day,
                allowed_days, time_slots, start_date, end_date,
                provider_name, provider_contact, is_active,
                images, imageUrl
            ];

            // We assume both 'images' (JSON) and 'image' (VARCHAR) columns might exist or not. 
            // But we can't easily conditionally insert in one statement without knowing schema.
            // Let's try to detect columns first.
        }

        // Better strategy: Check columns first
        const [columns] = await connection.query('DESCRIBE workshops');
        const columnNames = columns.map(c => c.Field);

        console.log('Columns detected:', columnNames);

        for (const row of rows) {
            if (row.length < 14) continue;

            const name = row[0];
            const description = row[1];
            const modality = row[2];
            const ambit = row[3];
            const capacity = parseInt(row[4]);
            const duration_hours = parseInt(row[5]);
            const duration_days = parseInt(row[6]);
            const hours_per_day = parseInt(row[7]);

            // parse days
            let daysStr = row[8];
            let days = [];
            if (daysStr.includes(' i ')) {
                days = daysStr.split(' i ').map(d => d.trim());
            } else {
                days = [daysStr.trim()];
            }
            const allowed_days = JSON.stringify(days);

            const time_slots = JSON.stringify([row[9]]);
            const start_date = parseDate(row[10]);
            const end_date = parseDate(row[11]);
            const provider_name = row[12];
            const provider_contact = row[13];
            const is_active = row[14] === 'Sí' ? 1 : 0;
            const imageUrl = getImageUrl(name, ambit);
            const images = JSON.stringify([imageUrl]);

            const data = {
                name, description, modality, capacity, ambit,
                duration_hours, duration_days, hours_per_day,
                provider_name, provider_contact, is_active
            };

            // Add conditional fields
            if (columnNames.includes('max_capacity')) data.max_capacity = capacity;
            if (columnNames.includes('allowed_days')) data.allowed_days = allowed_days;
            if (columnNames.includes('time_slots')) data.time_slots = time_slots;
            if (columnNames.includes('start_date')) data.start_date = start_date;
            if (columnNames.includes('end_date')) data.end_date = end_date;
            if (columnNames.includes('images')) data.images = images;
            if (columnNames.includes('image')) data.image = imageUrl;
            if (columnNames.includes('theme') && !columnNames.includes('ambit')) data.theme = ambit; // Fallback if ambit renamed

            const keys = Object.keys(data);
            const valPlaceholders = keys.map(() => '?').join(', ');
            const valArray = keys.map(k => data[k]);

            const query = `INSERT INTO workshops (${keys.join(', ')}) VALUES (${valPlaceholders})`;

            await connection.query(query, valArray);
        }

        console.log('All workshops inserted successfully.');

    } catch (err) {
        console.error('Error:', err);
    } finally {
        await connection.end();
    }
}

main();
