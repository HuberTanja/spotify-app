import requests
import urllib.parse
from datetime import datetime
from flask import Flask, redirect, request, jsonify, session

# Flask-Anwendung initialisieren
app = Flask(__name__)
app.secret_key = '53d355f8-571a-4590-a310-1f9579440851'  # Geheimschlüssel für Sitzungen

# Spotify API-Konfigurationsvariablen
CLIENT_ID = '2fe3ce085edc4366a2f227b368baa7e3'  # Client-ID der Spotify-App
CLIENT_SECRET = '6c264ec7f8f44c5ca7b7a62b9e0562f4'  # Client-Secret der Spotify-App
REDIRECT_URI = 'http://localhost:5000/callback'  # URI, auf die nach Authentifizierung weitergeleitet wird

# Spotify API-Endpunkte
AUTH_URL = 'https://accounts.spotify.com/authorize'  # URL für die Benutzeranmeldung
TOKEN_URL = 'https://accounts.spotify.com/api/token'  # URL für den Token-Austausch
API_BASE_URL = 'https://api.spotify.com/v1/'  # Basis-URL für Spotify API-Anfragen

@app.route('/')
def index():
    """
    Startseite mit einem Link zur Spotify-Anmeldung.
    """
    return "Welcome to my Spotify App <a href='/login'>Login with Spotify</a>"

@app.route('/login')
def login():
    """
    Leitet den Benutzer zur Spotify-Anmeldeseite weiter, um eine Autorisierung zu erhalten.
    """
    scope = 'user-read-private user-read-email'  # Zugriffsrechte, die angefordert werden

    params = {
        'client_id': CLIENT_ID,
        'response_type': 'code',  # Antworttyp: Autorisierungs-Code
        'scope': scope,
        'redirect_uri': REDIRECT_URI,
        'show_dialog': True  # Zeige Dialog, auch wenn der Benutzer bereits eingeloggt ist
    }

    auth_url = f"{AUTH_URL}?{urllib.parse.urlencode(params)}"  # Erstelle die vollständige URL
    return redirect(auth_url)  # Leite den Benutzer weiter

@app.route('/callback')
def callback():
    """
    Verarbeitet den Rückruf von Spotify nach der Benutzeranmeldung.
    Tauscht den Autorisierungs-Code gegen einen Access-Token aus.
    """
    if 'error' in request.args:
        # Behandelt den Fall, dass die Autorisierung fehlschlägt
        return jsonify({"error": request.args['error']})
    
    if 'code' in request.args:
        # Tauscht den Autorisierungs-Code gegen einen Access-Token
        req_body = {
            'code': request.args['code'],
            'grant_type': 'authorization_code',
            'redirect_uri': REDIRECT_URI,
            'client_id': CLIENT_ID,
            'client_secret': CLIENT_SECRET
        }
        response = requests.post(TOKEN_URL, data=req_body)
        token_info = response.json()

        if response.status_code != 200:
            return jsonify({"error": "Failed to get token", "details": token_info})

        # Speichert Token-Informationen in der Sitzung
        session['access_token'] = token_info['access_token']
        session['refresh_token'] = token_info.get('refresh_token', None)
        session['expires_at'] = datetime.now().timestamp() + token_info['expires_in']

        return redirect('/playlists')  # Weiterleitung zur Playlist-Seite
    return jsonify({"error": "No authorization code provided"})

@app.route('/playlists')
def get_playlists():
    """
    Ruft die Playlists des angemeldeten Benutzers ab.
    """
    if 'access_token' not in session:
        # Leitet zur Anmeldung weiter, wenn kein Access-Token vorhanden ist
        return redirect('/login')

    if datetime.now().timestamp() > session['expires_at']:
        # Wenn das Token abgelaufen ist, wird es erneuert
        return redirect('/refresh-token')

    headers = {
        'Authorization': f"Bearer {session['access_token']}"  # Authentifizierung mit Access-Token
    }

    # Anfrage an die Spotify API zum Abrufen der Benutzer-Playlists
    response = requests.get(API_BASE_URL + 'me/playlists', headers=headers)
    if response.status_code != 200:
        return jsonify({"error": "Failed to fetch playlists", "details": response.json()})

    playlists = response.json()
    return jsonify(playlists)  # Gibt die Playlists als JSON zurück

@app.route('/refresh-token')
def refresh_token():
    """
    Erneuert das Access-Token, wenn es abgelaufen ist.
    """
    if 'refresh_token' not in session:
        # Wenn kein Refresh-Token vorhanden ist, leitet zur Anmeldung weiter
        return redirect('/login')

    req_body = {
        'grant_type': 'refresh_token',
        'refresh_token': session['refresh_token'],
        'client_id': CLIENT_ID,
        'client_secret': CLIENT_SECRET
    }

    # Anfrage an die Spotify API zum Erneuern des Tokens
    response = requests.post(TOKEN_URL, data=req_body)
    new_token_info = response.json()

    if response.status_code != 200:
        return jsonify({"error": "Failed to refresh token", "details": new_token_info})

    # Aktualisiert den Access-Token und die Ablaufzeit in der Sitzung
    session['access_token'] = new_token_info['access_token']
    session['expires_at'] = datetime.now().timestamp() + new_token_info.get('expires_in', 3600)

    return redirect('/playlists')  # Weiterleitung zur Playlist-Seite

if __name__ == '__main__':
    app.run(host='0.0.0.0', debug=True)  # Startet die Flask-Anwendung
