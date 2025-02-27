require('dotenv').config();
const express = require('express');
const session = require('express-session');
const fetch = require('node-fetch');  // Install via: npm install node-fetch
const { URLSearchParams } = require('url');
import fetch from 'node-fetch';

const app = express();

// Flask-Anwendung initialisieren
app.use(session({
    secret: '53d355f8-571a-4590-a310-1f9579440851',  // Geheimschlüssel für Sitzungen
    resave: false,
    saveUninitialized: true
}));

// Spotify API-Konfigurationsvariablen
const CLIENT_ID = process.env.CLIENT_ID;
const CLIENT_SECRET = process.env.CLIENT_SECRET;
const REDIRECT_URI = process.env.REDIRECT_URI;


// Spotify API-Endpunkte
const AUTH_URL = 'https://accounts.spotify.com/authorize';  // URL für die Benutzeranmeldung
const TOKEN_URL = 'https://accounts.spotify.com/api/token';  // URL für den Token-Austausch
const API_BASE_URL = 'https://api.spotify.com/v1/';  // Basis-URL für Spotify API-Anfragen

app.get('/', (req, res) => {
    /*
    Startseite mit einem Link zur Spotify-Anmeldung.
    */
    res.send("Welcome to my Spotify App <a href='/login'>Login with Spotify</a>");
});

app.get('/login', (req, res) => {
    /*
    Leitet den Benutzer zur Spotify-Anmeldeseite weiter, um eine Autorisierung zu erhalten.
    */
    const scope = 'user-read-private user-read-email';  // Zugriffsrechte, die angefordert werden

    const params = new URLSearchParams({
        client_id: CLIENT_ID,
        response_type: 'code',  // Antworttyp: Autorisierungs-Code
        scope: scope,
        redirect_uri: REDIRECT_URI,
        show_dialog: 'true'  // Zeige Dialog, auch wenn der Benutzer bereits eingeloggt ist
    });

    const auth_url = `${AUTH_URL}?${params.toString()}`;  // Erstelle die vollständige URL
    res.redirect(auth_url);  // Leite den Benutzer weiter
});

app.get('/callback', async (req, res) => {
    /*
    Verarbeitet den Rückruf von Spotify nach der Benutzeranmeldung.
    Tauscht den Autorisierungs-Code gegen einen Access-Token aus.
    */
    if (req.query.error) {
        // Behandelt den Fall, dass die Autorisierung fehlschlägt
        return res.json({ error: req.query.error });
    }
    
    if (req.query.code) {
        // Tauscht den Autorisierungs-Code gegen einen Access-Token
        const req_body = new URLSearchParams({
            code: req.query.code,
            grant_type: 'authorization_code',
            redirect_uri: REDIRECT_URI,
            client_id: CLIENT_ID,
            client_secret: CLIENT_SECRET
        });
        
        const response = await fetch(TOKEN_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: req_body.toString()
        });
        const token_info = await response.json();
        
        if (response.status !== 200) {
            return res.json({ error: "Failed to get token", details: token_info });
        }
        
        // Speichert Token-Informationen in der Sitzung
        req.session.access_token = token_info.access_token;
        req.session.refresh_token = token_info.refresh_token || null;
        req.session.expires_at = (Date.now() / 1000) + token_info.expires_in;
        
        return res.redirect('/playlists');  // Weiterleitung zur Playlist-Seite
    }
    return res.json({ error: "No authorization code provided" });
});

app.get('/playlists', async (req, res) => {
    /*
    Ruft die Playlists des angemeldeten Benutzers ab.
    */
    if (!req.session.access_token) {
        // Leitet zur Anmeldung weiter, wenn kein Access-Token vorhanden ist
        return res.redirect('/login');
    }
    
    if ((Date.now() / 1000) > req.session.expires_at) {
        // Wenn das Token abgelaufen ist, wird es erneuert
        return res.redirect('/refresh-token');
    }
    
    const headers = {
        'Authorization': `Bearer ${req.session.access_token}`  // Authentifizierung mit Access-Token
    };
    
    // Anfrage an die Spotify API zum Abrufen der Benutzer-Playlists
    const response = await fetch(API_BASE_URL + 'me/playlists', { headers });
    if (response.status !== 200) {
        const errorDetails = await response.json();
        return res.json({ error: "Failed to fetch playlists", details: errorDetails });
    }
    
    const playlists = await response.json();
    return res.json(playlists);  // Gibt die Playlists als JSON zurück
});

app.get('/refresh-token', async (req, res) => {
    /*
    Erneuert das Access-Token, wenn es abgelaufen ist.
    */
    if (!req.session.refresh_token) {
        // Wenn kein Refresh-Token vorhanden ist, leitet zur Anmeldung weiter
        return res.redirect('/login');
    }
    
    const req_body = new URLSearchParams({
        grant_type: 'refresh_token',
        refresh_token: req.session.refresh_token,
        client_id: CLIENT_ID,
        client_secret: CLIENT_SECRET
    });
    
    // Anfrage an die Spotify API zum Erneuern des Tokens
    const response = await fetch(TOKEN_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: req_body.toString()
    });
    
    const new_token_info = await response.json();
    
    if (response.status !== 200) {
        return res.json({ error: "Failed to refresh token", details: new_token_info });
    }
    
    // Aktualisiert den Access-Token und die Ablaufzeit in der Sitzung
    req.session.access_token = new_token_info.access_token;
    req.session.expires_at = (Date.now() / 1000) + (new_token_info.expires_in || 3600);
    
    return res.redirect('/playlists');  // Weiterleitung zur Playlist-Seite
});

// Startet die Flask-Anwendung
const PORT = 5000;
app.listen(PORT, '0.0.0.0', () => {
    console.log(`Server running on http://0.0.0.0:${PORT}`);
});
