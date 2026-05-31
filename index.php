<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VHost Manager Pro</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(255,255,255,0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.06); }
        .glass-light { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.08); }
        .glow { box-shadow: 0 0 30px rgba(99, 102, 241, 0.15), 0 0 60px rgba(99, 102, 241, 0.05); }
        .glow-success { box-shadow: 0 0 30px rgba(16, 185, 129, 0.15); }
        .glow-danger { box-shadow: 0 0 30px rgba(239, 68, 68, 0.15); }
        .input-dark { background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #e2e8f0; transition: all 0.3s; }
        .input-dark:focus { border-color: rgba(99, 102, 241, 0.5); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); outline: none; }
        .input-dark::placeholder { color: rgba(148, 163, 184, 0.5); }
        .btn-primary { background: linear-gradient(135deg, #6366f1, #4f46e5); transition: all 0.3s; }
        .btn-primary:hover { background: linear-gradient(135deg, #818cf8, #6366f1); transform: translateY(-1px); box-shadow: 0 4px 20px rgba(99, 102, 241, 0.4); }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); transition: all 0.3s; }
        .btn-danger:hover { background: linear-gradient(135deg, #f87171, #ef4444); transform: translateY(-1px); box-shadow: 0 4px 20px rgba(239, 68, 68, 0.4); }
        .btn-success { background: linear-gradient(135deg, #10b981, #059669); transition: all 0.3s; }
        .btn-success:hover { background: linear-gradient(135deg, #34d399, #10b981); transform: translateY(-1px); box-shadow: 0 4px 20px rgba(16, 185, 129, 0.4); }
        .btn-warning { background: linear-gradient(135deg, #f59e0b, #d97706); transition: all 0.3s; }
        .btn-warning:hover { background: linear-gradient(135deg, #fbbf24, #f59e0b); transform: translateY(-1px); box-shadow: 0 4px 20px rgba(245, 158, 11, 0.4); }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-2px); border-color: rgba(99, 102, 241, 0.3); }
        .fade-in { animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .pulse-dot { animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .scrollbar-thin::-webkit-scrollbar { width: 6px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: rgba(0,0,0,0.2); border-radius: 3px; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.3); border-radius: 3px; }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover { background: rgba(99, 102, 241, 0.5); }
        .modal-overlay { background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); }
        .code-block { background: rgba(0,0,0,0.4); font-family: 'Fira Code', 'Cascadia Code', 'JetBrains Mono', monospace; }
        .badge { font-size: 0.65rem; padding: 2px 8px; border-radius: 9999px; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; }
        .tab-active { border-bottom: 2px solid #6366f1; color: #818cf8; }
        .tab-inactive { border-bottom: 2px solid transparent; color: #64748b; }
        .tab-inactive:hover { color: #94a3b8; border-color: rgba(99, 102, 241, 0.2); }
        .stat-card { background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(99, 102, 241, 0.02)); border: 1px solid rgba(99, 102, 241, 0.15); }
        .bg-grid { background-image: radial-gradient(rgba(99, 102, 241, 0.08) 1px, transparent 1px); background-size: 24px 24px; }
    </style>
</head>
<body class="bg-[#0a0a1a] text-gray-200 min-h-screen bg-grid">

<?php
// ─── Configuration ───
$vhostsFile = 'C:/xampp/apache/conf/extra/httpd-vhosts.conf';
$hostsFile  = 'C:/Windows/System32/drivers/etc/hosts';

$message = '';
$messageType = '';

// ─── Helper: Parse vhosts ───
function parseVhosts($file) {
    if (!file_exists($file)) return [];
    $content = file_get_contents($file);
    $vhosts = [];
    // Match <VirtualHost ...> ... </VirtualHost> blocks
    preg_match_all('/<VirtualHost\s+([^>]+)>(.*?)<\/VirtualHost>/si', $content, $matches, PREG_SET_ORDER);
    foreach ($matches as $i => $match) {
        $address = trim($match[1]);
        $block = $match[2];
        $vhost = [
            'id' => $i,
            'address' => $address,
            'ServerName' => '',
            'ServerAlias' => '',
            'DocumentRoot' => '',
            'ErrorLog' => '',
            'CustomLog' => '',
            'DirectoryIndex' => '',
            'raw' => $match[0],
            'extra_directives' => '',
        ];
        // Extract known directives
        $knownKeys = ['ServerName','ServerAlias','DocumentRoot','ErrorLog','CustomLog','DirectoryIndex'];
        foreach ($knownKeys as $key) {
            if (preg_match('/' . $key . '\s+"?([^"\n]+)"?\s*/i', $block, $m)) {
                $vhost[$key] = trim($m[1], '" ');
            }
        }
        // Gather extra lines not matching known directives or <Directory>
        $lines = explode("\n", $block);
        $extraLines = [];
        $inDirectory = false;
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' ) continue;
            if (preg_match('/<Directory/i', $trimmed)) { $inDirectory = true; }
            if ($inDirectory) {
                $extraLines[] = $line;
                if (preg_match('/<\/Directory>/i', $trimmed)) { $inDirectory = false; }
                continue;
            }
            $isKnown = false;
            foreach ($knownKeys as $key) {
                if (stripos($trimmed, $key) === 0) { $isKnown = true; break; }
            }
            if (!$isKnown && $trimmed !== '') {
                $extraLines[] = $line;
            }
        }
        $vhost['extra_directives'] = implode("\n", $extraLines);
        $vhosts[] = $vhost;
    }
    return $vhosts;
}

// ─── Helper: Parse hosts file ───
function parseHosts($file) {
    if (!file_exists($file)) return [];
    $lines = file($file, FILE_IGNORE_NEW_LINES);
    $entries = [];
    foreach ($lines as $i => $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || $trimmed[0] === '#') continue;
        if (preg_match('/^(\S+)\s+(.+)$/', $trimmed, $m)) {
            $hosts = preg_split('/\s+/', trim($m[2]));
            foreach ($hosts as $h) {
                $entries[] = ['ip' => $m[1], 'hostname' => $h, 'line' => $i];
            }
        }
    }
    return $entries;
}

// ─── Helper: Build vhost block ───
function buildVhostBlock($data) {
    $block  = "<VirtualHost " . $data['address'] . ">\n";
    if (!empty($data['ServerName']))     $block .= "    ServerName " . $data['ServerName'] . "\n";
    if (!empty($data['ServerAlias']))    $block .= "    ServerAlias " . $data['ServerAlias'] . "\n";
    if (!empty($data['DocumentRoot']))   $block .= "    DocumentRoot \"" . $data['DocumentRoot'] . "\"\n";
    if (!empty($data['DirectoryIndex'])) $block .= "    DirectoryIndex " . $data['DirectoryIndex'] . "\n";
    if (!empty($data['ErrorLog']))       $block .= "    ErrorLog \"" . $data['ErrorLog'] . "\"\n";
    if (!empty($data['CustomLog']))      $block .= "    CustomLog \"" . $data['CustomLog'] . "\"\n";
    if (!empty($data['extra_directives'])) {
        $block .= "\n" . $data['extra_directives'] . "\n";
    }
    $block .= "</VirtualHost>\n";
    return $block;
}

// ─── Helper: Rebuild vhosts.conf ───
function rebuildVhostsFile($file, $vhosts) {
    // Preserve any content before the first VirtualHost block (comments, includes, etc.)
    $content = file_get_contents($file);
    $header = '';
    $pos = stripos($content, '<VirtualHost');
    if ($pos !== false) {
        $header = substr($content, 0, $pos);
    }
    $output = $header;
    foreach ($vhosts as $vh) {
        $output .= buildVhostBlock($vh) . "\n";
    }
    file_put_contents($file, $output);
}

// ─── Handle POST Actions ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Add VHost ──
    if ($action === 'add_vhost') {
        $newVhost = [
            'address' => $_POST['address'] ?? '*:80',
            'ServerName' => $_POST['ServerName'] ?? '',
            'DocumentRoot' => $_POST['DocumentRoot'] ?? '',
            'ServerAlias' => $_POST['ServerAlias'] ?? '',
            'ErrorLog' => $_POST['ErrorLog'] ?? '',
            'CustomLog' => $_POST['CustomLog'] ?? '',
            'DirectoryIndex' => $_POST['DirectoryIndex'] ?? '',
            'extra_directives' => $_POST['extra_directives'] ?? '',
        ];
        if (!empty($newVhost['ServerName']) && !empty($newVhost['DocumentRoot'])) {
            $block = "\n" . buildVhostBlock($newVhost);
            file_put_contents($vhostsFile, $block, FILE_APPEND);
            // Auto-add to hosts file
            if (!empty($_POST['auto_hosts'])) {
                $hostsContent = file_get_contents($hostsFile);
                $hostname = $newVhost['ServerName'];
                if (strpos($hostsContent, $hostname) === false) {
                    $entry = "\n127.0.0.1\t" . $hostname;
                    file_put_contents($hostsFile, $entry, FILE_APPEND);
                }
            }
            $message = 'Virtual host "' . htmlspecialchars($newVhost['ServerName']) . '" created successfully.';
            $messageType = 'success';
        } else {
            $message = 'ServerName and DocumentRoot are required.';
            $messageType = 'error';
        }
    }

    // ── Delete VHost ──
    if ($action === 'delete_vhost') {
        $deleteId = (int)($_POST['vhost_id'] ?? -1);
        $vhosts = parseVhosts($vhostsFile);
        if (isset($vhosts[$deleteId])) {
            $deletedName = $vhosts[$deleteId]['ServerName'];
            // Remove from vhosts.conf
            $content = file_get_contents($vhostsFile);
            $content = str_replace($vhosts[$deleteId]['raw'], '', $content);
            // Clean up multiple blank lines
            $content = preg_replace('/\n{3,}/', "\n\n", $content);
            file_put_contents($vhostsFile, $content);
            // Also remove from hosts if requested
            if (!empty($_POST['remove_hosts']) && !empty($deletedName)) {
                $hostsLines = file($hostsFile, FILE_IGNORE_NEW_LINES);
                $hostsLines = array_filter($hostsLines, function($line) use ($deletedName) {
                    return strpos($line, $deletedName) === false;
                });
                file_put_contents($hostsFile, implode("\n", $hostsLines) . "\n");
            }
            $message = 'Virtual host "' . htmlspecialchars($deletedName) . '" deleted successfully.';
            $messageType = 'success';
        }
    }

    // ── Edit VHost ──
    if ($action === 'edit_vhost') {
        $editId = (int)($_POST['vhost_id'] ?? -1);
        $vhosts = parseVhosts($vhostsFile);
        if (isset($vhosts[$editId])) {
            $oldRaw = $vhosts[$editId]['raw'];
            $updated = [
                'address' => $_POST['address'] ?? '*:80',
                'ServerName' => $_POST['ServerName'] ?? '',
                'DocumentRoot' => $_POST['DocumentRoot'] ?? '',
                'ServerAlias' => $_POST['ServerAlias'] ?? '',
                'ErrorLog' => $_POST['ErrorLog'] ?? '',
                'CustomLog' => $_POST['CustomLog'] ?? '',
                'DirectoryIndex' => $_POST['DirectoryIndex'] ?? '',
                'extra_directives' => $_POST['extra_directives'] ?? '',
            ];
            $newBlock = buildVhostBlock($updated);
            $content = file_get_contents($vhostsFile);
            $content = str_replace($oldRaw, $newBlock, $content);
            file_put_contents($vhostsFile, $content);
            $message = 'Virtual host "' . htmlspecialchars($updated['ServerName']) . '" updated successfully.';
            $messageType = 'success';
        }
    }

    // ── Add Host Entry ──
    if ($action === 'add_host') {
        $ip = $_POST['ip'] ?? '127.0.0.1';
        $hostname = $_POST['hostname'] ?? '';
        if (!empty($hostname)) {
            $entry = "\n" . $ip . "\t" . $hostname;
            file_put_contents($hostsFile, $entry, FILE_APPEND);
            $message = 'Host entry "' . htmlspecialchars($hostname) . '" added successfully.';
            $messageType = 'success';
        }
    }

    // ── Delete Host Entry ──
    if ($action === 'delete_host') {
        $delIp = $_POST['ip'] ?? '';
        $delHostname = $_POST['hostname'] ?? '';
        if (!empty($delHostname)) {
            $lines = file($hostsFile, FILE_IGNORE_NEW_LINES);
            $newLines = [];
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed === '' || $trimmed[0] === '#') {
                    $newLines[] = $line;
                    continue;
                }
                // Check if this line contains the hostname to delete
                if (preg_match('/^(\S+)\s+(.+)$/', $trimmed, $m)) {
                    $lineIp = $m[1];
                    $lineHosts = preg_split('/\s+/', trim($m[2]));
                    if ($lineIp === $delIp && in_array($delHostname, $lineHosts)) {
                        $lineHosts = array_filter($lineHosts, function($h) use ($delHostname) {
                            return $h !== $delHostname;
                        });
                        if (!empty($lineHosts)) {
                            $newLines[] = $lineIp . "\t" . implode("\t", $lineHosts);
                        }
                        // If empty, skip the line entirely
                    } else {
                        $newLines[] = $line;
                    }
                } else {
                    $newLines[] = $line;
                }
            }
            file_put_contents($hostsFile, implode("\n", $newLines) . "\n");
            $message = 'Host entry "' . htmlspecialchars($delHostname) . '" removed successfully.';
            $messageType = 'success';
        }
    }

    // ── Save Raw File ──
    if ($action === 'save_raw_vhosts') {
        $raw = $_POST['raw_content'] ?? '';
        file_put_contents($vhostsFile, $raw);
        $message = 'vhosts.conf saved (raw edit).';
        $messageType = 'success';
    }
    if ($action === 'save_raw_hosts') {
        $raw = $_POST['raw_content'] ?? '';
        file_put_contents($hostsFile, $raw);
        $message = 'hosts file saved (raw edit).';
        $messageType = 'success';
    }

    // ── Restart Apache ──
    if ($action === 'restart_apache') {
        $output = [];
        $retval = 0;
        exec('C:\\xampp\\apache\\bin\\httpd.exe -k restart 2>&1', $output, $retval);
        if ($retval === 0) {
            $message = 'Apache restart signal sent successfully.';
            $messageType = 'success';
        } else {
            $message = 'Apache restart returned code ' . $retval . ': ' . implode(' ', $output);
            $messageType = 'error';
        }
    }
}

// ─── Load Data ───
$vhosts = parseVhosts($vhostsFile);
$hostsEntries = parseHosts($hostsFile);
$vhostsRaw = file_exists($vhostsFile) ? file_get_contents($vhostsFile) : '';
$hostsRaw = file_exists($hostsFile) ? file_get_contents($hostsFile) : '';
$vhostsWritable = is_writable($vhostsFile);
$hostsWritable = is_writable($hostsFile);
$vhostsExists = file_exists($vhostsFile);
$hostsExists = file_exists($hostsFile);
?>

<!-- ═══════════════════ HEADER ═══════════════════ -->
<header class="border-b border-white/5">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-white tracking-tight">VHost Manager <span class="text-indigo-400 font-light">Pro</span></h1>
                <p class="text-xs text-slate-500">Apache Virtual Hosts & Windows Hosts Editor</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <!-- Restart Apache -->
            <form method="POST" onsubmit="return confirm('Restart Apache now?');">
                <input type="hidden" name="action" value="restart_apache">
                <button type="submit" class="btn-warning text-white text-sm font-medium px-4 py-2 rounded-lg flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Restart Apache
                </button>
            </form>
        </div>
    </div>
</header>

<main class="max-w-7xl mx-auto px-6 py-6 space-y-6">

    <!-- ═══════════════════ NOTIFICATION ═══════════════════ -->
    <?php if ($message): ?>
    <div class="fade-in rounded-xl px-5 py-4 flex items-center gap-3 <?php echo $messageType === 'success' ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-300' : 'bg-red-500/10 border border-red-500/20 text-red-300'; ?>">
        <?php if ($messageType === 'success'): ?>
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?php else: ?>
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?php endif; ?>
        <span class="text-sm font-medium"><?php echo $message; ?></span>
    </div>
    <?php endif; ?>

    <!-- ═══════════════════ STATUS CARDS ═══════════════════ -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="stat-card rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Virtual Hosts</p>
                    <p class="text-3xl font-bold text-white mt-1"><?php echo count($vhosts); ?></p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-500/10 flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                </div>
            </div>
        </div>
        <div class="stat-card rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Host Entries</p>
                    <p class="text-3xl font-bold text-white mt-1"><?php echo count($hostsEntries); ?></p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
            </div>
        </div>
        <div class="stat-card rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">VHosts File</p>
                    <p class="text-sm font-semibold mt-2 flex items-center gap-2">
                        <?php if ($vhostsExists && $vhostsWritable): ?>
                            <span class="w-2 h-2 rounded-full bg-emerald-400 pulse-dot"></span>
                            <span class="text-emerald-400">Writable</span>
                        <?php elseif ($vhostsExists): ?>
                            <span class="w-2 h-2 rounded-full bg-amber-400 pulse-dot"></span>
                            <span class="text-amber-400">Read Only</span>
                        <?php else: ?>
                            <span class="w-2 h-2 rounded-full bg-red-400 pulse-dot"></span>
                            <span class="text-red-400">Not Found</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-violet-500/10 flex items-center justify-center">
                    <svg class="w-6 h-6 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
            </div>
        </div>
        <div class="stat-card rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Hosts File</p>
                    <p class="text-sm font-semibold mt-2 flex items-center gap-2">
                        <?php if ($hostsExists && $hostsWritable): ?>
                            <span class="w-2 h-2 rounded-full bg-emerald-400 pulse-dot"></span>
                            <span class="text-emerald-400">Writable</span>
                        <?php elseif ($hostsExists): ?>
                            <span class="w-2 h-2 rounded-full bg-amber-400 pulse-dot"></span>
                            <span class="text-amber-400">Read Only</span>
                        <?php else: ?>
                            <span class="w-2 h-2 rounded-full bg-red-400 pulse-dot"></span>
                            <span class="text-red-400">Not Found</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-rose-500/10 flex items-center justify-center">
                    <svg class="w-6 h-6 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════ TABS ═══════════════════ -->
    <div class="border-b border-white/5">
        <nav class="flex gap-6" id="mainTabs">
            <button onclick="switchTab('vhosts')" id="tab-vhosts" class="tab-active py-3 text-sm font-semibold transition-all duration-200">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9"/></svg>
                    Virtual Hosts
                </span>
            </button>
            <button onclick="switchTab('hosts')" id="tab-hosts" class="tab-inactive py-3 text-sm font-semibold transition-all duration-200">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                    Hosts File
                </span>
            </button>
            <button onclick="switchTab('raw')" id="tab-raw" class="tab-inactive py-3 text-sm font-semibold transition-all duration-200">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    Raw Editor
                </span>
            </button>
        </nav>
    </div>

    <!-- ═══════════════════ TAB: VIRTUAL HOSTS ═══════════════════ -->
    <div id="panel-vhosts" class="space-y-6 fade-in">

        <!-- Add New VHost Form -->
        <div class="glass rounded-2xl overflow-hidden">
            <button onclick="toggleSection('addVhostForm')" class="w-full px-6 py-4 flex items-center justify-between hover:bg-white/[0.02] transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <span class="text-sm font-semibold text-white">Add New Virtual Host</span>
                </div>
                <svg id="addVhostFormIcon" class="w-5 h-5 text-slate-500 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div id="addVhostForm" class="hidden border-t border-white/5">
                <form method="POST" class="p-6">
                    <input type="hidden" name="action" value="add_vhost">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Server Name <span class="text-red-400">*</span></label>
                            <input type="text" name="ServerName" required placeholder="mysite.local" class="input-dark w-full px-4 py-2.5 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Listen Address</label>
                            <input type="text" name="address" value="*:80" placeholder="*:80" class="input-dark w-full px-4 py-2.5 rounded-lg text-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Document Root <span class="text-red-400">*</span></label>
                            <input type="text" name="DocumentRoot" required placeholder="C:/xampp/htdocs/mysite" class="input-dark w-full px-4 py-2.5 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Server Alias</label>
                            <input type="text" name="ServerAlias" placeholder="www.mysite.local" class="input-dark w-full px-4 py-2.5 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Directory Index</label>
                            <input type="text" name="DirectoryIndex" placeholder="index.php index.html" class="input-dark w-full px-4 py-2.5 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Error Log</label>
                            <input type="text" name="ErrorLog" placeholder="logs/mysite-error.log" class="input-dark w-full px-4 py-2.5 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Custom Log</label>
                            <input type="text" name="CustomLog" placeholder="logs/mysite-access.log common" class="input-dark w-full px-4 py-2.5 rounded-lg text-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Extra Directives</label>
                            <textarea name="extra_directives" rows="6" placeholder="<Directory &quot;C:/xampp/htdocs/mysite&quot;>&#10;    Options Indexes FollowSymLinks&#10;    AllowOverride All&#10;    Require all granted&#10;</Directory>" class="input-dark w-full px-4 py-2.5 rounded-lg text-sm font-mono">&lt;Directory "C:/xampp/htdocs/projects/dir"&gt;
    AllowOverride All
    Require all granted
&lt;/Directory&gt;</textarea>
                        </div>
                    </div>
                    <div class="mt-5 flex items-center justify-between">
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" name="auto_hosts" value="1" checked class="w-4 h-4 rounded bg-black/30 border-white/20 text-indigo-500 focus:ring-indigo-500/30">
                            <span class="text-sm text-slate-400 group-hover:text-slate-300 transition-colors">Auto-add to Windows hosts file (127.0.0.1)</span>
                        </label>
                        <button type="submit" class="btn-primary text-white text-sm font-semibold px-6 py-2.5 rounded-lg flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Create Virtual Host
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- VHost List -->
        <?php if (empty($vhosts)): ?>
        <div class="glass rounded-2xl p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-indigo-500/10 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-indigo-400/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-white mb-1">No Virtual Hosts Found</h3>
            <p class="text-sm text-slate-500">Create your first virtual host using the form above.</p>
        </div>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($vhosts as $vh): ?>
            <div class="glass rounded-2xl card-hover fade-in overflow-hidden">
                <div class="px-6 py-4 flex items-start justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-base font-bold text-white truncate"><?php echo htmlspecialchars($vh['ServerName'] ?: 'Unnamed'); ?></h3>
                            <span class="badge bg-indigo-500/20 text-indigo-300"><?php echo htmlspecialchars($vh['address']); ?></span>
                            <?php if ($vh['ServerAlias']): ?>
                                <span class="badge bg-purple-500/20 text-purple-300">alias</span>
                            <?php endif; ?>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-1 text-xs">
                            <div class="flex items-center gap-2 text-slate-400">
                                <svg class="w-3.5 h-3.5 text-slate-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                                <span class="truncate"><?php echo htmlspecialchars($vh['DocumentRoot'] ?: '—'); ?></span>
                            </div>
                            <?php if ($vh['ServerAlias']): ?>
                            <div class="flex items-center gap-2 text-slate-400">
                                <svg class="w-3.5 h-3.5 text-slate-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101"/></svg>
                                <span class="truncate"><?php echo htmlspecialchars($vh['ServerAlias']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($vh['ErrorLog']): ?>
                            <div class="flex items-center gap-2 text-slate-400">
                                <svg class="w-3.5 h-3.5 text-slate-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                <span class="truncate"><?php echo htmlspecialchars($vh['ErrorLog']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 ml-4">
                        <button onclick="openEditModal(<?php echo $vh['id']; ?>)" class="p-2 rounded-lg hover:bg-white/5 text-slate-500 hover:text-indigo-400 transition-all" title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <form method="POST" onsubmit="return confirm('Delete this virtual host?');" class="inline">
                            <input type="hidden" name="action" value="delete_vhost">
                            <input type="hidden" name="vhost_id" value="<?php echo $vh['id']; ?>">
                            <input type="hidden" name="remove_hosts" value="1">
                            <button type="submit" class="p-2 rounded-lg hover:bg-white/5 text-slate-500 hover:text-red-400 transition-all" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════ TAB: HOSTS FILE ═══════════════════ -->
    <div id="panel-hosts" class="hidden space-y-6">

        <!-- Add Host Entry -->
        <div class="glass rounded-2xl overflow-hidden">
            <button onclick="toggleSection('addHostForm')" class="w-full px-6 py-4 flex items-center justify-between hover:bg-white/[0.02] transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <span class="text-sm font-semibold text-white">Add New Host Entry</span>
                </div>
                <svg id="addHostFormIcon" class="w-5 h-5 text-slate-500 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div id="addHostForm" class="hidden border-t border-white/5">
                <form method="POST" class="p-6">
                    <input type="hidden" name="action" value="add_host">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">IP Address</label>
                            <input type="text" name="ip" value="127.0.0.1" placeholder="127.0.0.1" class="input-dark w-full px-4 py-2.5 rounded-lg text-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Hostname <span class="text-red-400">*</span></label>
                            <input type="text" name="hostname" required placeholder="mysite.local" class="input-dark w-full px-4 py-2.5 rounded-lg text-sm">
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end">
                        <button type="submit" class="btn-success text-white text-sm font-semibold px-6 py-2.5 rounded-lg flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Entry
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Hosts Entries Table -->
        <?php if (empty($hostsEntries)): ?>
        <div class="glass rounded-2xl p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-emerald-500/10 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-emerald-400/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-white mb-1">No Custom Entries</h3>
            <p class="text-sm text-slate-500">Your hosts file doesn't have custom entries (comments are ignored).</p>
        </div>
        <?php else: ?>
        <div class="glass rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-white">Active Host Entries</h3>
                <div class="relative">
                    <input type="text" id="hostsSearch" onkeyup="filterHostsTable()" placeholder="Search entries..." class="input-dark px-4 py-1.5 rounded-lg text-xs w-48 pl-8">
                    <svg class="w-3.5 h-3.5 text-slate-500 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>
            <div class="overflow-x-auto scrollbar-thin max-h-[400px] overflow-y-auto">
                <table class="w-full" id="hostsTable">
                    <thead class="sticky top-0 bg-[#0d0d24]">
                        <tr class="border-b border-white/5">
                            <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">IP Address</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Hostname</th>
                            <th class="text-right px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hostsEntries as $entry): ?>
                        <tr class="border-b border-white/[0.03] hover:bg-white/[0.02] transition-colors host-row">
                            <td class="px-6 py-3">
                                <span class="text-sm font-mono text-indigo-300"><?php echo htmlspecialchars($entry['ip']); ?></span>
                            </td>
                            <td class="px-6 py-3">
                                <span class="text-sm text-slate-300"><?php echo htmlspecialchars($entry['hostname']); ?></span>
                            </td>
                            <td class="px-6 py-3 text-right">
                                <form method="POST" onsubmit="return confirm('Remove this host entry?');" class="inline">
                                    <input type="hidden" name="action" value="delete_host">
                                    <input type="hidden" name="ip" value="<?php echo htmlspecialchars($entry['ip']); ?>">
                                    <input type="hidden" name="hostname" value="<?php echo htmlspecialchars($entry['hostname']); ?>">
                                    <button type="submit" class="p-1.5 rounded-lg hover:bg-red-500/10 text-slate-600 hover:text-red-400 transition-all" title="Remove">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ═══════════════════ TAB: RAW EDITOR ═══════════════════ -->
    <div id="panel-raw" class="hidden space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Raw VHosts -->
            <div class="glass rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-violet-500/20 flex items-center justify-center">
                            <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-white">httpd-vhosts.conf</h3>
                            <p class="text-[10px] text-slate-600 font-mono"><?php echo htmlspecialchars($vhostsFile); ?></p>
                        </div>
                    </div>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="save_raw_vhosts">
                    <textarea name="raw_content" class="code-block w-full p-4 text-xs text-emerald-300 resize-none scrollbar-thin focus:outline-none leading-relaxed" rows="22" spellcheck="false"><?php echo htmlspecialchars($vhostsRaw); ?></textarea>
                    <div class="px-6 py-3 border-t border-white/5 flex justify-end">
                        <button type="submit" class="btn-primary text-white text-xs font-semibold px-5 py-2 rounded-lg flex items-center gap-2" onclick="return confirm('Save changes to vhosts.conf?');">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                            Save VHosts
                        </button>
                    </div>
                </form>
            </div>

            <!-- Raw Hosts -->
            <div class="glass rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-rose-500/20 flex items-center justify-center">
                            <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-white">hosts</h3>
                            <p class="text-[10px] text-slate-600 font-mono"><?php echo htmlspecialchars($hostsFile); ?></p>
                        </div>
                    </div>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="save_raw_hosts">
                    <textarea name="raw_content" class="code-block w-full p-4 text-xs text-amber-300 resize-none scrollbar-thin focus:outline-none leading-relaxed" rows="22" spellcheck="false"><?php echo htmlspecialchars($hostsRaw); ?></textarea>
                    <div class="px-6 py-3 border-t border-white/5 flex justify-end">
                        <button type="submit" class="btn-primary text-white text-xs font-semibold px-5 py-2 rounded-lg flex items-center gap-2" onclick="return confirm('Save changes to hosts file?');">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                            Save Hosts
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</main>

<!-- ═══════════════════ EDIT VHOST MODAL ═══════════════════ -->
<div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="modal-overlay absolute inset-0" onclick="closeEditModal()"></div>
    <div class="glass rounded-2xl w-full max-w-2xl relative z-10 max-h-[90vh] overflow-y-auto scrollbar-thin glow fade-in">
        <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between sticky top-0 bg-[#12122a]/90 backdrop-blur-xl z-10 rounded-t-2xl">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-500/20 flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <h3 class="text-sm font-semibold text-white">Edit Virtual Host</h3>
            </div>
            <button onclick="closeEditModal()" class="p-2 rounded-lg hover:bg-white/5 text-slate-500 hover:text-white transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" class="p-6">
            <input type="hidden" name="action" value="edit_vhost">
            <input type="hidden" name="vhost_id" id="edit_vhost_id">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Server Name</label>
                    <input type="text" name="ServerName" id="edit_ServerName" class="input-dark w-full px-4 py-2.5 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Listen Address</label>
                    <input type="text" name="address" id="edit_address" class="input-dark w-full px-4 py-2.5 rounded-lg text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Document Root</label>
                    <input type="text" name="DocumentRoot" id="edit_DocumentRoot" class="input-dark w-full px-4 py-2.5 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Server Alias</label>
                    <input type="text" name="ServerAlias" id="edit_ServerAlias" class="input-dark w-full px-4 py-2.5 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Directory Index</label>
                    <input type="text" name="DirectoryIndex" id="edit_DirectoryIndex" class="input-dark w-full px-4 py-2.5 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Error Log</label>
                    <input type="text" name="ErrorLog" id="edit_ErrorLog" class="input-dark w-full px-4 py-2.5 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Custom Log</label>
                    <input type="text" name="CustomLog" id="edit_CustomLog" class="input-dark w-full px-4 py-2.5 rounded-lg text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Extra Directives</label>
                    <textarea name="extra_directives" id="edit_extra_directives" rows="4" class="input-dark w-full px-4 py-2.5 rounded-lg text-sm font-mono"></textarea>
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-3">
                <button type="button" onclick="closeEditModal()" class="px-5 py-2.5 rounded-lg text-sm font-medium text-slate-400 hover:text-white hover:bg-white/5 transition-all">Cancel</button>
                <button type="submit" class="btn-primary text-white text-sm font-semibold px-6 py-2.5 rounded-lg flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════ FOOTER ═══════════════════ -->
<footer class="border-t border-white/5 mt-12">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <p class="text-xs text-slate-600">VHost Manager Pro &mdash; Apache & Hosts Configuration Tool</p>
        <p class="text-xs text-slate-700">
            <span class="text-slate-600">PHP <?php echo phpversion(); ?></span>
            &middot;
            <span class="text-slate-600"><?php echo php_uname('s') . ' ' . php_uname('r'); ?></span>
        </p>
    </div>
</footer>

<script>
// VHosts data for edit modal
const vhostsData = <?php echo json_encode($vhosts); ?>;

// Tab switching
function switchTab(tab) {
    ['vhosts', 'hosts', 'raw'].forEach(t => {
        document.getElementById('panel-' + t).classList.toggle('hidden', t !== tab);
        document.getElementById('tab-' + t).className = t === tab
            ? 'tab-active py-3 text-sm font-semibold transition-all duration-200'
            : 'tab-inactive py-3 text-sm font-semibold transition-all duration-200';
    });
}

// Collapsible sections
function toggleSection(id) {
    const el = document.getElementById(id);
    const icon = document.getElementById(id + 'Icon');
    el.classList.toggle('hidden');
    if (icon) icon.style.transform = el.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
}

// Edit modal
function openEditModal(id) {
    const vh = vhostsData.find(v => v.id === id);
    if (!vh) return;
    document.getElementById('edit_vhost_id').value = vh.id;
    document.getElementById('edit_ServerName').value = vh.ServerName || '';
    document.getElementById('edit_address').value = vh.address || '*:80';
    document.getElementById('edit_DocumentRoot').value = vh.DocumentRoot || '';
    document.getElementById('edit_ServerAlias').value = vh.ServerAlias || '';
    document.getElementById('edit_DirectoryIndex').value = vh.DirectoryIndex || '';
    document.getElementById('edit_ErrorLog').value = vh.ErrorLog || '';
    document.getElementById('edit_CustomLog').value = vh.CustomLog || '';
    document.getElementById('edit_extra_directives').value = vh.extra_directives || '';
    document.getElementById('editModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.body.style.overflow = '';
}

// Close modal on Escape
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEditModal(); });

// Filter hosts table
function filterHostsTable() {
    const filter = document.getElementById('hostsSearch').value.toLowerCase();
    const rows = document.querySelectorAll('.host-row');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}

// Auto-dismiss notification after 5 seconds
<?php if ($message): ?>
setTimeout(() => {
    const notif = document.querySelector('.fade-in');
    if (notif && notif.closest('main')) {
        notif.style.transition = 'opacity 0.5s, transform 0.5s';
        notif.style.opacity = '0';
        notif.style.transform = 'translateY(-10px)';
        setTimeout(() => notif.remove(), 500);
    }
}, 5000);
<?php endif; ?>
</script>

</body>
</html>

