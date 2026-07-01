<?php
define('GITHUB_USER', 'marcioguedesdias');
define('GITHUB_REPO', 'founder');
define('GITHUB_BRANCH', 'master');
define('GITHUB_RAW', 'https://raw.githubusercontent.com/' . GITHUB_USER . '/' . GITHUB_REPO . '/' . GITHUB_BRANCH . '/');

function fetchMD($path) {
    $url = GITHUB_RAW . $path;
    $ctx = stream_context_create(['http' => ['timeout' => 5, 'user_agent' => 'founder-dashboard']]);
    $content = @file_get_contents($url, false, $ctx);
    return $content ?: '';
}

function readSection($content, $section) {
    if (!$content) return [];
    $lines = explode("\n", $content);
    $capture = false;
    $items = [];
    foreach ($lines as $line) {
        if (strpos($line, '## ' . $section) !== false) { $capture = true; continue; }
        if ($capture && strpos($line, '## ') === 0) break;
        if ($capture && preg_match('/- \[(x| )\] (.+)/i', $line, $m)) {
            $items[] = ['done' => strtolower(trim($m[1])) === 'x', 'text' => trim($m[2])];
        }
    }
    return $items;
}

function readSubsections($content, $parentSection) {
    if (!$content) return [];
    $lines = explode("\n", $content);
    $inParent = false;
    $currentSub = null;
    $result = [];
    foreach ($lines as $line) {
        if (strpos($line, '## ' . $parentSection) !== false) { $inParent = true; continue; }
        if ($inParent && strpos($line, '## ') === 0 && strpos($line, '### ') === false) break;
        if ($inParent && strpos($line, '### ') === 0) {
            $currentSub = trim(str_replace('###', '', $line));
            $result[$currentSub] = [];
            continue;
        }
        if ($inParent && $currentSub && preg_match('/- \[(x| )\] (.+)/i', $line, $m)) {
            $result[$currentSub][] = ['done' => strtolower(trim($m[1])) === 'x', 'text' => trim($m[2])];
        }
    }
    return $result;
}

function lastUpdate($content) {
    preg_match('/Última atualização: (.+)/', $content, $m);
    return isset($m[1]) ? trim($m[1]) : '—';
}

function pct($items) {
    if (!$items || count($items) === 0) return 0;
    $done = array_filter($items, fn($i) => $i['done']);
    return round(count($done) / count($items) * 100);
}

// Busca arquivos
$curriculo = fetchMD('jornada/curriculo.md');
$tarefasAp = fetchMD('jornada/tarefas-aprendizado.md');
$tarefasPr = fetchMD('produto/tarefas.md');
$roadmap   = fetchMD('produto/roadmap.md');

// Seções
$produtoItems  = readSection($curriculo, 'Fundamentos de Produto');
$tecnicoItems  = readSection($curriculo, 'Fundamentos Técnicos');
$iaItems       = readSection($curriculo, 'Uso de IA');
$decisoesItems = readSection($curriculo, 'Decisões Técnicas');
$analogosSubs  = readSubsections($curriculo, 'Conhecimentos Análogos');
$softItems     = readSection($curriculo, 'Soft Skills aplicadas ao contexto técnico');
$lacunas       = readSection($tarefasAp, 'Próximas — Conceitos de Produto para revisão');
$tarefasAndamento = readSection($tarefasAp, 'Em andamento');
$tarefasConcluidas = readSection($tarefasAp, 'Concluídas');
$prodAndamento = readSection($tarefasPr, 'Em andamento');
$prodProximas  = readSection($tarefasPr, 'Próximas');
$prodConcluidas = readSection($tarefasPr, 'Concluídas');

$produtoPct  = pct($produtoItems);
$tecnicoPct  = pct($tecnicoItems);
$iaPct       = pct($iaItems);
$decisoesPct = pct($decisoesItems);
$analogosAll = [];
foreach ($analogosSubs as $sub => $items) foreach ($items as $i) $analogosAll[] = $i;
$analogosPct = pct($analogosAll);
$softPct     = pct($softItems);

$cats = [$produtoPct, $tecnicoPct, $iaPct, $decisoesPct, $analogosPct, $softPct];
$geralPct = round(array_sum($cats) / count($cats));
$update = lastUpdate($curriculo);
$lacunasAbertas = count(array_filter($lacunas, fn($i) => !$i['done']));

$timeline = [
    ['done' => true,  'text' => 'Repositório estruturado e governança de IA definida',          'date' => '01 jul 2026'],
    ['done' => true,  'text' => 'Papéis mapeados: Founder, PO, Gerente de Projeto',             'date' => '01 jul 2026'],
    ['done' => true,  'text' => 'Questionário de conceitos de produto — 5 validados, 5 lacunas', 'date' => '01 jul 2026'],
    ['done' => true,  'text' => 'Git e GitHub — fluxo básico conectado ao VS Code',             'date' => '01 jul 2026'],
    ['done' => true,  'text' => 'Dashboard publicado em predilleto.com/founder',                 'date' => '01 jul 2026'],
    ['done' => false, 'text' => 'Revisão das lacunas identificadas',                             'date' => 'Próxima sessão'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Founder — Dashboard</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --bg: #f5f4f0; --surface: #ffffff; --surface-2: #f0efe9;
  --text: #0b0b0b; --text-secondary: #52514e; --text-muted: #898781;
  --border: rgba(11,11,11,0.10); --border-strong: rgba(11,11,11,0.18);
  --radius: 8px; --accent: #2a78d6; --success: #1baf7a; --warning: #eda100; --danger: #e34948;
}
@media (prefers-color-scheme: dark) {
  :root {
    --bg: #111110; --surface: #1a1a19; --surface-2: #222221;
    --text: #ffffff; --text-secondary: #c3c2b7; --text-muted: #898781;
    --border: rgba(255,255,255,0.10); --border-strong: rgba(255,255,255,0.18);
  }
}
body { background: var(--bg); color: var(--text); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 15px; line-height: 1.6; }
.wrap { max-width: 900px; margin: 0 auto; padding: 2rem 1rem; }
.db-header { margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 8px; }
.db-title { font-size: 22px; font-weight: 500; }
.db-sub { font-size: 13px; color: var(--text-muted); }
.gh-link { color: var(--accent); text-decoration: none; font-size: 12px; }
.gh-link:hover { text-decoration: underline; }

/* TABS */
.tabs { display: flex; gap: 4px; border-bottom: 0.5px solid var(--border-strong); margin-bottom: 2rem; overflow-x: auto; }
.tab-btn { background: none; border: none; padding: 10px 16px; font-size: 13px; color: var(--text-muted); cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; white-space: nowrap; transition: color 0.15s; }
.tab-btn:hover { color: var(--text-secondary); }
.tab-btn.active { color: var(--text); border-bottom-color: var(--text); font-weight: 500; }
.tab-content { display: none; }
.tab-content.active { display: block; }

/* LAYOUT */
.section { margin-bottom: 2rem; }
.section-label { font-size: 11px; font-weight: 500; color: var(--text-muted); letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 10px; }
.grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; }
.grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 12px; }
.grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }

/* CARDS */
.metric { background: var(--surface); border-radius: var(--radius); padding: 1rem; }
.metric-label { font-size: 12px; color: var(--text-muted); margin-bottom: 6px; }
.metric-value { font-size: 30px; font-weight: 500; line-height: 1; }
.metric-detail { font-size: 12px; color: var(--text-secondary); margin-top: 5px; }
.card { background: var(--surface); border: 0.5px solid var(--border); border-radius: 12px; padding: 1.25rem; }

/* PROGRESS */
.progress-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.progress-row:last-child { margin-bottom: 0; }
.p-label { font-size: 13px; color: var(--text-secondary); width: 200px; flex-shrink: 0; }
.p-track { flex: 1; background: var(--surface-2); border-radius: 4px; height: 6px; overflow: hidden; }
.p-bar { height: 6px; border-radius: 4px; }
.p-pct { font-size: 12px; color: var(--text-muted); width: 34px; text-align: right; flex-shrink: 0; }

/* TAGS */
.tag { display: inline-block; font-size: 11px; padding: 3px 9px; border-radius: var(--radius); margin: 3px 3px 3px 0; font-weight: 500; }
.tag-gap { background: #fcebeb; color: #a32d2d; }
.tag-partial { background: #faeeda; color: #854f0b; }
.tag-done { background: #eaf3de; color: #3b6d11; }
@media (prefers-color-scheme: dark) {
  .tag-gap { background: #501313; color: #f09595; }
  .tag-partial { background: #412402; color: #fac775; }
  .tag-done { background: #173404; color: #c0dd97; }
}

/* TIMELINE */
.timeline-item { display: flex; gap: 12px; padding: 10px 0; border-bottom: 0.5px solid var(--border); align-items: flex-start; }
.timeline-item:last-child { border-bottom: none; }
.t-dot { width: 8px; height: 8px; border-radius: 50%; margin-top: 6px; flex-shrink: 0; }
.t-text { font-size: 13px; color: var(--text-secondary); flex: 1; }
.t-date { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
.t-next { color: var(--accent); }

/* LEGEND */
.legend { display: flex; gap: 16px; margin-bottom: 12px; flex-wrap: wrap; }
.legend-item { display: flex; align-items: center; gap: 5px; font-size: 12px; color: var(--text-secondary); }
.legend-dot { width: 10px; height: 10px; border-radius: 2px; }

/* SKILL CARDS */
.skill-category { margin-bottom: 1.5rem; }
.skill-cat-title { font-size: 13px; font-weight: 500; color: var(--text-secondary); margin-bottom: 8px; padding-bottom: 6px; border-bottom: 0.5px solid var(--border); }
.skill-grid { display: flex; flex-wrap: wrap; gap: 8px; }
.skill-chip { display: flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: var(--radius); border: 0.5px solid var(--border); font-size: 13px; background: var(--surface); }
.skill-chip.done { border-color: #1baf7a; background: #eaf3de; color: #3b6d11; }
.skill-chip.pending { border-color: var(--border); color: var(--text-muted); }
@media (prefers-color-scheme: dark) {
  .skill-chip.done { background: #173404; color: #c0dd97; border-color: #3b6d11; }
}
.skill-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }

/* CV */
.cv-wrap { background: var(--surface); border: 0.5px solid var(--border); border-radius: 12px; padding: 2.5rem; max-width: 680px; margin: 0 auto; }
.cv-name { font-size: 26px; font-weight: 500; margin-bottom: 4px; }
.cv-tagline { font-size: 14px; color: var(--text-secondary); margin-bottom: 1.5rem; }
.cv-meta { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 0.5px solid var(--border); }
.cv-meta-item { font-size: 13px; color: var(--text-muted); }
.cv-section { margin-bottom: 1.75rem; }
.cv-section-title { font-size: 11px; font-weight: 500; letter-spacing: 0.08em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 10px; padding-bottom: 6px; border-bottom: 0.5px solid var(--border); }
.cv-item { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 8px; font-size: 13px; }
.cv-item:last-child { margin-bottom: 0; }
.cv-check { color: #1baf7a; font-size: 14px; flex-shrink: 0; margin-top: 1px; }
.cv-pending-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--border-strong); flex-shrink: 0; margin-top: 6px; }
.cv-item-text { color: var(--text-secondary); }
.cv-badge { display: inline-block; font-size: 10px; padding: 2px 7px; border-radius: 4px; background: #eaf3de; color: #3b6d11; margin-left: 6px; font-weight: 500; vertical-align: middle; }
@media (prefers-color-scheme: dark) { .cv-badge { background: #173404; color: #c0dd97; } }
.cv-progress-row { margin-bottom: 10px; }
.cv-prog-header { display: flex; justify-content: space-between; font-size: 12px; color: var(--text-secondary); margin-bottom: 4px; }
.cv-prog-track { background: var(--surface-2); border-radius: 4px; height: 5px; overflow: hidden; }
.cv-prog-bar { height: 5px; border-radius: 4px; }
.cv-tabs { display: flex; gap: 8px; margin-bottom: 1.5rem; }
.cv-tab { background: none; border: 0.5px solid var(--border); border-radius: var(--radius); padding: 6px 14px; font-size: 13px; color: var(--text-muted); cursor: pointer; }
.cv-tab.active { background: var(--text); color: var(--bg); border-color: var(--text); }

/* TAREFAS */
.task-section { margin-bottom: 1.5rem; }
.task-section-title { font-size: 13px; font-weight: 500; color: var(--text-secondary); margin-bottom: 8px; }
.task-item { display: flex; align-items: flex-start; gap: 10px; padding: 10px 0; border-bottom: 0.5px solid var(--border); }
.task-item:last-child { border-bottom: none; }
.task-check { width: 16px; height: 16px; border-radius: 4px; border: 1.5px solid var(--border-strong); flex-shrink: 0; margin-top: 2px; display: flex; align-items: center; justify-content: center; }
.task-check.done { background: #1baf7a; border-color: #1baf7a; color: white; font-size: 10px; }
.task-text { font-size: 13px; color: var(--text-secondary); flex: 1; }
.task-text.done { text-decoration: line-through; color: var(--text-muted); }
.task-badge { font-size: 10px; padding: 2px 7px; border-radius: 4px; font-weight: 500; flex-shrink: 0; }
.task-badge.aprendizado { background: #e6f1fb; color: #185fa5; }
.task-badge.produto { background: #faeeda; color: #854f0b; }
@media (prefers-color-scheme: dark) {
  .task-badge.aprendizado { background: #042c53; color: #85b7eb; }
  .task-badge.produto { background: #412402; color: #fac775; }
}

/* METAS */
.meta-card { background: var(--surface); border: 0.5px solid var(--border); border-radius: 12px; padding: 1.25rem; margin-bottom: 12px; }
.meta-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.meta-title { font-size: 14px; font-weight: 500; }
.meta-pct { font-size: 13px; color: var(--text-muted); }
.meta-track { background: var(--surface-2); border-radius: 4px; height: 6px; overflow: hidden; margin-bottom: 8px; }
.meta-bar { height: 6px; border-radius: 4px; }
.meta-detail { font-size: 12px; color: var(--text-muted); }

/* FOOTER */
.footer { margin-top: 3rem; padding-top: 1rem; border-top: 0.5px solid var(--border); font-size: 12px; color: var(--text-muted); display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
</style>
</head>
<body>
<div class="wrap">

  <div class="db-header">
    <div>
      <p class="db-title">Projeto Founder</p>
      <p class="db-sub">Atualizado em <?= htmlspecialchars($update) ?></p>
    </div>
    <a class="gh-link" href="https://github.com/<?= GITHUB_USER ?>/<?= GITHUB_REPO ?>" target="_blank">Ver repositório &rarr;</a>
  </div>

  <!-- TABS -->
  <div class="tabs">
    <button class="tab-btn active" onclick="switchTab('dashboard')">Dashboard</button>
    <button class="tab-btn" onclick="switchTab('curriculo')">Currículo</button>
    <button class="tab-btn" onclick="switchTab('metas')">Metas</button>
    <button class="tab-btn" onclick="switchTab('tarefas')">Tarefas</button>
  </div>

  <!-- TAB: DASHBOARD -->
  <div id="tab-dashboard" class="tab-content active">

    <div class="section">
      <p class="section-label">Visão geral</p>
      <div class="grid-4">
        <div class="metric">
          <p class="metric-label">Progresso geral</p>
          <p class="metric-value"><?= $geralPct ?>%</p>
          <p class="metric-detail">currículo vivo</p>
        </div>
        <div class="metric">
          <p class="metric-label">Fundamentos técnicos</p>
          <p class="metric-value"><?= $tecnicoPct ?>%</p>
          <p class="metric-detail">validados</p>
        </div>
        <div class="metric">
          <p class="metric-label">Uso de IA</p>
          <p class="metric-value"><?= $iaPct ?>%</p>
          <p class="metric-detail">validados</p>
        </div>
        <div class="metric">
          <p class="metric-label">Lacunas abertas</p>
          <p class="metric-value"><?= $lacunasAbertas ?></p>
          <p class="metric-detail">para revisão</p>
        </div>
      </div>
    </div>

    <div class="grid-2">
      <div class="section">
        <p class="section-label">Progresso por categoria</p>
        <div class="card">
          <div class="legend">
            <span class="legend-item"><span class="legend-dot" style="background:#1baf7a"></span>Concluído</span>
            <span class="legend-item"><span class="legend-dot" style="background:#2a78d6"></span>Em andamento</span>
          </div>
          <?php
          $categories = [
            'Fundamentos de produto' => $produtoPct,
            'Uso de IA'              => $iaPct,
            'Soft skills'            => $softPct,
            'Decisões técnicas'      => $decisoesPct,
            'Conhecimentos análogos' => $analogosPct,
            'Fundamentos técnicos'   => $tecnicoPct,
          ];
          foreach ($categories as $label => $p):
            $color = $p >= 100 ? '#1baf7a' : ($p > 0 ? '#2a78d6' : '#d1d0c9');
          ?>
          <div class="progress-row">
            <span class="p-label"><?= $label ?></span>
            <div class="p-track"><div class="p-bar" style="width:<?= $p ?>%;background:<?= $color ?>"></div></div>
            <span class="p-pct"><?= $p ?>%</span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="section">
        <p class="section-label">Jornada — linha do tempo</p>
        <div class="card">
          <?php foreach ($timeline as $t): ?>
          <div class="timeline-item">
            <div class="t-dot" style="background:<?= $t['done'] ? '#1baf7a' : '#2a78d6' ?>"></div>
            <div>
              <p class="t-text"><?= htmlspecialchars($t['text']) ?></p>
              <p class="t-date <?= !$t['done'] ? 't-next' : '' ?>"><?= $t['date'] ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="section">
      <p class="section-label">Lacunas para revisão</p>
      <div class="card">
        <?php foreach ($lacunas as $item):
          $class = strpos(strtolower($item['text']), 'lacuna') !== false ? 'tag-gap' : 'tag-partial';
        ?>
        <span class="tag <?= $class ?>"><?= htmlspecialchars($item['text']) ?></span>
        <?php endforeach; ?>
        <?php if (empty($lacunas)): ?><p style="font-size:13px;color:var(--text-muted)">Nenhuma lacuna registrada.</p><?php endif; ?>
      </div>
    </div>

  </div>

  <!-- TAB: CURRÍCULO -->
  <div id="tab-curriculo" class="tab-content">

    <div class="cv-tabs">
      <button class="cv-tab active" onclick="switchCVTab('cards')">Visual</button>
      <button class="cv-tab" onclick="switchCVTab('doc')">Documento</button>
    </div>

    <!-- CV: CARDS -->
    <div id="cv-cards">
      <?php
      $allSections = [
        'Fundamentos de Produto'                    => $produtoItems,
        'Fundamentos Técnicos'                      => $tecnicoItems,
        'Uso de IA'                                 => $iaItems,
        'Decisões Técnicas'                         => $decisoesItems,
        'Soft Skills aplicadas ao contexto técnico' => $softItems,
      ];
      foreach ($allSections as $sectionTitle => $items):
        if (empty($items)) continue;
      ?>
      <div class="skill-category">
        <p class="skill-cat-title"><?= $sectionTitle ?> — <?= pct($items) ?>%</p>
        <div class="skill-grid">
          <?php foreach ($items as $item): ?>
          <div class="skill-chip <?= $item['done'] ? 'done' : 'pending' ?>">
            <span class="skill-dot" style="background:<?= $item['done'] ? '#1baf7a' : '#d1d0c9' ?>"></span>
            <?= htmlspecialchars($item['text']) ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>

      <?php if (!empty($analogosSubs)): ?>
      <div class="skill-category">
        <p class="skill-cat-title">Conhecimentos Análogos — <?= $analogosPct ?>%</p>
        <?php foreach ($analogosSubs as $sub => $items): ?>
        <p style="font-size:12px;color:var(--text-muted);margin:8px 0 6px;"><?= htmlspecialchars($sub) ?></p>
        <div class="skill-grid">
          <?php foreach ($items as $item): ?>
          <div class="skill-chip <?= $item['done'] ? 'done' : 'pending' ?>">
            <span class="skill-dot" style="background:<?= $item['done'] ? '#1baf7a' : '#d1d0c9' ?>"></span>
            <?= htmlspecialchars($item['text']) ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- CV: DOCUMENTO -->
    <div id="cv-doc" style="display:none">
      <div class="cv-wrap">
        <p class="cv-name">Marcio Guedes Dias</p>
        <p class="cv-tagline">Founder em transição técnica — produto digital, IA e tecnologia</p>
        <div class="cv-meta">
          <span class="cv-meta-item">github.com/marcioguedesdias/founder</span>
          <span class="cv-meta-item">predilleto.com/founder</span>
          <span class="cv-meta-item">Atualizado em <?= htmlspecialchars($update) ?></span>
        </div>

        <div class="cv-section">
          <p class="cv-section-title">Progresso geral</p>
          <?php foreach ($categories as $label => $p):
            $color = $p >= 100 ? '#1baf7a' : ($p > 0 ? '#2a78d6' : '#d1d0c9');
          ?>
          <div class="cv-progress-row">
            <div class="cv-prog-header"><span><?= $label ?></span><span><?= $p ?>%</span></div>
            <div class="cv-prog-track"><div class="cv-prog-bar" style="width:<?= $p ?>%;background:<?= $color ?>"></div></div>
          </div>
          <?php endforeach; ?>
        </div>

        <?php
        $cvSections = [
          'Fundamentos de Produto'                    => $produtoItems,
          'Fundamentos Técnicos'                      => $tecnicoItems,
          'Uso de IA'                                 => $iaItems,
          'Decisões Técnicas'                         => $decisoesItems,
          'Soft Skills'                               => $softItems,
        ];
        foreach ($cvSections as $title => $items):
          if (empty($items)) continue;
          $doneItems    = array_filter($items, fn($i) => $i['done']);
          $pendingItems = array_filter($items, fn($i) => !$i['done']);
        ?>
        <div class="cv-section">
          <p class="cv-section-title"><?= $title ?></p>
          <?php foreach ($doneItems as $item): ?>
          <div class="cv-item">
            <span class="cv-check">✓</span>
            <span class="cv-item-text"><?= htmlspecialchars($item['text']) ?> <span class="cv-badge">validado</span></span>
          </div>
          <?php endforeach; ?>
          <?php foreach ($pendingItems as $item): ?>
          <div class="cv-item">
            <span class="cv-pending-dot"></span>
            <span class="cv-item-text" style="color:var(--text-muted)"><?= htmlspecialchars($item['text']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>

        <?php if (!empty($analogosSubs)): ?>
        <div class="cv-section">
          <p class="cv-section-title">Conhecimentos Análogos</p>
          <?php foreach ($analogosSubs as $sub => $items): ?>
          <p style="font-size:11px;color:var(--text-muted);margin:8px 0 4px;text-transform:uppercase;letter-spacing:0.06em"><?= htmlspecialchars($sub) ?></p>
          <?php foreach ($items as $item): ?>
          <div class="cv-item">
            <?php if ($item['done']): ?>
            <span class="cv-check">✓</span>
            <span class="cv-item-text"><?= htmlspecialchars($item['text']) ?> <span class="cv-badge">validado</span></span>
            <?php else: ?>
            <span class="cv-pending-dot"></span>
            <span class="cv-item-text" style="color:var(--text-muted)"><?= htmlspecialchars($item['text']) ?></span>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <!-- TAB: METAS -->
  <div id="tab-metas" class="tab-content">
    <div class="section">
      <p class="section-label">Metas da jornada</p>
      <?php
      $metas = [
        ['title' => 'Currículo vivo — progresso geral',        'pct' => $geralPct,    'detail' => 'Média de todas as categorias',           'color' => '#2a78d6'],
        ['title' => 'Fundamentos de produto',                   'pct' => $produtoPct,  'detail' => count(array_filter($produtoItems, fn($i) => $i['done'])) . ' de ' . count($produtoItems) . ' habilidades validadas', 'color' => '#1baf7a'],
        ['title' => 'Fundamentos técnicos',                     'pct' => $tecnicoPct,  'detail' => count(array_filter($tecnicoItems, fn($i) => $i['done'])) . ' de ' . count($tecnicoItems) . ' habilidades validadas', 'color' => '#1baf7a'],
        ['title' => 'Uso de IA',                                'pct' => $iaPct,       'detail' => count(array_filter($iaItems, fn($i) => $i['done'])) . ' de ' . count($iaItems) . ' habilidades validadas',       'color' => '#1baf7a'],
        ['title' => 'Conhecimentos análogos (papéis e times)',  'pct' => $analogosPct, 'detail' => 'Papéis e carreiras em tech mapeados',    'color' => '#eda100'],
        ['title' => 'Soft skills no contexto técnico',          'pct' => $softPct,     'detail' => count(array_filter($softItems, fn($i) => $i['done'])) . ' de ' . count($softItems) . ' habilidades validadas',    'color' => '#1baf7a'],
        ['title' => 'Lacunas resolvidas',                       'pct' => count($lacunas) > 0 ? round(count(array_filter($lacunas, fn($i) => $i['done'])) / count($lacunas) * 100) : 0, 'detail' => ($lacunasAbertas) . ' lacunas ainda abertas', 'color' => '#e34948'],
      ];
      foreach ($metas as $meta):
      ?>
      <div class="meta-card">
        <div class="meta-header">
          <span class="meta-title"><?= $meta['title'] ?></span>
          <span class="meta-pct"><?= $meta['pct'] ?>%</span>
        </div>
        <div class="meta-track"><div class="meta-bar" style="width:<?= $meta['pct'] ?>%;background:<?= $meta['color'] ?>"></div></div>
        <p class="meta-detail"><?= $meta['detail'] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- TAB: TAREFAS -->
  <div id="tab-tarefas" class="tab-content">

    <div class="grid-2">
      <div class="section">
        <p class="section-label">Aprendizado</p>
        <div class="card">
          <?php if (!empty($tarefasAndamento)): ?>
          <div class="task-section">
            <p class="task-section-title">Em andamento</p>
            <?php foreach ($tarefasAndamento as $t): ?>
            <div class="task-item">
              <div class="task-check <?= $t['done'] ? 'done' : '' ?>"><?= $t['done'] ? '✓' : '' ?></div>
              <span class="task-text <?= $t['done'] ? 'done' : '' ?>"><?= htmlspecialchars($t['text']) ?></span>
              <span class="task-badge aprendizado">aprendizado</span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <?php if (!empty($lacunas)): ?>
          <div class="task-section">
            <p class="task-section-title">Para revisão</p>
            <?php foreach ($lacunas as $t): ?>
            <div class="task-item">
              <div class="task-check <?= $t['done'] ? 'done' : '' ?>"><?= $t['done'] ? '✓' : '' ?></div>
              <span class="task-text <?= $t['done'] ? 'done' : '' ?>"><?= htmlspecialchars($t['text']) ?></span>
              <span class="task-badge aprendizado">lacuna</span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <?php if (!empty($tarefasConcluidas)): ?>
          <div class="task-section">
            <p class="task-section-title">Concluídas</p>
            <?php foreach ($tarefasConcluidas as $t): ?>
            <div class="task-item">
              <div class="task-check done">✓</div>
              <span class="task-text done"><?= htmlspecialchars($t['text']) ?></span>
              <span class="task-badge aprendizado">aprendizado</span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <?php if (empty($tarefasAndamento) && empty($lacunas) && empty($tarefasConcluidas)): ?>
          <p style="font-size:13px;color:var(--text-muted)">Nenhuma tarefa registrada.</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="section">
        <p class="section-label">Produto</p>
        <div class="card">
          <?php if (!empty($prodAndamento)): ?>
          <div class="task-section">
            <p class="task-section-title">Em andamento</p>
            <?php foreach ($prodAndamento as $t): ?>
            <div class="task-item">
              <div class="task-check <?= $t['done'] ? 'done' : '' ?>"><?= $t['done'] ? '✓' : '' ?></div>
              <span class="task-text <?= $t['done'] ? 'done' : '' ?>"><?= htmlspecialchars($t['text']) ?></span>
              <span class="task-badge produto">produto</span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <?php if (!empty($prodProximas)): ?>
          <div class="task-section">
            <p class="task-section-title">Próximas</p>
            <?php foreach ($prodProximas as $t): ?>
            <div class="task-item">
              <div class="task-check"></div>
              <span class="task-text"><?= htmlspecialchars($t['text']) ?></span>
              <span class="task-badge produto">produto</span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <?php if (!empty($prodConcluidas)): ?>
          <div class="task-section">
            <p class="task-section-title">Concluídas</p>
            <?php foreach ($prodConcluidas as $t): ?>
            <div class="task-item">
              <div class="task-check done">✓</div>
              <span class="task-text done"><?= htmlspecialchars($t['text']) ?></span>
              <span class="task-badge produto">produto</span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <?php if (empty($prodAndamento) && empty($prodProximas) && empty($prodConcluidas)): ?>
          <p style="font-size:13px;color:var(--text-muted)">Nenhuma tarefa de produto registrada.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="footer">
    <span>Projeto Founder &mdash; uso pessoal</span>
    <span>Fonte: <a class="gh-link" href="https://github.com/<?= GITHUB_USER ?>/<?= GITHUB_REPO ?>" target="_blank">github.com/<?= GITHUB_USER ?>/<?= GITHUB_REPO ?></a></span>
  </div>

</div>

<script>
function switchTab(name) {
  document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
  document.getElementById('tab-' + name).classList.add('active');
  event.target.classList.add('active');
}
function switchCVTab(name) {
  document.getElementById('cv-cards').style.display = name === 'cards' ? 'block' : 'none';
  document.getElementById('cv-doc').style.display   = name === 'doc'   ? 'block' : 'none';
  document.querySelectorAll('.cv-tab').forEach(el => el.classList.remove('active'));
  event.target.classList.add('active');
}
</script>
</body>
</html>
