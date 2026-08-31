<?php

/**
 * Endpoint kompatibilitas dari versi integrasi SSO sebelumnya.
 *
 * Login LMS dan SIAKAD kini sengaja dipisahkan. Permintaan lama ke endpoint
 * ini diteruskan ke halaman login SIAKAD dan tidak lagi menerima tiket SSO.
 */
header('Location: login', true, 303);
exit;
