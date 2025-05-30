<?php
// Error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'proxy_errors.log');

// Proxy handler
if (isset($_GET['proxy'])) {
    $url = urldecode($_GET['proxy']);
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid URL']);
        error_log("Invalid URL: $url");
        exit;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER => false,
        CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'] ?? 'AmazingBrowser/1.0',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_ENCODING => 'gzip, deflate',
        CURLOPT_COOKIEFILE => 'cookies.txt',
        CURLOPT_COOKIEJAR => 'cookies.txt',
        CURLOPT_BINARYTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Connection: keep-alive',
        ],
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        http_response_code(502);
        header('Content-Type: application/json');
        echo json_encode(['error' => "Proxy error: $error"]);
        error_log("Proxy error for $url: $error");
        curl_close($ch);
        exit;
    }

    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'text/html';
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 400) {
        http_response_code($http_code);
        header('Content-Type: application/json');
        echo json_encode(['error' => "HTTP error: $http_code"]);
        error_log("HTTP error $http_code for $url");
        exit;
    }

    if (stripos($content_type, 'text/html') !== false) {
        $proxy_base = $_SERVER['PHP_SELF'] . '?proxy=';
        $ws_proxy = 'ws://your-node-app.onrender.com'; // Replace with actual WebSocket URL
        $response = preg_replace(
            '/(href|src|action)=[\'"](?!http|data:)([^\'"]+)[\'"]/i',
            '$1="' . $proxy_base . '$2"',
            $response
        );
        $response = preg_replace(
            '/(WebSocket\s*\(\s*[\'"])(ws[s]?):\/\//i',
            '$1' . $ws_proxy . '/',
            $response
        );
    }

    header("Content-Type: $content_type");
    echo $response;
    exit;
}

// Page and games
$page = $_GET['page'] ?? 'home';
$genres = [
    'Action' => [
        ['title' => 'Slither.io', 'url' => 'https://slither.io/', 'thumb' => 'https://upload.wikimedia.org/wikipedia/en/7/7a/Slither.io_logo.png'],
        ['title' => 'Krunker.io', 'url' => 'https://krunker.io/', 'thumb' => 'https://krunker.io/img/favicon.png'],
        ['title' => 'Shell Shockers', 'url' => 'https://shellshock.io/', 'thumb' => 'https://shellshock.io/favicon.ico'],
        // Add more (total 65 per genre)
    ],
    'Puzzle' => [
        ['title' => '2048', 'url' => 'https://play2048.co/', 'thumb' => 'https://play2048.co/favicon.ico'],
        ['title' => 'Sudoku', 'url' => 'https://www.websudoku.com/', 'thumb' => 'https://www.websudoku.com/favicon.ico'],
        ['title' => 'Cut the Rope', 'url' => 'https://www.cuttherope.net/', 'thumb' => 'https://www.cuttherope.net/favicon.ico'],
        // Add more
    ],
    'Multiplayer' => [
        ['title' => 'Agar.io', 'url' => 'https://agar.io/', 'thumb' => 'https://upload.wikimedia.org/wikipedia/en/3/3a/Agar.io_logo.png'],
        ['title' => 'Diep.io', 'url' => 'https://diep.io/', 'thumb' => 'https://diep.io/favicon.ico'],
        ['title' => 'Skribbl.io', 'url' => 'https://skribbl.io/', 'thumb' => 'https://skribbl.io/favicon.ico'],
        // Add more
    ],
    'Strategy' => [
        ['title' => 'Bloons TD 5', 'url' => 'https://bloons-tower-defense-5.com/', 'thumb' => 'https://bloons-tower-defense-5.com/favicon.ico'],
        ['title' => 'Kingdom Rush', 'url' => 'https://www.kingdomrush.com/', 'thumb' => 'https://www.kingdomrush.com/favicon.ico'],
        // Add more
    ],
    'Retro' => [
        ['title' => 'Pac-Man', 'url' => 'https://www.pacman1.net/', 'thumb' => 'https://www.pacman1.net/favicon.ico'],
        ['title' => 'Tetris', 'url' => 'https://tetris.com/play-tetris/', 'thumb' => 'https://tetris.com/favicon.ico'],
        // Add more
    ],
    'Sports' => [
        ['title' => 'Basketball Stars', 'url' => 'https://basketballstarsunblocked.io/', 'thumb' => 'https://basketballstarsunblocked.io/favicon.ico'],
        ['title' => 'Soccer Physics', 'url' => 'https://soccerphysics.net/', 'thumb' => 'https://soccerphysics.net/favicon.ico'],
        ['title' => 'Retro Bowl', 'url' => 'https://retrobowl.me/', 'thumb' => 'https://retrobowl.me/favicon.ico'],
        // Add more
    ],
    'Racing' => [
        ['title' => 'Madalin Stunt Cars 2', 'url' => 'https://madalinstuntcars2.io/', 'thumb' => 'https://madalinstuntcars2.io/favicon.ico'],
        ['title' => 'Drift Hunters', 'url' => 'https://drifthunters.com/', 'thumb' => 'https://drifthunters.com/favicon.ico'],
        ['title' => 'Moto X3M', 'url' => 'https://motox3m.io/', 'thumb' => 'https://motox3m.io/favicon.ico'],
        // Add more
    ],
    'Adventure' => [
        ['title' => 'Fireboy and Watergirl', 'url' => 'https://fireboyandwatergirl.io/', 'thumb' => 'https://fireboyandwatergirl.io/favicon.ico'],
        ['title' => 'Run 3', 'url' => 'https://run3.io/', 'thumb' => 'https://run3.io/favicon.ico'],
        ['title' => 'Vex 5', 'url' => 'https://vex5.io/', 'thumb' => 'https://vex5.io/favicon.ico'],
        // Add more
    ],
];

// Expand to 520 games (8 genres × 65 games each)
foreach ($genres as &$genre) {
    while (count($genre) < 65) {
        $count = count($genre) + 1;
        $genre[] = [
            'title' => "Game $count",
            'url' => "https://example.com/game$count",
            'thumb' => 'https://via.placeholder.com/150?text=Game+' . $count
        ];
    }
}
unset($genre);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Amazing Browser</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body, html {
            height: 100%;
            font-family: 'Orbitron', sans-serif;
            display: flex;
            background: #000;
            color: #d500f9;
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
        }
        /* Purple particle background */
        .star {
            position: absolute;
            width: 5px;
            height: 5px;
            background: #d500f9;
            border-radius: 50%;
            animation: float 5s infinite;
            box-shadow: 0 0 10px #d500f9;
        }
        @keyframes float {
            0% { transform: translateY(0); opacity: 1; }
            100% { transform: translateY(-100vh); opacity: 0; }
        }
        /* Theme styles */
        body.dark {
            background: #1a1a1a;
            color: #fff;
        }
        body.dark #sidebar { background: #222; }
        body.dark nav a:hover, body.dark nav a.active { background: #444; }
        body.dark #address-bar { background: #333; }
        body.dark .genre-btn { background: #333; }
        body.dark .genre-btn:hover { background: #555; }
        body.dark .game-card { background: #333; }
        body.light {
            background: #f0f0f0;
            color: #000;
        }
        body.light #sidebar { background: #ddd; }
        body.light nav a { color: #000; }
        body.light nav a:hover, body.light nav a.active { background: #ccc; }
        body.light #address-bar { background: #e0e0e0; }
        body.light #address-input { color: #000; }
        body.light .genre-btn { background: #e0e0e0; color: #000; }
        body.light .genre-btn:hover { background: #d0d0d0; }
        body.light .game-card { background: #e0e0e0; }
        body.neon {
            background: #000;
            color: #d500f9;
        }
        body.neon #sidebar { background: #111; }
        body.neon nav a { color: #d500f9; }
        body.neon nav a:hover, body.neon nav a.active { background: #333; }
        body.neon #address-bar { background: #222; }
        body.neon #address-input { color: #d500f9; }
        body.neon .genre-btn { background: #222; color: #d500f9; }
        body.neon .genre-btn:hover { background: #444; }
        body.neon .game-card { background: #222; }
        body.custom { background-size: cover; background-position: center; }
        /* Layout */
        #sidebar {
            width: 60px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 20px;
            background: #111;
            transition: width 0.3s;
            z-index: 10;
        }
        #sidebar.wide { width: 100px; }
        #sidebar.narrow { width: 40px; }
        nav a {
            padding: 1rem;
            color: inherit;
            text-decoration: none;
            font-size: 0.7rem;
            width: 100%;
            text-align: center;
            text-shadow: 0 0 5px #d500f9;
            transition: background 0.2s;
        }
        nav a:hover, nav a.active {
            background: #222;
        }
        nav a i { font-size: 1.5rem; margin-bottom: 0.2rem; display: block; }
        main {
            margin-left: 60px;
            flex: 1;
            padding: 1rem;
            height: 100vh;
            overflow-y: auto;
            transition: margin-left 0.3s;
            z-index: 5;
        }
        main.wide { margin-left: 100px; }
        main.narrow { margin-left: 40px; }
        #home-section { text-align: center; padding: 2rem 0; }
        #home-section h1 { font-size: 2.5rem; text-shadow: 0 0 15px #d500f9; }
        #home-section p { font-size: 1.2rem; opacity: 0.8; text-shadow: 0 0 5px #d500f9; }
        #browser-section { display: flex; flex-direction: column; gap: 0.5rem; }
        #address-bar {
            display: flex;
            border-radius: 20px;
            background: #222;
            box-shadow: 0 0 10px #d500f9;
        }
        #address-input {
            flex: 1;
            padding: 0.5rem;
            border: none;
            background: transparent;
            font-size: 1rem;
            outline: none;
            color: #d500f9;
            text-shadow: 0 0 5px #d500f9;
        }
        #go-btn {
            background: #d500f9;
            border: none;
            padding: 0 1rem;
            cursor: pointer;
            color: #000;
            border-radius: 20px;
            font-weight: bold;
            box-shadow: 0 0 10px #d500f9;
        }
        #go-btn:hover { background: #b000d5; }
        #iframe-container {
            height: 90vh;
            border-radius: 5px;
            background: #111;
            box-shadow: 0 0 15px #d500f9 inset;
        }
        #iframe-container iframe { width: 100%; height: 100%; border: none; }
        #loading { display: none; text-align: center; padding: 1rem; color: #d500f9; text-shadow: 0 0 5px #d500f9; }
        #error { display: none; text-align: center; padding: 0.5rem; color: #ff4d4d; text-shadow: 0 0 5px #ff4d4d; }
        #games-section { display: flex; flex-direction: column; gap: 1rem; }
        #genres { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .genre-btn {
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 15px;
            cursor: pointer;
            transition: background 0.2s;
            background: #222;
            color: #d500f9;
            box-shadow: 0 0 10px #d500f9;
            text-shadow: 0 0 5px #d500f9;
        }
        .genre-btn:hover { background: #333; }
        .genre-btn.active { background: #d500f9; color: #000; text-shadow: none; }
        #game-grid {
            display: grid;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        #game-grid.small { grid-template-columns: repeat(5, 1fr); }
        #game-grid.medium { grid-template-columns: repeat(4, 1fr); }
        #game-grid.large { grid-template-columns: repeat(3, 1fr); }
        .game-card {
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.2s;
            background: #222;
            box-shadow: 0 0 10px #d500f9;
        }
        .game-card:hover { transform: scale(1.05); }
        .game-card img {
            width: 100%;
            object-fit: cover;
            border-radius: 5px 5px 0 0;
        }
        .game-card.small img { height: 80px; }
        .game-card.medium img { height: 100px; }
        .game-card.large img { height: 120px; }
        .game-card div {
            padding: 0.5rem;
            text-align: center;
            font-size: 0.8rem;
            color: #d500f9;
            text-shadow: 0 0 5px #d500f9;
        }
        /* Settings */
        #settings-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1001;
            justify-content: center;
            align-items: center;
        }
        #settings-modal.show { display: flex; }
        #settings-content {
            background: #222;
            padding: 2rem;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            color: #d500f9;
            box-shadow: 0 0 20px #d500f9;
        }
        #settings-content h2 { font-size: 1.5rem; margin-bottom: 1rem; text-shadow: 0 0 10px #d500f9; }
        .settings-group { margin-bottom: 1.5rem; }
        .settings-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            text-shadow: 0 0 5px #d500f9;
        }
        .settings-group select, .settings-group input[type="file"] {
            width: 100%;
            padding: 0.5rem;
            border-radius: 5px;
            border: none;
            background: #333;
            color: #d500f9;
            box-shadow: 0 0 5px #d500f9 inset;
        }
        #settings-content button {
            background: #d500f9;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            color: #000;
            font-weight: bold;
            margin-top: 1rem;
            box-shadow: 0 0 10px #d500f9;
        }
        #settings-content button:hover { background: #b000d5; }
    </style>
</head>
<body class="neon">
    <div id="sidebar">
        <nav>
            <a href="?page=home" class="<?= $page === 'home' ? 'active' : '' ?>"><i class="fas fa-home"></i> Home</a>
            <a href="?page=browser" class="<?= $page === 'browser' ? 'active' : '' ?>"><i class="fas fa-globe"></i> Browser</a>
            <a href="?page=games" class="<?= $page === 'games' ? 'active' : '' ?>"><i class="fas fa-gamepad"></i> Games</a>
            <a href="#" id="settings-btn"><i class="fas fa-cog"></i> Settings</a>
        </nav>
    </div>
    <main>
        <?php if ($page === 'home'): ?>
            <section id="home-section">
                <h1>Amazing Browser</h1>
                <p>Browse any site and play 500+ games!</p>
            </section>
        <?php elseif ($page === 'browser'): ?>
            <section id="browser-section">
                <div id="address-bar">
                    <input type="text" id="address-input" placeholder="URL or search">
                    <button id="go-btn"><i class="fas fa-arrow-right"></i></button>
                </div>
                <div id="error"></div>
                <div id="iframe-container">
                    <div id="loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
                </div>
            </section>
        <?php elseif ($page === 'games'): ?>
            <section id="games-section">
                <div id="genres">
                    <?php foreach (array_keys($genres) as $genre): ?>
                        <button class="genre-btn" data-genre="<?= htmlspecialchars($genre) ?>"><?= htmlspecialchars($genre) ?></button>
                    <?php endforeach; ?>
                </div>
                <div id="game-grid"></div>
            </section>
        <?php endif; ?>
    </main>
    <div id="settings-modal">
        <div id="settings-content">
            <h2>Settings</h2>
            <div class="settings-group">
                <label>Theme</label>
                <select id="theme-select">
                    <option value="neon">Neon (Default)</option>
                    <option value="dark">Dark</option>
                    <option value="light">Light</option>
                    <option value="custom">Custom Wallpaper</option>
                </select>
                <input type="file" id="wallpaper-upload" accept="image/*" style="display: none;">
            </div>
            <div class="settings-group">
                <label>Font Size</label>
                <select id="font-size">
                    <option value="small">Small</option>
                    <option value="medium">Medium (Default)</option>
                    <option value="large">Large</option>
                </select>
            </div>
            <div class="settings-group">
                <label>Sidebar Width</label>
                <select id="sidebar-width">
                    <option value="narrow">Narrow</option>
                    <option value="medium">Medium (Default)</option>
                    <option value="wide">Wide</option>
                </select>
            </div>
            <div class="settings-group">
                <label>Game Grid Layout</label>
                <select id="grid-size">
                    <option value="small">Small Cards (5 columns)</option>
                    <option value="medium">Medium Cards (4 columns, Default)</option>
                    <option value="large">Large Cards (3 columns)</option>
                </select>
            </div>
            <button id="reset-settings">Reset to Defaults</button>
            <button id="close-settings">Close</button>
        </div>
    </div>
    <script>
        // Generate purple particles
        function createStars() {
            for (let i = 0; i < 100; i++) {
                const star = document.createElement('div');
                star.className = 'star';
                star.style.left = `${Math.random() * 100}vw`;
                star.style.top = `${Math.random() * 100}vh`;
                star.style.animationDelay = `${Math.random() * 5}s`;
                document.body.appendChild(star);
            }
        }

        const body = document.body;
        const main = document.querySelector('main');
        const sidebar = document.getElementById('sidebar');
        const gameGrid = document.getElementById('game-grid');
        const settingsBtn = document.getElementById('settings-btn');
        const settingsModal = document.getElementById('settings-modal');
        const closeSettings = document.getElementById('close-settings');
        const themeSelect = document.getElementById('theme-select');
        const wallpaperUpload = document.getElementById('wallpaper-upload');
        const fontSize = document.getElementById('font-size');
        const sidebarWidth = document.getElementById('sidebar-width');
        const gridSize = document.getElementById('grid-size');
        const resetSettings = document.getElementById('reset-settings');

        function applySettings() {
            const theme = localStorage.getItem('theme') || 'neon';
            const customWallpaper = localStorage.getItem('customWallpaper');
            const selectedFontSize = localStorage.getItem('fontSize') || 'medium';
            const selectedSidebarWidth = localStorage.getItem('sidebarWidth') || 'medium';
            const selectedGridSize = localStorage.getItem('gridSize') || 'medium';

            body.className = theme;
            if (theme === 'custom' && customWallpaper) {
                body.style.backgroundImage = `url(${customWallpaper})`;
            } else {
                body.style.backgroundImage = '';
            }
            body.style.fontSize = selectedFontSize === 'small' ? '0.9rem' : selectedFontSize === 'large' ? '1.1rem' : '1rem';
            sidebar.className = `sidebar ${selectedSidebarWidth}`;
            main.className = `main ${selectedSidebarWidth}`;
            gameGrid.className = `game-grid ${selectedGridSize}`;
            themeSelect.value = theme;
            fontSize.value = selectedFontSize;
            sidebarWidth.value = selectedSidebarWidth;
            gridSize.value = selectedGridSize;
        }

        function setupEventListeners() {
            settingsBtn.addEventListener('click', (e) => {
                e.preventDefault();
                settingsModal.classList.add('show');
            });

            closeSettings.addEventListener('click', () => {
                settingsModal.classList.remove('show');
            });

            themeSelect.addEventListener('change', () => {
                const value = themeSelect.value;
                localStorage.setItem('theme', value);
                if (value === 'custom') {
                    wallpaperUpload.style.display = 'block';
                    wallpaperUpload.click();
                } else {
                    wallpaperUpload.style.display = 'none';
                    body.style.backgroundImage = '';
                    localStorage.removeItem('customWallpaper');
                    applySettings();
                }
            });

            wallpaperUpload.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        const dataUrl = event.target.result;
                        localStorage.setItem('customWallpaper', dataUrl);
                        applySettings();
                    };
                    reader.readAsDataURL(file);
                }
            });

            fontSize.addEventListener('change', () => {
                localStorage.setItem('fontSize', fontSize.value);
                applySettings();
            });

            sidebarWidth.addEventListener('change', () => {
                localStorage.setItem('sidebarWidth', sidebarWidth.value);
                applySettings();
            });

            gridSize.addEventListener('change', () => {
                localStorage.setItem('gridSize', gridSize.value);
                applySettings();
            });

            resetSettings.addEventListener('click', () => {
                localStorage.clear();
                applySettings();
            });

            // Browser and games
            const addressInput = document.getElementById('address-input');
            const goBtn = document.getElementById('go-btn');
            const iframeContainer = document.getElementById('iframe-container');
            const loading = document.getElementById('loading');
            const error = document.getElementById('error');
            const genreButtons = document.querySelectorAll('.genre-btn');
            const games = <?php echo json_encode($genres); ?>;

            function loadUrl(url) {
                loading.style.display = 'block';
                error.style.display = 'none';
                const proxyUrl = '?proxy=' + encodeURIComponent(url);
                const iframe = document.createElement('iframe');
                iframe.src = proxyUrl;
                iframe.onload = () => loading.style.display = 'none';
                iframe.onerror = () => {
                    loading.style.display = 'none';
                    error.style.display = 'block';
                    error.textContent = 'Failed to load.';
                };
                iframeContainer.innerHTML = '';
                iframeContainer.appendChild(iframe);
            }

            goBtn?.addEventListener('click', () => {
                let val = addressInput.value.trim();
                if (!val) {
                    error.style.display = 'block';
                    error.textContent = 'Enter a URL or search.';
                    return;
                }
                let url = val.match(/^https?:\/\//) ? val : 'https://' + val;
                if (!val.match(/^(https?:\/\/)?[\w.-]+\.[a-z]{2,}/i)) {
                    url = `https://www.google.com/search?q=${encodeURIComponent(val)}`;
                }
                loadUrl(url);
            });

            addressInput?.addEventListener('keypress', e => {
                if (e.key === 'Enter') goBtn.click();
            });

            function displayGames(genre) {
                gameGrid.innerHTML = '';
                games[genre].forEach(game => {
                    const card = document.createElement('div');
                    card.className = `game-card ${localStorage.getItem('gridSize') || 'medium'}`;
                    card.dataset.url = game.url;
                    card.innerHTML = `
                        <img src="${game.thumb}" alt="${game.title}">
                        <div>${game.title}</div>
                    `;
                    card.addEventListener('click', () => {
                        window.location.href = '?page=browser&proxy=' + encodeURIComponent(game.url);
                    });
                    gameGrid.appendChild(card);
                });
            }

            genreButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    genreButtons.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    displayGames(btn.dataset.genre);
                });
            });

            if (genreButtons.length > 0) {
                genreButtons[0].classList.add('active');
                displayGames(genreButtons[0].dataset.genre);
            }
        }

        // Initialize UI and particles immediately
        createStars();
        applySettings();
        setupEventListeners();
    </script>
</body>
</html>
