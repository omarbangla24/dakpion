<?php
defined('ADMIN') || exit;

http_response_code(404);
admin_head('Page not found');
echo '<div class="card empty"><h2>Ei page ta nai</h2><p class="muted">Link ta hoyto bodle geche.</p><a class="btn" href="' . aurl() . '">← Dashboard</a></div>';
admin_foot();
