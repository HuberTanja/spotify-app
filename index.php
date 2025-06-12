<?php
session_start();

// Spotify API-Konfigurationsvariablen
define('CLIENT_ID', '2fe3ce085edc4366a2f227b368baa7e3');
define('CLIENT_SECRET', '6c264ec7f8f44c5ca7b7a62b9e0562f4');
define('REDIRECT_URI', 'http://localhost:8080/spotify-app/index.php?action=callback');
define('AUTH_URL', 'https://accounts.spotify.com/authorize');
define('TOKEN_URL', 'https://accounts.spotify.com/api/token');
define('API_BASE_URL', 'https://api.spotify.com/v1/');


// --- Hilfsfunktionen ---
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

function apiPostRequest($url, $data) {
    $ch = curl_init(API_BASE_URL . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $_SESSION['access_token'],
        'Content-Type: application/json'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
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
        <h1 id="logoAll">
            <div class="headlineTop">Beat</div>
            <img src="./Design/Icons/logofafinalj.png" id="logoTop" alt="" srcset="">
            <div class="headlineTop">Buddy</div>
        </h1>
        <p><a href="?action=login">Login mit Spotify</a></p>

    </body>
    </html>
    <?php
    exit;
}

// Login: Weiterleitung zur Spotify-Authentifizierung
if (isset($_GET['action']) && $_GET['action'] == 'login') {
 //$scope = 'user-read-private user-read-email playlist-read-private';
 $scope = 'playlist-modify-private playlist-modify-public user-read-private user-read-email playlist-read-private';
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
    foreach ($playlists['items'] as $playlist) {
        if (strtolower($playlist['name']) === 'all genres, all decades') {
            $playlistId = $playlist['id'];
            header("Location: ?action=playlist&id=" . urlencode($playlistId));
            exit;
        }
    }

    ?>
    <!DOCTYPE html>
    < lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="./style/main.css">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Anton&family=Atma:wght@300;400;500;600;700&family=Ephesis&family=Funnel+Display:wght@300..800&family=Jua&family=Modak&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

        <title>Deine Spotify Playlists</title>
    </head>
    <>
     
         <h1>Deine Playlists</h1>
        <?php foreach ($playlists['items'] as $playlist): ?>
            <a href="?action=playlist&id=<?= $playlist['id'] ?>">
                <img src="<?= $playlist['images'][0]['url'] ?? 'default.jpg' ?>" alt="<?= htmlspecialchars($playlist['name']) ?>" width="100">
                <?= htmlspecialchars($playlist['name']) ?>
            </a>
        <?php endforeach; ?>
        <button onclick="window.location.href='./PHP/logout.php'">Logout</button>

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

    

     function getAudioFeatures($track_id) {
         if (!isset($_SESSION['access_token'])) {
             return ["error" => "No access token"];
        }
    
         $url = API_BASE_URL . "audio-features/" . $track_id;
    
         $ch = curl_init($url);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
             "Authorization: Bearer " . $_SESSION['access_token']
         ]);
    
         $response = curl_exec($ch);
         curl_close($ch);
    
         return json_decode($response, true);
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

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Anton&family=Atma:wght@300;400;500;600;700&family=Ephesis&family=Funnel+Display:wght@300..800&family=Jua&family=Modak&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    </head>
    <body>
        <img class="backimg" id="backimg1" src="./Design/bgIMG/back1.png" alt="" srcset="">
        <img class="backimg" id="backimg2" src="./Design/bgIMG/back2.png" alt="" srcset="">
        <img class="backimg" id="backimg3" src="./Design/bgIMG/back3.png" alt="" srcset="">
        <img class="backimg" id="backimg4" src="./Design/bgIMG/back4.png" alt="" srcset="">
        <img class="backimg" id="backimg5" src="./Design/bgIMG/back5.png" alt="" srcset="">

        <h1 id="logoAll">
            <div class="headlineTop">Beat</div>
            <img src="./Design/Icons/logofafinalj.png" id="logoTop" alt="" srcset="">
            <div class="headlineTop">Buddy</div>
        </h1>

<script>
function addToBeatBuddy(trackId) {
    fetch('?action=add-to-playlist&track_id=' + trackId, {
        headers: { 'X-Requested-With': 'fetch' }
    })
    .then(res => res.json())
    .then(data => {
        const statusEl = document.getElementById('add-status-' + trackId);
        if (data.success) {
            statusEl.innerText = "✅ Song wurde hinzugefügt!";
        } else {
            statusEl.innerText = "❌ Fehler: " + data.message;
        }
    })
    .catch(error => {
        const statusEl = document.getElementById('add-status-' + trackId);
        statusEl.innerText = "❌ Netzwerkfehler.";
        console.error("Fehler:", error);
    });
}
</script>

        <!-- Track Container - Entire Swipe Mechanism -->
            <div class="track-container" id="trackBox">
                <img id="albumCoverIMG" src="<?= $current_track['album']['images'][0]['url'] ?? 'default.jpg' ?>" 
                    alt="<?= htmlspecialchars($current_track['name']) ?>" 
                    width="200">
                
                
                <iframe src="https://open.spotify.com/embed/track/<?= $current_track['id'] ?>?autoplay=1"
                        width="300" height="80" frameborder="0" allowtransparency="true" allow="encrypted-media"></iframe>
            </div>

            <!-- Buttons zum Swipen -->
             <div class="controls">
                <a href="?action=playlist&id=<?= $playlist_id ?>&nav=next"><img id="redHeart" src="./Design/Icons/HeartRed.png" alt="redHeart"></a>
                <a href="?action=playlist&id=<?= $playlist_id ?>&nav=next"><img id="greenHeart" src="./Design/Icons/HeartGreen.png" alt="greenHeart"></a>
            </div>

            <script>
            const trackBox = document.getElementById('trackBox');
            const redHeart = document.getElementById('redHeart');
            const greenHeart = document.getElementById('greenHeart');
            const playlistId = "<?= htmlspecialchars($_GET['id']) ?>"; // aktuelle Playlist-ID

            // ---- SWIPE MECHANISMUS ----
            let isDragging = false;
            let startX = 0;
            let currentX = 0;

            function setTransform(x) {
                trackBox.style.transform = `translateX(${x}px) rotate(${x/20}deg)`;
                trackBox.style.opacity = 1 - Math.min(Math.abs(x)/400, 0.7);
            }
            // Nach Animation: Weiter zum nächsten Song
            function goToNext(nav) {
                setTimeout(() => {
                    window.location.href = "?action=playlist&id=" + playlistId + "&nav=" + nav;
                }, 400); // muss zur CSS-Transition passen
            }
            // Maus
            trackBox.addEventListener('mousedown', (e) => {
                isDragging = true;
                startX = e.clientX;
                trackBox.style.transition = 'none';
            });

            document.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                currentX = e.clientX - startX;
                setTransform(currentX);
            });

            document.addEventListener('mouseup', (e) => {
            if (!isDragging) return;
            isDragging = false;
            trackBox.style.transition = '';
            if (currentX < -120) {
                trackBox.classList.add('swipe-out-left');
                goToNext('next');
            } else if (currentX > 120) {
                addToBeatBuddy('<?= $current_track['id'] ?>');
                trackBox.classList.add('swipe-out-right');
                goToNext('next');
            }
 else {
                trackBox.style.transform = '';
                trackBox.style.opacity = '';
            }
            currentX = 0;
            });

            // Touch
            trackBox.addEventListener('touchstart', (e) => {
                isDragging = true;
                startX = e.touches[0].clientX;
                trackBox.style.transition = 'none';
            });
            trackBox.addEventListener('touchmove', (e) => {
                if (!isDragging) return;
                currentX = e.touches[0].clientX - startX;
                setTransform(currentX);
            });
            trackBox.addEventListener('touchend', (e) => {
            if (!isDragging) return;
            isDragging = false;
            trackBox.style.transition = '';
            if (currentX < -120) {
                trackBox.classList.add('swipe-out-left');
                goToNext('next');
            } else if (currentX > 120) {
                addToBeatBuddy('<?= $current_track['id'] ?>'); // call your function
                trackBox.classList.add('swipe-out-right');
                goToNext('next');
            } else {
                trackBox.style.transform = '';
                trackBox.style.opacity = '';
            }
            currentX = 0;
            });

            // BUTTONS
            redHeart.addEventListener('click', () => {
                trackBox.classList.add('swipe-out-left');
                goToNext('next');
            });
          greenHeart.addEventListener('click', () => {
                addToBeatBuddy('<?= $current_track['id'] ?>'); // call your function
                trackBox.classList.add('swipe-out-right');
                goToNext('next');
            });

        </script>

        
        <p><a href="?action=playlists" style="display: none;">Zurück zu den Playlists</a></p>
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

if (isset($_GET['action']) && $_GET['action'] == 'add-to-playlist') {
    // Prüfen ob es ein fetch()-Request ist (AJAX)
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'fetch';

    if (!isset($_SESSION['access_token']) || time() > $_SESSION['expires_at']) {
        if ($is_ajax) {
            echo json_encode(["success" => false, "message" => "Zugriff abgelaufen"]);
        } else {
            header("Location: ?action=refresh-token");
        }
        exit;
    }

    if (!isset($_GET['track_id'])) {
        $msg = "Kein Track angegeben.";
        if ($is_ajax) {
            echo json_encode(["success" => false, "message" => $msg]);
        } else {
            echo "<p>$msg</p>";
        }
        exit;
    }

    $track_id = $_GET['track_id'];
    $track_uri = "spotify:track:" . $track_id;

    // 1. Playlist suchen
    $playlists = apiRequest('me/playlists');
    $playlist_id = null;
    foreach ($playlists['items'] as $playlist) {
        if ($playlist['name'] === 'BeatBuddy') {
            $playlist_id = $playlist['id'];
            break;
        }
    }

    // 2. Playlist erstellen falls nötig
    if (!$playlist_id) {
        $user = apiRequest('me');
        $create = apiPostRequest("users/{$user['id']}/playlists", [
            "name" => "BeatBuddy",
            "description" => "Erstellt durch deine App",
            "public" => false
        ]);
        if (isset($create['id'])) {
            $playlist_id = $create['id'];
        } else {
            $msg = "Playlist konnte nicht erstellt werden.";
            if ($is_ajax) {
                echo json_encode(["success" => false, "message" => $msg]);
            } else {
                echo "<p>$msg</p>";
            }
            exit;
        }
    }

    // 3. Track hinzufügen
    $result = apiPostRequest("playlists/{$playlist_id}/tracks", [
        "uris" => [$track_uri],
        "position" => 0
    ]);

    if (isset($result['snapshot_id'])) {
        if ($is_ajax) {
            echo json_encode(["success" => true]);
        } else {
            echo "<p>✅ Song wurde hinzugefügt!</p>";
            echo '<p><a href="?action=playlists">Zurück</a></p>';
        }
    } else {
        $msg = "Fehler beim Hinzufügen.";
        if ($is_ajax) {
            echo json_encode(["success" => false, "message" => $msg]);
        } else {
            echo "<p>$msg</p>";
        }
    }

    exit;
}

