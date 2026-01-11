<?php
  require_once __DIR__ . '/config/maintenance.php';
  volume75EnforceMaintenanceMode();

  $pageTitle = 'Lazergame leaderboard – Volume 75';
  $pageDescription = 'Volg live de tussenstanden van het Volume 75 lazergame toernooi en bekijk de resultaten van de individuele potjes.';
  $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
  $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
  $basePath = $basePath === '.' ? '' : $basePath;
?>
<!DOCTYPE html>
<html lang="nl">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/png" href="<?= $basePath ?>/assets/images/volume75-logo.png" />
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/style.css" />
    <link rel="stylesheet" href="<?= $basePath ?>/assets/css/lazergame.css" />
  </head>
  <body class="lazergame-body">
    <div class="lazergame-wrapper">
      <section class="lazergame-hero">
        <p class="eyebrow">Volume 75 presents</p>
        <h1>Lazergame Live</h1>
        <p class="lede">
          Bekijk de actuele tussenstand van alle teams en volg de uitslagen van de meest recente potjes.
        </p>
        <div class="view-toggle" role="tablist" aria-label="Kies overzicht">
          <button type="button" class="view-toggle__button is-active" data-view-button="leaderboard" role="tab" aria-selected="true">
            Leaderboard
          </button>
          <button type="button" class="view-toggle__button" data-view-button="games" role="tab" aria-selected="false">
            Laatste games
          </button>
        </div>
      </section>

      <section class="panel leaderboard-panel" data-view-section="leaderboard" aria-live="polite">
        <div class="panel-header">
          <div>
            <p class="eyebrow">Tussenstand</p>
            <h2>Leaderboard</h2>
          </div>
        </div>
        <ol class="leaderboard-list" data-leaderboard-list>
          <li class="placeholder">Leaderboard ophalen…</li>
        </ol>
      </section>

      <section class="panel games-panel" data-view-section="games" aria-live="polite">
        <div class="panel-header">
          <div>
            <p class="eyebrow">Laatste games</p>
            <h2>Individuele potjes</h2>
          </div>
        </div>
        <div class="games-list" data-games-list>
          <p class="placeholder">Games ophalen…</p>
        </div>
      </section>
    </div>

    <script>
      (() => {
        const leaderboardUrl = 'https://glr75-xi.ict-lab.nl/api/leaderboard';
        const gamesUrl = 'https://glr75-xi.ict-lab.nl/api/games';
        const leaderboardList = document.querySelector('[data-leaderboard-list]');
        const gamesList = document.querySelector('[data-games-list]');
        const viewButtons = document.querySelectorAll('[data-view-button]');
        const viewSections = document.querySelectorAll('[data-view-section]');
        const formatter = new Intl.DateTimeFormat('nl-NL', {
          weekday: 'long',
          hour: '2-digit',
          minute: '2-digit',
          day: '2-digit',
          month: 'long'
        });

        let refreshInterval;
        let activeView = 'leaderboard';

        const setActiveView = (view) => {
          activeView = view;
          viewButtons.forEach((button) => {
            const isActive = button.dataset.viewButton === view;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
          });
          viewSections.forEach((section) => {
            const isActive = section.dataset.viewSection === view;
            section.hidden = !isActive;
            section.setAttribute('aria-hidden', isActive ? 'false' : 'true');
          });
        };

        const formatTimestamp = (ts) => {
          if (!ts) return 'Onbekend';
          try {
            const date = typeof ts === 'number' ? new Date(ts * 1000) : new Date(ts);
            return formatter.format(date);
          } catch (error) {
            return 'Onbekend';
          }
        };

        const renderLeaderboard = (items) => {
          if (!leaderboardList) return;
          if (!Array.isArray(items) || !items.length) {
            leaderboardList.innerHTML = '<li class="placeholder">Geen leaderboard gegevens beschikbaar.</li>';
            return;
          }

          leaderboardList.innerHTML = items
            .sort((a, b) => (a.rank ?? 999) - (b.rank ?? 999))
            .map((item) => {
              const rank = item.rank ?? '?';
              const name = item.team_naam ?? 'Onbekend team';
              const score = item.totaal_punten ?? 0;
              const highlightClass = rank <= 3 ? 'leaderboard-item--top' : '';
              return `
                <li class="leaderboard-item ${highlightClass}">
                  <span class="leaderboard-rank">${rank}</span>
                  <span class="leaderboard-name">${name}</span>
                  <span class="leaderboard-score">${score} pt</span>
                </li>
              `;
            })
            .join('');
        };

        const renderGames = (games) => {
          if (!gamesList) return;
          if (!Array.isArray(games) || !games.length) {
            gamesList.innerHTML = '<p class="placeholder">Nog geen gespeelde potjes.</p>';
            return;
          }

          gamesList.innerHTML = games
            .slice(0, 20)
            .map((game) => {
              const stamp = formatTimestamp(game.tijdstip);
              return `
                <article class="game-card">
                  <div class="game-score">
                    <div>
                      <p class="game-team">${game.team_a_naam ?? 'Team A'}</p>
                      <p class="game-points">${game.team_a_punten ?? 0}</p>
                    </div>
                    <span class="game-versus">vs</span>
                    <div>
                      <p class="game-team">${game.team_b_naam ?? 'Team B'}</p>
                      <p class="game-points">${game.team_b_punten ?? 0}</p>
                    </div>
                  </div>
                  <p class="game-meta">${stamp}</p>
                </article>
              `;
            })
            .join('');
        };

        const fetchJson = async (url) => {
          const response = await fetch(url, { cache: 'no-store' });
          if (!response.ok) {
            throw new Error('Netwerkfout');
          }
          return response.json();
        };

        const loadData = async () => {
          try {
            const [leaderboardData, gamesData] = await Promise.all([
              fetchJson(leaderboardUrl),
              fetchJson(gamesUrl)
            ]);

            renderLeaderboard(leaderboardData.leaderboard);
            renderGames(gamesData.recent_games);
          } catch (error) {
            console.error('Fout bij laden lazergame data', error);
            if (leaderboardList) {
              leaderboardList.innerHTML = '<li class="placeholder">Kon het leaderboard niet ophalen. Probeer het later opnieuw.</li>';
            }
            if (gamesList) {
              gamesList.innerHTML = '<p class="placeholder">Kon de games niet ophalen.</p>';
            }
          }
        };

        const startAutoRefresh = () => {
          clearInterval(refreshInterval);
          refreshInterval = setInterval(loadData, 30000);
        };

        viewButtons.forEach((button) => {
          button.addEventListener('click', () => {
            setActiveView(button.dataset.viewButton || 'leaderboard');
          });
        });

        loadData();
        startAutoRefresh();
        setActiveView(activeView);
      })();
    </script>
  </body>
</html>
