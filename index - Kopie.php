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
if (sizeof($_GET) == 0) {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="./style/main.css">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Anton&family=Atma:wght@300;400;500;600;700&family=Ephesis&family=Funnel+Display:wght@300..800&family=Jua&family=Modak&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

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
if (isset($_GET['action']) && $_GET['action'] == 'login') {
    $scope = 'user-read-private user-read-email playlist-read-private';
    $auth_url = AUTH_URL . "?" . http_build_query([
        'client_id' => CLIENT_ID,
        'response_type' => 'code',
        'scope' => $scope,
        'redirect_uri' => REDIRECT_URI,
        'show_dialog' => true
    ]);
    header("Location: $auth_url");
    exit;
}

// Callback: Verarbeitet die Rückmeldung von Spotify
if (isset($_GET['action']) && $_GET['action'] == 'callback') {
    if (isset($_GET['error'])) {
        echo json_encode(["error" => $_GET['error']]);
        exit;
    }
    
    if (isset($_GET['code'])) {
        $response = requestToken('authorization_code', $_GET['code']);
        if (!isset($response['access_token'])) {
            echo json_encode(["error" => "Failed to get token", "details" => $response]);
            exit;
        }

        $_SESSION['access_token'] = $response['access_token'];
        $_SESSION['refresh_token'] = $response['refresh_token'] ?? null;
        $_SESSION['expires_at'] = time() + $response['expires_in'];
        
        header("Location: ?action=playlists");
        exit;
    }
    echo json_encode(["error" => "No authorization code provided"]);
    exit;
}

// Playlists abrufen
if (isset($_GET['action']) && $_GET['action'] == 'playlists') {
    if (!isset($_SESSION['access_token']) || time() > $_SESSION['expires_at']) {
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

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Anton&family=Atma:wght@300;400;500;600;700&family=Ephesis&family=Funnel+Display:wght@300..800&family=Jua&family=Modak&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

        <title>Deine Spotify Playlists</title>
    </head>
    <body>
        <h1>Deine Playlists</h1>
            <?php foreach ($playlists['items'] as $playlist): ?>
                    <a href="?action=playlist&id=<?= $playlist['id'] ?>">
                        <img src="<?= $playlist['images'][0]['url'] ?? 'default.jpg' ?>" alt="<?= htmlspecialchars($playlist['name']) ?>" width="100">
                        <?= htmlspecialchars($playlist['name']) ?>
                    </a>
            <?php endforeach; ?>
    </body>
    </html>
    <?php
    exit;
}

// Einzelne Playlist abrufen
if (isset($_GET['action']) && $_GET['action'] == 'playlist') {
    if (!isset($_SESSION['access_token']) || time() > $_SESSION['expires_at']) {
        header("Location: ?action=refresh-token");
        exit;
    }

    if (!isset($_GET['id'])) {
        echo json_encode(["error" => "No playlist ID provided"]);
        exit;
    }

    $playlist_id = $_GET['id'];
    $playlist = apiRequest('playlists/' . $playlist_id);
    $tracks = $playlist['tracks']['items'] ?? [];

    if (empty($tracks)) {
        echo "<p>Diese Playlist enthält keine Songs.</p>";
        echo '<p><a href="?action=playlists">Zurück zu den Playlists</a></p>';
        exit;
    }

    // Initialisiere oder aktualisiere den Song-Index
    if (!isset($_SESSION['track_index']) || $_GET['id'] !== ($_SESSION['playlist_id'] ?? '')) {
        $_SESSION['track_index'] = 0;
        $_SESSION['playlist_id'] = $_GET['id'];
    }

    if (isset($_GET['nav']) && $_GET['nav'] == 'next') {
        $_SESSION['track_index'] = ($_SESSION['track_index'] + 1) % count($tracks);
    } elseif (isset($_GET['nav']) && $_GET['nav'] == 'prev') {
        $_SESSION['track_index'] = ($_SESSION['track_index'] - 1 + count($tracks)) % count($tracks);
    }

    $current_track = $tracks[$_SESSION['track_index']]['track'];
    ?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./style/main.css">
    <title><?= htmlspecialchars($playlist['name']) ?></title>

    <script src="./test/card.js"></script>
    <script src="./test/script.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Atma:wght@300;400;500;600;700&family=Ephesis&family=Funnel+Display:wght@300..800&family=Jua&family=Modak&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
</head>
<body>
    <h1 id="logoAll">
        <div class="headlineTop">Beat</div>
        <img src="./Design/Icons/logofafinalj.png" id="logoTop" alt="" srcset="">
        <div class="headlineTop">Buddy</div>
    </h1>

    <!-- Track Container -->
    <div class="track-container">
        <img id="albumCoverIMG" src="<?= $current_track['album']['images'][0]['url'] ?? 'default.jpg' ?>" 
             alt="<?= htmlspecialchars($current_track['name']) ?>" 
             width="200">
        <br>
        <p><strong><?= htmlspecialchars($current_track['name']) ?></strong></p>
        <br>
        <p><?= htmlspecialchars($current_track['artists'][0]['name']) ?></p>
    </div>

    <!-- Controls -->
    <div class="controls">
        <a href="?action=playlist&id=<?= $playlist_id ?>&nav=prev"><img id="redHeart" src="./Design/Icons/HeartRed.png" alt="redHeart" onclick="swipeRight()"></a>
        <a href="?action=playlist&id=<?= $playlist_id ?>&nav=next"><img id="greenHeart" src="./Design/Icons/HeartGreen.png" alt="greenHeart" onclick="swipeLeft()"></a>
    </div>

    <!-- Navigation Link -->
    <p><a href="?action=playlists">Zurück zu den Playlists</a></p>

    <!-- Swiper Container -->
    <div id="swiper"></div>

    <!-- JavaScript Integration -->

</body>
</html>
    <?php
    exit;
}


// Token erneuern
if (isset($_GET['action']) && $_GET['action'] == 'refresh-token') {
    if (!isset($_SESSION['refresh_token'])) {
        header("Location: ?action=login");
        exit;
    }
    
    $response = requestToken('refresh_token', $_SESSION['refresh_token']);
    if (!isset($response['access_token'])) {
        echo json_encode(["error" => "Failed to refresh token", "details" => $response]);
        exit;
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
