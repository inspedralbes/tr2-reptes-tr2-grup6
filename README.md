# KAIROS - Sistema de Gestió Programa ENGINY

---

## ℹ️ Informació del Projecte

- **Nom del Projecte**: KAIROS
- **Nom dels integrants**: Bryan Ruzafa, Aaron Soriano, Marcos Suarez, Pol Dones (Grup 6)
- **Petita descripció**: Plataforma integral per a la gestió del Programa ENGINY, connectant centres educatius amb tallers vocacionals. Gestiona tot el cicle: sol·licitud, assignació algorítmica, calendarització en temps real i feedback amb anàlisi de dades.
- **Adreça del gestor de tasques (Taiga)**: [Veure Projecte](https://tree.taiga.io/project/aaronsoripon8-tr2-grup-6/timeline)
- **Adreça del prototip gràfic (Figma)**: [Veure Disseny](https://www.figma.com/design/LSPXnmGoXrr2HUM5ZoOwGL/Untitled?node-id=7-2&t=sbQe0aoMzm88K78h-1)
- **URL de producció**: [kairos.daw.inspedralbes.cat](http://kairos.daw.inspedralbes.cat)
- **Estat**: 🟢 **Acabat** (Versió 2.0.0). El sistema està completament operatiu amb totes les funcionalitats principals implementades: algorisme d'assignació, reserves en temps real, sistema de feedback premium i tauler d'administració.

### 🔗 Altres Enllaços
- **Repositori de Codi (GitHub)**: [Veure Repositori](https://github.com/inspedralbes/tr2-reptes-tr2-grup6)

---

> **"L'encaix perfecte entre centre i taller."**

**KAIROS** és una plataforma integral dissenyada per gestionar el **Programa ENGINY**, una iniciativa del Consorci d'Educació de Barcelona. Connecta centres educatius amb tallers vocacionals per ajudar els estudiants a explorar camins professionals i reduir l'abandonament escolar.

---

## 🌟 Funcionalitats Clau

### 1. 🔄 El Procés d'Assignació en 4 Fases
El sistema automatitza la lògica complexa d'assignar milers d'estudiants a tallers:
- **Fase 1: Sol·licitud** - Els centres sol·liciten els tallers que els interessen.
- **Fase 2: Assignació** - Un algorisme intel·ligent assigna tallers basant-se en l'històric, capacitat i equitat.
- **Fase 3: Calendarització** - Els docents reserven dates específiques en temps real.
- **Fase 4: Feedback** - Avaluació posterior al taller.

### 2. 💎 Experiència de Feedback Premium
Hem renovat completament el sistema de feedback per assegurar una alta participació:
- **"Total Facelift"**: Una estètica neta, blanca i daurada que sembla professional i moderna.
- **Valoracions Interactives**: Estrelles fàcils d'utilitzar per valorar contingut, docent i organització.
- **Responsive Mòbil**: Funciona perfectament en tauletes i mòbils per a avaluacions in situ.

### 3. 👥 Registre d'Alumnes i Assistència
- **Entrada Manual**: Els docents poden registrar els assistents directament al formulari de feedback.
- **Pujada CSV**: Un botó nou permet importar noms massivament utilitzant un fitxer `.csv` o `.txt` (Un nom per línia).
- **Seguiment d'Assistència**: Compara assistents "Esperats" vs "Reals" per a informes precisos.

### 4. 📊 Tauler d'Administració
Un tauler potent per als administradors per supervisar tot el programa:
- **Monitor de Feedback**: Veure totes les valoracions enviades en temps real.
- **Estadístiques d'Assignació**: Seguiment de quants tallers s'han assignat i programat.
- **Gestió de Centres i Docents**: Gestionar comptes d'usuari i permisos.

---

## 🏗️ Estructura del Projecte

El projecte segueix una arquitectura neta separant responsabilitats:

```
kairos/
├── backend/                   # API REST (PHP 8.1 + MySQL)
│   ├── app/Controllers/       # Lògica per Feedback, Assignacions, Usuaris
│   ├── config/                # Configuració de Base de Dades i Entorn
│   └── public/                # Punt d'entrada (index.php)
├── frontend/                  # SPA (Vue 3 + Pinia)
│   ├── src/views/             # Pàgines d'Admin, Centre, i Feedback
│   ├── src/components/        # Components UI reutilitzables
│   └── src/stores/            # Gestió d'estat
├── docker/                    # Configuració de Docker
└── docs/                      # Documentació tècnica
```

---

## 🚀 Guia d'Inici Ràpid

### Requisits Previs
- **Docker** i **Docker Compose**
- **Node.js 18+** (per al desenvolupament frontend)

### Instal·lació

1.  **Clonar el repositori**
    ```bash
    git clone <url-del-repo> kairos
    cd kairos
    ```

2.  **Configuració de l'Entorn**
    ```bash
    cp .env.example .env
    # Edita .env amb les teves credencials de base de dades si és necessari
    ```

3.  **Iniciar Serveis (Docker)**
    ```bash
    docker-compose up -d
    ```

4.  **Inicialitzar Base de Dades**
    L'script `init.sql` normalment s'executa automàticament. Si no:
    ```bash
    docker-compose exec db mysql -u root -p < backend/database/schema.sql
    ```

5.  **Executar Frontend (Desenvolupament)**
    ```bash
    cd frontend
    npm install
    npm run dev
    ```

Visita `http://localhost:5173` per accedir a l'aplicació.

---

## 🔧 Tecnologies

- **Frontend**: Vue.js 3, Pinia, Vue Router, Vanilla CSS (Sistema de Disseny Personalitzat).
- **Backend**: PHP 8.1 Natiu (Framework MVC propi), PDO.
- **Base de Dades**: MySQL 8.0.
- **Infraestructura**: Docker, Nginx.

---

## 👥 Rols d'Usuari

| Rol | Responsabilitats |
|-----|------------------|
| **Administrador** | Gestionar catàleg, executar algorisme, veure estadístiques globals i feedback. |
| **Centre** | Sol·licitar tallers, veure places assignades. |
| **Docent** | Reservar dates, enviar feedback del taller, registrar alumnes. |

---

## 📞 Suport

Per a suport tècnic o sol·licituds de funcionalitats, si us plau contacteu amb l'equip de desenvolupament.

**Versió**: 2.0.0
**Última Actualització**: Gener 2026
