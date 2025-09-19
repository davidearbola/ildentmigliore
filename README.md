# Scaffold per Autenticazione SPA con Laravel 11 e Vue 3

Questo progetto è uno "scaffold" (un'impalcatura) di partenza completo e moderno per la creazione di **Single Page Application (SPA)** utilizzando Laravel 11 come backend API e Vue 3 come frontend.

Implementa un flusso di autenticazione robusto e sicuro basato su **Laravel Sanctum** in modalità "stateful" (basata su cookie), che è la soluzione consigliata per le SPA first-party.

## Tecnologie Principali

-   **Backend**: [Laravel 11](https://laravel.com/)
-   **Frontend**: [Vue 3](https://vuejs.org/) (con [Vite](https://vitejs.dev/))
-   **Autenticazione**: [Laravel Sanctum](https://laravel.com/docs/11.x/sanctum) (modalità SPA stateful)
-   **Routing Frontend**: [Vue Router](https://router.vuejs.org/)
-   **Chiamate API**: [Axios](https://axios-http.com/)
-   **Styling**: [Bootstrap 5](https://getbootstrap.com/)
-   **Validazione Frontend**: [VeeValidate](https://vee-validate.logaretm.com/v4/) & [Yup](https://github.com/jquense/yup)
-   **Notifiche**: [Vue Toastification](https://vue-toastification.maronato.dev/)
-   **Database**: MySQL (configurato, ma facilmente modificabile)

## Funzionalità Implementate

Questo scaffold non è solo un login, ma un sistema di autenticazione completo con un'ottima User Experience.

-   ✅ **Registrazione Utente**
-   ✅ **Login Utente**
-   ✅ **Logout Sicuro**
-   ✅ **Protezione delle Rotte**: Le rotte frontend (es. `/dashboard`) sono protette da Navigation Guards e inaccessibili se non si è loggati.
-   ✅ **Gestione Stato Centralizzata**: Lo stato dell'utente (`user`, `isLoading`, etc.) è gestito in modo pulito e riutilizzabile tramite un **Composable** di Vue 3.
-   ✅ **Recupero Password**: Flusso completo di "Password Dimenticata?" che invia un'email con un link sicuro per reimpostare la password.
-   ✅ **Verifica Email**: I nuovi utenti devono verificare il proprio indirizzo email cliccando su un link inviato dopo la registrazione per poter accedere.
-   ✅ **Reinvio Email di Verifica**: Pagina dedicata per permettere agli utenti di richiedere un nuovo invio del link di verifica.
-   ✅ **Funzione "Ricordami"**: Checkbox nel login per mantenere la sessione attiva anche dopo la chiusura del browser.
-   ✅ **Feedback Utente (UX)**:
    -   **Indicatori di Caricamento (Spinner)** sui pulsanti durante le chiamate API.
    -   **Notifiche Toast** per messaggi di successo ed errore (es. "Login effettuato con successo!", "Credenziali errate").
-   ✅ **Validazione Frontend Istantanea**: I form di login e registrazione validano i dati in tempo reale con VeeValidate, fornendo feedback immediato all'utente prima di inviare la richiesta al server.
-   ✅ **Styling Professionale**: L'intera interfaccia è stilata con Bootstrap 5 per un aspetto pulito e responsive.

---

## Prerequisiti

Assicurati di avere installato sulla tua macchina:
-   PHP >= 8.2
-   Composer
-   Node.js e npm
-   Un server di database (es. MySQL, MariaDB)

## Installazione e Avvio

Il progetto è diviso in due cartelle: `backend-api` per Laravel e `frontend-spa` per Vue. Devono essere avviate separatamente in due terminali distinti.

### 1. Clonare il Repository

```bash
# Sostituisci con l'URL del tuo repository GitHub
git clone [URL_DEL_TUO_REPOSITORY]
cd [NOME_DELLA_CARTELLA_CLONATA]
```

### 2. Configurare il Backend (Laravel)

1.  **Naviga nella cartella del backend:**
    ```bash
    cd backend-api
    ```

2.  **Installa le dipendenze PHP:**
    ```bash
    composer install
    ```

3.  **Crea il file di configurazione ambientale:**
    ```bash
    cp .env.example .env
    ```

4.  **Genera la chiave dell'applicazione:**
    ```bash
    php artisan key:generate
    ```

5.  **Configura il database nel file `.env`:**
    Crea un database vuoto sul tuo server MySQL e inserisci le credenziali nel file `.env`. Assicurati anche che `FRONTEND_URL` sia corretto.

    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=laravel_vue_auth # Nome del tuo DB
    DB_USERNAME=root             # Tuo username DB
    DB_PASSWORD=                 # Tua password DB

    FRONTEND_URL=http://localhost:5173
    SESSION_DOMAIN=localhost
    SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173

    # Per testare le email in locale senza un vero server SMTP
    MAIL_MAILER=log
    ```

6.  **Esegui le migrazioni del database:**
    Questo comando creerà le tabelle `users`, `password_reset_tokens`, ecc.
    ```bash
    php artisan migrate
    ```

7.  **Avvia il server di sviluppo di Laravel:**
    ```bash
    php artisan serve
    ```
    Il backend sarà in esecuzione su `http://localhost:8000`.

### 3. Configurare il Frontend (Vue)

1.  **Apri un nuovo terminale** e naviga nella cartella del frontend:
    ```bash
    # Partendo dalla radice del progetto
    cd frontend-spa
    ```

2.  **Installa le dipendenze Javascript:**
    ```bash
    npm install
    ```

3.  **Avvia il server di sviluppo di Vue:**
    ```bash
    npm run dev
    ```
    Il frontend sarà accessibile su `http://localhost:5173`.

---

## Flusso di Test

Una volta avviati entrambi i server, apri `http://localhost:5173` nel tuo browser e prova il flusso completo:
1.  Crea un nuovo account tramite la pagina di registrazione.
2.  Controlla il file di log di Laravel (`backend-api/storage/logs/laravel.log`) per trovare l'email di verifica.
3.  Copia il link di verifica e incollalo nel browser per attivare l'account.
4.  Effettua il login con le credenziali appena verificate.
5.  Prova tutte le altre funzionalità come il logout, il recupero password, etc.