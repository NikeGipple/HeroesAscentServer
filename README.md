Heroes Ascent Server
<div align="center">

<strong>Backend system for the Guild Wars 2 community competition <em>Heroes Ascent</em>, powered by L’Arco del Leone.</strong>
<br><br>

<a href="https://github.com/larcodelleone/heroes-ascent-server/actions"> <img src="https://github.com/larcodelleone/heroes-ascent-server/workflows/tests/badge.svg" alt="Build Status"> </a> <a href="https://github.com/larcodelleone/heroes-ascent-server/releases"> <img src="https://img.shields.io/github/v/release/larcodelleone/heroes-ascent-server" alt="Latest Release"> </a> <a href="https://www.php.net/"> <img src="https://img.shields.io/badge/PHP-%5E8.3-blue" alt="PHP"> </a> <a href="https://laravel.com/"> <img src="https://img.shields.io/badge/Laravel-11-red" alt="Laravel"> </a> <a href="https://github.com/larcodelleone/heroes-ascent-server"> <img src="https://img.shields.io/github/license/larcodelleone/heroes-ascent-server" alt="License"> </a> </div>
Overview

Heroes Ascent Server is the backend core of the Heroes Ascent competition —
a Guild Wars 2 event where players must level a new character from level 0 to 80 under strict and transparent rules.

The server connects directly to the Heroes Ascent Addon (C++ client using RTAPI and ImGui) to receive live player data, validate compliance with the event rules, and log any violations.

Features

        Secure Registration — Players register with their Guild Wars 2 API key and receive a unique account token.
        Real-Time Telemetry — The server collects game data directly from the addon (map, combat, state).
        Rule Validation — Detects forbidden actions such as mounts, gliding, healing, or group use.
        Structured Logging — Each update is recorded for transparency and review.
        Leaderboard Support — Can be integrated with public leaderboard APIs for ranking.

Architecture
        Component	Technology
        Framework	Laravel 11 (PHP 8.3+)
        Database	MySQL 8
        Containerization	Docker
        Protocol	RTAPI-based JSON
        Client	Heroes Ascent Addon (C++ / ImGui)