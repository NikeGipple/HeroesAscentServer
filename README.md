<h1 >Heroes Ascent Server</h1>

<p >
  <strong>Backend system for the Guild Wars 2 community competition <em>Heroes Ascent</em>, powered by L’Arco del Leone.</strong>
</p>


<hr>

<h2>Overview</h2>

<p>
  <strong>Heroes Ascent Server</strong> is the backend core of the <em>Heroes Ascent</em> competition —
  a Guild Wars 2 event where players must level a new character from level 0 to 80 under strict and transparent rules.
</p>

<p>
  The server connects directly to the <strong>Heroes Ascent Addon</strong> (C++ client using RTAPI and ImGui)
  to receive live player data, validate compliance with the event rules, and log any violations.
</p>

<hr>

<h2>Features</h2>

<ul>
  <li><strong>Secure Registration</strong> — Players register with their Guild Wars 2 API key and receive a unique account token.</li>
  <li><strong>Real-Time Telemetry</strong> — The server collects game data directly from the addon (map, combat, state).</li>
  <li><strong>Rule Validation</strong> — Detects forbidden actions such as mounts, gliding, healing, or group use.</li>
  <li><strong>Structured Logging</strong> — Each update is recorded for transparency and review.</li>
  <li><strong>Leaderboard Support</strong> — Can be integrated with public leaderboard APIs for ranking.</li>
</ul>

<hr>

<h2>Architecture</h2>

<table>
  <tr><th>Component</th><th>Technology</th></tr>
  <tr><td>Framework</td><td>Laravel 12 (PHP 8.3+)</td></tr>
  <tr><td>Database</td><td>MySQL 8</td></tr>
  <tr><td>Containerization</td><td>Docker</td></tr>
  <tr><td>Protocol</td><td>JSON</td></tr>
  <tr><td>Client</td><td>Heroes Ascent Addon (C++ / ImGui)</td></tr>
</table>


<h2>Community</h2>

<p>
  This project is part of <strong>L’Arco del Leone</strong>, the main Italian Guild Wars 2 community hub.<br>
  Join us to follow community events, guides, and livestreams:
</p>


<h2>Security</h2>

<p>
  If you discover a security vulnerability, please contact us <br>

</p>

<hr>

<h2>License</h2>

<p>
  The <strong>Heroes Ascent Server</strong> is open-source software licensed under the <strong>MIT License</strong>.<br>
  © 2025 L’Arco del Leone — Heroes Ascent Project.
</p>
