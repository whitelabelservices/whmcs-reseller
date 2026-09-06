<?php

/**
 * Minimal WebSocket relay for Proxmox VNC (server-side PVEAuthCookie).
 */

function wls_ws_accept_key($key) {
    return base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
}

function wls_ws_read_http_headers($socket) {
    $headers = [];
    while (($line = fgets($socket)) !== false) {
        $line = rtrim($line, "\r\n");
        if ($line === '') {
            break;
        }
        if (strpos($line, ':') !== false) {
            [$name, $value] = explode(':', $line, 2);
            $headers[strtolower(trim($name))] = trim($value);
        } else {
            $headers['status'] = $line;
        }
    }
    return $headers;
}

function wls_ws_read_frame($socket) {
    $header = fread($socket, 2);
    if ($header === false || strlen($header) < 2) {
        return false;
    }

    $byte1 = ord($header[0]);
    $byte2 = ord($header[1]);
    $opcode = $byte1 & 0x0f;
    $masked = ($byte2 & 0x80) !== 0;
    $length = $byte2 & 0x7f;

    if ($length === 126) {
        $ext = fread($socket, 2);
        if ($ext === false || strlen($ext) < 2) {
            return false;
        }
        $length = unpack('n', $ext)[1];
    } elseif ($length === 127) {
        $ext = fread($socket, 8);
        if ($ext === false || strlen($ext) < 8) {
            return false;
        }
        $parts = unpack('N2', $ext);
        $length = $parts[2];
    }

    $mask = '';
    if ($masked) {
        $mask = fread($socket, 4);
        if ($mask === false || strlen($mask) < 4) {
            return false;
        }
    }

    $payload = '';
    if ($length > 0) {
        $remaining = $length;
        while ($remaining > 0) {
            $chunk = fread($socket, $remaining);
            if ($chunk === false || $chunk === '') {
                return false;
            }
            $payload .= $chunk;
            $remaining -= strlen($chunk);
        }
    }

    if ($masked && $mask !== '') {
        for ($i = 0; $i < $length; $i++) {
            $payload[$i] = $payload[$i] ^ $mask[$i % 4];
        }
    }

    return ['opcode' => $opcode, 'payload' => $payload, 'fin' => ($byte1 & 0x80) !== 0];
}

function wls_ws_write_frame($socket, $payload, $opcode = 0x02, $masked = false) {
    $length = strlen($payload);
    $frame = chr(0x80 | ($opcode & 0x0f));

    if ($length < 126) {
        $frame .= chr(($masked ? 0x80 : 0x00) | $length);
    } elseif ($length < 65536) {
        $frame .= chr(($masked ? 0x80 : 0x00) | 126) . pack('n', $length);
    } else {
        $frame .= chr(($masked ? 0x80 : 0x00) | 127) . pack('NN', 0, $length);
    }

    if ($masked) {
        $mask = random_bytes(4);
        $frame .= $mask;
        for ($i = 0; $i < $length; $i++) {
            $frame .= $payload[$i] ^ $mask[$i % 4];
        }
    } else {
        $frame .= $payload;
    }

    return fwrite($socket, $frame) !== false;
}

function wls_ws_accept_client($clientSocket, $requestHeaders) {
    $key = $requestHeaders['sec-websocket-key'] ?? '';
    if ($key === '') {
        return false;
    }

    $response = "HTTP/1.1 101 Switching Protocols\r\n"
        . "Upgrade: websocket\r\n"
        . "Connection: Upgrade\r\n"
        . 'Sec-WebSocket-Accept: ' . wls_ws_accept_key($key) . "\r\n\r\n";

    return fwrite($clientSocket, $response) !== false;
}

function wls_ws_connect_proxmox(array $session) {
    $host = $session['pve_host'];
    $port = (int) $session['pve_port'];
    $path = '/api2/json/nodes/' . rawurlencode($session['node'])
        . '/qemu/' . (int) $session['vmid']
        . '/vncwebsocket?port=' . rawurlencode((string) $session['vnc_port'])
        . '&vncticket=' . rawurlencode((string) $session['vnc_ticket']);

    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ]);

    $remote = @stream_socket_client(
        'ssl://' . $host . ':' . $port,
        $errno,
        $errstr,
        20,
        STREAM_CLIENT_CONNECT,
        $context
    );

    if (!$remote) {
        throw new RuntimeException('Could not connect to hypervisor: ' . $errstr);
    }

    stream_set_blocking($remote, true);
    stream_set_timeout($remote, 0);

    $wsKey = base64_encode(random_bytes(16));
    $cookie = 'PVEAuthCookie=' . $session['pve_ticket'];
    $hostHeader = ($port <= 0 || $port === 443) ? $host : $host . ':' . $port;
    $request = "GET {$path} HTTP/1.1\r\n"
        . "Host: {$hostHeader}\r\n"
        . "Upgrade: websocket\r\n"
        . "Connection: Upgrade\r\n"
        . "Sec-WebSocket-Key: {$wsKey}\r\n"
        . "Sec-WebSocket-Version: 13\r\n"
        . "Cookie: {$cookie}\r\n"
        . "\r\n";

    if (fwrite($remote, $request) === false) {
        fclose($remote);
        throw new RuntimeException('Failed to start hypervisor WebSocket handshake');
    }

    $headers = wls_ws_read_http_headers($remote);
    $status = $headers['status'] ?? '';
    if (strpos($status, '101') === false) {
        fclose($remote);
        throw new RuntimeException('Hypervisor rejected WebSocket: ' . ($status ?: 'unknown'));
    }

    stream_set_blocking($remote, false);
    return $remote;
}

function wls_ws_relay($clientSocket, $remoteSocket) {
    stream_set_blocking($clientSocket, false);

    while (true) {
        $read = [$clientSocket, $remoteSocket];
        $write = null;
        $except = null;

        if (@stream_select($read, $write, $except, 60) === false) {
            break;
        }

        if (empty($read)) {
            break;
        }

        foreach ($read as $socket) {
            $frame = wls_ws_read_frame($socket);
            if ($frame === false) {
                return;
            }

            $opcode = $frame['opcode'];
            if ($opcode === 0x08) {
                return;
            }

            if ($opcode === 0x09) {
                $target = ($socket === $clientSocket) ? $remoteSocket : $clientSocket;
                wls_ws_write_frame($target, $frame['payload'], 0x0a);
                continue;
            }

            $target = ($socket === $clientSocket) ? $remoteSocket : $clientSocket;
            if (!wls_ws_write_frame($target, $frame['payload'], $opcode)) {
                return;
            }
        }
    }
}

function wls_ws_proxy_console(array $session) {
    if (strcasecmp($_SERVER['HTTP_UPGRADE'] ?? '', 'websocket') !== 0
        || stripos($_SERVER['HTTP_CONNECTION'] ?? '', 'upgrade') === false) {
        http_response_code(426);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'WebSocket upgrade required';
        return;
    }

    $clientKey = $_SERVER['HTTP_SEC_WEBSOCKET_KEY'] ?? '';
    if ($clientKey === '') {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Missing Sec-WebSocket-Key';
        return;
    }

    while (ob_get_level()) {
        ob_end_clean();
    }

    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', '1');
    }
    @ini_set('zlib.output_compression', '0');
    @ini_set('implicit_flush', '1');

    $remoteSocket = wls_ws_connect_proxmox($session);

    header('HTTP/1.1 101 Switching Protocols');
    header('Upgrade: websocket');
    header('Connection: Upgrade');
    header('Sec-WebSocket-Accept: ' . wls_ws_accept_key($clientKey));
    header('X-Accel-Buffering: no');

    $clientSocket = @fopen('php://input', 'rb');
    if (!$clientSocket) {
        fclose($remoteSocket);
        throw new RuntimeException('WebSocket relay is not supported on this web server');
    }

    stream_set_blocking($clientSocket, false);
    wls_ws_relay($clientSocket, $remoteSocket);
    @fclose($remoteSocket);
    @fclose($clientSocket);
}
