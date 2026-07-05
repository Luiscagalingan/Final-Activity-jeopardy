# Web Feud: Information Security Edition
### Jeopardy Elimination → Last 2 Standing → CTF Resolution

A web-based classroom game for BSIT Information Security II — built with HTML5, CSS3, JavaScript, PHP, and MySQL, designed to run locally on XAMPP.

## How the game flows

1. **Elimination round** — A Jeopardy-style board with 5 InfoSec categories × 5 point values ($100–$500). All active teams compete; the host reveals questions, judges answers, and updates scores live.
2. **Last 2 Standing** — Once the host advances the game, the two highest-scoring teams become finalists. They privately wager points and answer a single Final Jeopardy question.
3. **CTF resolution** — If the finalists end up tied, the game automatically launches a live Capture The Flag challenge (Caesar cipher / Base64 decode). Finalists race to submit the correct flag from their own device; first correct submission wins.

## Setup on XAMPP

1. Copy the `webfeud_ctf` folder into your XAMPP `htdocs` directory, e.g. `C:\xampp\htdocs\webfeud_ctf` or `/Applications/XAMPP/htdocs/webfeud_ctf`.
2. Start **Apache** and **MySQL** from the XAMPP control panel.
3. Open `http://localhost/phpmyadmin`, click **Import**, and import `database/schema.sql`. This creates the `web_feud_ctf` database with all tables and the seeded question bank.
4. If your MySQL root user has a password, update `config/db.php` (`DB_PASS`).
5. Visit `http://localhost/webfeud_ctf/` — this is your landing page with links to all three interfaces.

## The three interfaces

| Interface | URL | Used by |
|---|---|---|
| Main Board | `board/main_board.php` | Projected screen for players/audience |
| Host Dashboard | `host/login.php` | The host/instructor (PIN protected, default PIN: `1234`) |
| Team Submission | `team/submit.php` | Finalists, on their own device, during the CTF stage only |

**Change the default PIN** in `includes/functions.php` (`HOST_PIN` constant) before running a real session.

## Running a session

1. Open the Main Board on the projector and the Host Dashboard on the host's laptop.
2. In the Host Dashboard, add each competing team by name, then click **Start elimination round**.
3. Click any dollar value on the board to select a question, then **Reveal question**, then judge which team answered correctly (or mark it wrong / close it if nobody gets it).
4. Once at least 2 teams remain active, click **Advance to Last 2 Standing**. Enter each finalist's wager, reveal the Final Jeopardy question, then grade each team correct/wrong.
5. If the finalists end up tied, the game automatically moves to the **CTF resolution** stage. Have both finalists open the Team Submission page on a phone or laptop and race to submit the correct flag. The host dashboard also has a manual "declare winner" button as a fallback.
6. Click **Reset entire game** on the finished screen to run another session with the same question bank.

## Customizing questions

All questions, categories, and CTF challenges live in `database/schema.sql`. To add or edit content:
- Edit the `INSERT INTO questions` statements for the elimination round board.
- Edit `INSERT INTO questions` for `category_id = 6` (Final Jeopardy).
- For CTF challenges, store only a `sha256` hash of the flag (never the plain flag) in `ctf_challenges.flag_hash`. You can generate a hash from a terminal with:
  ```
  echo -n "FLAG{YOUR_FLAG_HERE}" | sha256sum   (macOS/Linux)
  ```
  Then re-import the schema, or run an `INSERT`/`UPDATE` directly in phpMyAdmin.

## Notes on the current design (matches your project's stated limitations)

- **Polling, not WebSockets**: the Main Board and Host Dashboard both poll `api/state.php` every 1.5 seconds, so there is a small (1–2s) delay between a host action and what the board shows — consistent with the "no real-time synchronization" limitation in the proposal.
- **Single session only**: `game_state` is a single row; only one game can run at a time per server, matching the "single session per server instance" limitation.
- **Fixed question bank**: questions are seeded ahead of time via `schema.sql`; there's no in-game question editor, matching the "fixed question bank" limitation.
- **Flags are never sent to the browser in plain text** — only a sha256 hash is stored, and `api/state.php` explicitly strips it from every response, even to the host.
