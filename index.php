<?php
session_start();

// Spotify API-Konfigurationsvariablen
define('CLIENT_ID', '2fe3ce085edc4366a2f227b368baa7e3');
define('CLIENT_SECRET', '6c264ec7f8f44c5ca7b7a62b9e0562f4');
define('REDIRECT_URI', 'http://localhost:8080/spotify-app/index.php?action=callback');
define('AUTH_URL', 'https://accounts.spotify.com/authorize');
define('TOKEN_URL', 'https://accounts.spotify.com/api/token');
define('API_BASE_URL', 'https://api.spotify.com/v1/');

// Startseite mit Login-Link
if (empty($_GET)) {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="./style/main.css">
        <title>Spotify App</title>
    </head>
    <body>
        <h1>Willkommen bei der Spotify App</h1>
        <p><a href="?action=login">Login mit Spotify</a></p>
    </body>
    </html>
    <?php
    exit;
}

// Login: Weiterleitung zur Spotify-Authentifizierung
if ($_GET['action'] === 'login') {
    $scope = 'user-read-private user-read-email playlist-read-private';
    $auth_url = AUTH_URL . '?' . http_build_query([
        'client_id' => CLIENT_ID,
        'response_type' => 'code',
        'scope' => $scope,
        'redirect_uri' => REDIRECT_URI,
        'show_dialog' => true
    ]);
    header("Location: $auth_url");
    exit;
}

// Logout: Sitzung beenden
if ($_GET['action'] === 'logout') {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Callback: Verarbeitet die Rückmeldung von Spotify
if ($_GET['action'] === 'callback') {
    if (isset($_GET['error'])) {
        die(json_encode(["error" => $_GET['error']]));
    }
    
    if (!isset($_GET['code'])) {
        die(json_encode(["error" => "No authorization code provided"]));
    }

    $response = requestToken('authorization_code', $_GET['code']);
    if (!isset($response['access_token'])) {
        die(json_encode(["error" => "Failed to get token", "details" => $response]));
    }

    $_SESSION['access_token'] = $response['access_token'];
    $_SESSION['refresh_token'] = $response['refresh_token'] ?? null;
    $_SESSION['expires_at'] = time() + $response['expires_in'];

    header("Location: ?action=playlists");
    exit;
}

// Playlists abrufen
if ($_GET['action'] === 'playlists') {
    if (!isAuthenticated()) {
        header("Location: ?action=refresh-token");
        exit;
    }

    $playlists = apiRequest('me/playlists');
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="./style/main.css">
        <title>Deine Spotify Playlists</title>
    </head>
    <body>
        <h1>Deine Playlists</h1>
        <ul>
            <?php foreach ($playlists['items'] as $playlist): ?>
                <li>
                    <a href="?action=playlist&id=<?= htmlspecialchars($playlist['id']) ?>">
                        <img src="<?= htmlspecialchars($playlist['images'][0]['url'] ?? 'default.jpg') ?>" alt="Cover" width="100">
                        <?= htmlspecialchars($playlist['name']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <p><a href="?action=logout">Logout</a></p>
    </body>
    </html>
    <?php
    exit;
}

// Einzelne Playlist abrufen
if ($_GET['action'] === 'playlist') {
    if (!isAuthenticated()) {
        header("Location: ?action=refresh-token");
        exit;
    }
    
    if (!isset($_GET['id'])) {
        die(json_encode(["error" => "No playlist ID provided"]));
    }
    
    $playlist = apiRequest('playlists/' . $_GET['id']);
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="./style/main.css">
        <title><?= htmlspecialchars($playlist['name']) ?></title>
    </head>
    <body>
        <h1><?= htmlspecialchars($playlist['name']) ?></h1>
        <img src="<?= htmlspecialchars($playlist['images'][0]['url'] ?? 'default.jpg') ?>" alt="Cover" width="200">
        <ul>
            <?php foreach ($playlist['tracks']['items'] as $track): ?>
                <li>
                    <?= htmlspecialchars($track['track']['name']) ?> – 
                    <?= htmlspecialchars($track['track']['artists'][0]['name']) ?>
                    <?php if (!empty($track['track']['preview_url'])): ?>
                        <br>
                        <audio controls>
                            <source src="<?= htmlspecialchars($track['track']['preview_url']) ?>" type="audio/mpeg">
                            Dein Browser unterstützt kein Audio-Tag.
                        </audio>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <p><a href="?action=playlists">Zurück zu den Playlists</a></p>
    </body>
    </html>
    <?php
    exit;
}

// Token erneuern
if ($_GET['action'] === 'refresh-token') {
    if (!isset($_SESSION['refresh_token'])) {
        header("Location: ?action=login");
        exit;
    }
    
    $response = requestToken('refresh_token', $_SESSION['refresh_token']);
    if (!isset($response['access_token'])) {
        die(json_encode(["error" => "Failed to refresh token", "details" => $response]));
    }
    
    $_SESSION['access_token'] = $response['access_token'];
    $_SESSION['expires_at'] = time() + ($response['expires_in'] ?? 3600);
    
    header("Location: ?action=playlists");
    exit;
}

// Funktion für Token-Anfragen
function requestToken($grantType, $codeOrToken) {
    $postFields = [
        'grant_type' => $grantType,
        'client_id' => CLIENT_ID,
        'client_secret' => CLIENT_SECRET,
    ];
    if ($grantType === 'authorization_code') {
        $postFields['code'] = $codeOrToken;
        $postFields['redirect_uri'] = REDIRECT_URI;
    } else {
        $postFields['refresh_token'] = $codeOrToken;
    }

    $ch = curl_init(TOKEN_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));

    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// Funktion für API-Anfragen
function apiRequest($endpoint) {
    $ch = curl_init(API_BASE_URL . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $_SESSION['access_token']
    ]);

    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// Prüfen, ob der Nutzer authentifiziert ist
function isAuthenticated() {
    return isset($_SESSION['access_token']) && time() < $_SESSION['expires_at'];
}
