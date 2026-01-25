import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

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
// Enhanced Image keywords mapping
const imageKeywords = {
    'Circ': 'circus,acrobatics,trapeze',
    'Fusta': 'woodworking,carpentry,woodcraft',
    'Cuina': 'cooking,chef,restaurant,kitchen',
    'Metall': 'metalworking,welding,jewelry making',
    'Serigrafia': 'screen printing,textile art',
    'Oficis gastronòmics': 'gourmet food,culinary arts',
    'TMB': 'public transport,bus,subway,mobility',
    'Vela': 'sailing,yachting,sailboat',
    'SmArt': 'graphic design,creative studio,artist',
    'Imatge personal': 'personal styling,makeup artist',
    'Vinyeta': 'comic book art,illustration,drawing',
    'IoT': 'arduino,robotics,electronics',
    'Tecnologia digital': '3d printing,laser cutter,maker',
    'Fem jocs': 'board game design,tabletop games',
    'història': 'archaeology,ancient ruins,history museum',
    'cinema': 'filmmaking,movie camera,film set',
    'teatral': 'theatre play,acting,stage directions',
    'Percussió': 'percussion instruments,drum circle',
    'moda': 'sustainable fashion,sewing,clothing design',
    'rap': 'hip hop culture,music studio,microphone',
    'Tecnologia': 'coding,programming,computer lab',
    'Mur-Art': 'street art,mural painting,graffiti',
    'Retratistes': 'portrait photography,photographer',
    'Intervencions': 'art installation,sculpture',
    'Energies': 'solar panels,wind turbine,green energy',
    'Talent femení': 'women in technology,coding girls',
    'Estètica': 'beauty salon,manicure,skincare',
    'Costura': 'sewing machine,fashion workshop',
    'Instal·lacions': 'electrical wiring,plumbing,tools',
    'Perruqueria': 'hair salon,haircutting,barber',
    'Mecànica': 'bicycle repair,bike mechanic',
    'Xarxes': 'water pipes,infrastructure,engineering',
    'Acompanyament': 'elderly care,social work,healthcare',
    'Jardineria': 'gardening,landscaping,horticulture',
    'Oficis de la mar': 'fishing boat,harbor,maritime',
    'PC': 'computer hardware,pc building,tech',
    'Mobilitat': 'urban cycling,bike path',
    'Picasso': 'picasso art,cubism,painting'
};

function getImageUrl(name, ambit) {
    let keywords = 'workshop,education';
    for (const key in imageKeywords) {
        if (name.includes(key)) {
            keywords = imageKeywords[key];
            break;
        }
    }
    // Use loremflickr from previous logic, but add random param to avoid collisions if multiple have same keyword
    const firstKeyword = keywords.split(',')[0].trim();
    const randomLock = Math.floor(Math.random() * 10000);
    return `https://loremflickr.com/800/600/${encodeURIComponent(firstKeyword)}?lock=${randomLock}`;
}

// Assign course based on modality/intensity logic or random
function determineCourse(modality) {
    // Modality A: Introductory -> 3r ESO, 4t ESO
    // Modality B: Intermediate -> 4t ESO, Batxillerat
    // Modality C: Intensive -> Batxillerat, Cicles Formatius

    // We make it slightly random within sensible bounds
    const coursesA = ['3r ESO', '4t ESO'];
    const coursesB = ['4t ESO', '1r Batxillerat'];
    const coursesC = ['1r Batxillerat', '2n Batxillerat', 'Cicles Formatius'];

    let pool = coursesA;
    if (modality === 'B') pool = coursesB;
    if (modality === 'C') pool = coursesC;

    return pool[Math.floor(Math.random() * pool.length)];
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
        row.push(current.trim());

        if (row[1] && row[1].startsWith('"') && row[1].endsWith('"')) {
            row[1] = row[1].substring(1, row[1].length - 1);
        }
        result.push(row);
    }
    return result;
}

// Categorization Logic
const categoryMap = [
    { keywords: ['Circ', 'Teatre', 'Escènic', 'teatral'], ambit: 'Artístic', theme: 'arts_esceniques' },
    { keywords: ['Fusta', 'Metall', 'Construcció', 'Instal·lacions', 'Mecànica'], ambit: 'Industrial', theme: 'bricolatge_construccio' },
    { keywords: ['Cuina', 'Gastronòmic', 'Alimentació'], ambit: 'Hosteleria', theme: 'gastronomia' },
    { keywords: ['Serigrafia', 'Moda', 'Costura', 'Teixits'], ambit: 'Artístic', theme: 'moda_textil' },
    { keywords: ['TMB', 'Mobilitat', 'Bicicleta', 'Transport', 'Xarxes'], ambit: 'Sostenibilitat', theme: 'mobilitat' },
    { keywords: ['Vela', 'Mar', 'Esport', 'Navegació'], ambit: 'Esport i Lleure', theme: 'esports_nautics' },
    { keywords: ['Imatge', 'Estètica', 'Perruqueria', 'Benestar'], ambit: 'Imatge Personal', theme: 'cura_personal' },
    { keywords: ['Còmic', 'Vinyeta', 'Manga', 'Il·lustració', 'Pintura', 'Picasso', 'Mur-Art', 'Intervencions'], ambit: 'Artístic', theme: 'arts_plastiques' },
    { keywords: ['IoT', 'Tecnologia', 'Digital', 'PC', 'Robòtica', 'Programació', 'Code', 'Tech'], ambit: 'Tecnològic', theme: 'tecnologia' },
    { keywords: ['Energies', 'Renovables', 'Solar'], ambit: 'Sostenibilitat', theme: 'energies_renovables' },
    { keywords: ['Jocs', 'Gamificació'], ambit: 'Esport i Lleure', theme: 'jocs' },
    { keywords: ['Història', 'Arqueologia'], ambit: 'Humanitats', theme: 'historia' },
    { keywords: ['Cinema', 'Audiovisual', 'Foto', 'Retratistes', 'SmArt'], ambit: 'Artístic', theme: 'audiovisual' },
    { keywords: ['Social', 'Acompanyament', 'Educació'], ambit: 'Serveis a la Comunitat', theme: 'social' },
    { keywords: ['Jardineria', 'Verd'], ambit: 'Sostenibilitat', theme: 'jardineria' }
];

function determineCategory(name, rawAmbit) {
    const normalize = (s) => s.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    const nName = normalize(name);
    const nAmbit = normalize(rawAmbit);

    for (const cat of categoryMap) {
        for (const kw of cat.keywords) {
            if (nName.includes(normalize(kw)) || nAmbit.includes(normalize(kw))) {
                return { ambit: cat.ambit, theme: cat.theme };
            }
        }
    }

    // Default fallback
    if (nAmbit.includes('art')) return { ambit: 'Artístic', theme: 'arts_generals' };
    if (nAmbit.includes('tec')) return { ambit: 'Tecnològic', theme: 'tecnologia' };
    if (nAmbit.includes('ind')) return { ambit: 'Industrial', theme: 'industrial' };
    if (nAmbit.includes('oci')) return { ambit: 'Esport i Lleure', theme: 'lleure' };

    return { ambit: 'Altres', theme: 'general' };
}

function generateSQL() {
    const rows = parseCSV(csvData);
    let sql = '-- Auto-generated SQL script to repopulate workshops\n';
    sql += 'USE kairos_db;\n';
    sql += 'SET FOREIGN_KEY_CHECKS = 0;\n';
    sql += 'TRUNCATE TABLE workshops;\n';
    sql += 'SET FOREIGN_KEY_CHECKS = 1;\n\n';

    for (const row of rows) {
        if (row.length < 14) continue;

        const name = row[0].replace(/'/g, "''");
        const description = row[1].replace(/'/g, "''");
        const modality = row[2];
        const rawAmbit = row[3]; // Used only for fallback or reference
        const capacity = parseInt(row[4]);
        const duration_hours = parseInt(row[5]);
        const duration_days = parseInt(row[6]);
        const hours_per_day = parseInt(row[7]);

        let daysStr = row[8];
        let days = [];
        if (daysStr.includes(' i ')) {
            days = daysStr.split(' i ').map(d => d.trim());
        } else {
            days = [daysStr.trim()];
        }
        const allowed_days = JSON.stringify(days).replace(/'/g, "''");

        const time_slots = JSON.stringify([row[9]]).replace(/'/g, "''");

        const start_date = parseDate(row[10]);
        const end_date = parseDate(row[11]);

        const provider_name = row[12].replace(/'/g, "''");
        const provider_contact = row[13].replace(/'/g, "''");
        const is_active = row[14] === 'Sí' ? 1 : 0;

        // Calculate Category & Theme
        const { ambit, theme } = determineCategory(name, rawAmbit);

        const imageUrl = getImageUrl(name, ambit);
        const images = JSON.stringify([imageUrl]).replace(/'/g, "''");

        // Calculate course
        const course = determineCourse(modality);

        // Value string construction
        const valStr = `'${name}', '${description}', '${modality}', ${capacity}, ${capacity}, '${ambit}', ${duration_hours}, ${duration_days}, ${hours_per_day}, '${allowed_days}', '${time_slots}', '${start_date}', '${end_date}', '${provider_name}', '${provider_contact}', ${is_active}, '${images}', '${course}', '${theme}'`;

        sql += `INSERT INTO workshops (name, description, modality, capacity, max_capacity, ambit, duration_hours, duration_days, hours_per_day, allowed_days, time_slots, start_date, end_date, provider_name, provider_contact, is_active, images, course, theme) VALUES (${valStr});\n`;
    }

    return sql;
}

const sqlContent = generateSQL();
const outputPath = path.resolve('../backend/database/repopulate_workshops.sql');
fs.writeFileSync(outputPath, sqlContent);
console.log(`Generated SQL file at ${outputPath}`);
