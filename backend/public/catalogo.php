<?php
// Listagem pública dinâmica: mostra os álbuns cadastrados no painel, com busca e filtro por GET.
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/../views/layout.php';

use Onda\Session;
use Onda\Database as DB;

Session::start();

// Entradas por GET: texto de busca e ano (opcional), sempre validadas e com tamanho limitado.
$q = is_string($_GET['q'] ?? null) ? trim(mb_substr($_GET['q'], 0, 100)) : '';
$yearInput = is_string($_GET['ano'] ?? null) ? trim($_GET['ano']) : '';
$year = $yearInput === '' ? null
    : filter_var($yearInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1000, 'max_range' => 9999]]);
$error = $year === false ? 'Informe um ano entre 1000 e 9999.' : '';

$where = [];
$params = [];
if ($q !== '') {
    // Os curingas digitados são escapados para a busca ser literal.
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(b.title LIKE ? OR a.name LIKE ?)';
    array_push($params, $like, $like);
}
if (is_int($year)) {
    $where[] = 'b.year = ?';
    $params[] = $year;
}
// Paginação por GET (?pagina=2): primeiro conta o total, depois busca só a página pedida.
const PER_PAGE = 6;
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$total = $error ? 0 : (int) DB::query(
    'SELECT count(*) FROM albums b JOIN artists a ON a.id = b.artist_id' . $whereSql, $params,
)->fetchColumn();
$pages = max(1, (int) ceil($total / PER_PAGE));
$page = filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
$page = min($page, $pages);
$albums = $error ? [] : DB::query(
    'SELECT b.id, b.title, b.year, a.id AS artist_id, a.name AS artist_name, count(t.id) AS track_count
       FROM albums b JOIN artists a ON a.id = b.artist_id LEFT JOIN tracks t ON t.album_id = b.id'
    . $whereSql
    . ' GROUP BY b.id, b.title, b.year, a.id, a.name ORDER BY a.name, b.year DESC, b.title LIMIT ' . PER_PAGE
    . ' OFFSET ' . (($page - 1) * PER_PAGE),
    $params,
)->fetchAll();
// Links de página mantêm a busca e o filtro de ano na URL.
$pageUrl = fn (int $n): string => '/catalogo.php?' . http_build_query(array_filter(
    ['q' => $q, 'ano' => $yearInput, 'pagina' => $n > 1 ? $n : null],
    fn ($value) => $value !== null && $value !== '',
));
$years = DB::query('SELECT DISTINCT year FROM albums WHERE year IS NOT NULL ORDER BY year DESC')->fetchAll(PDO::FETCH_COLUMN);

publicStart('Catálogo', 'catalogo');
?>
<h1>Catálogo</h1>

<form method="get" action="/catalogo.php" class="search-inline" role="search">
  <label>Buscar álbum ou artista
    <input type="search" name="q" maxlength="100" value="<?= h($q) ?>" placeholder="Ex.: Maré">
  </label>
  <label>Ano
    <select name="ano">
      <option value="">Todos</option>
      <?php foreach ($years as $option): ?>
        <option value="<?= h($option) ?>"<?= $year === (int) $option ? ' selected' : '' ?>><?= h($option) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <button class="primary">Filtrar</button>
  <?php if ($q !== '' || $yearInput !== ''): ?><a class="pill" href="/catalogo.php">Limpar</a><?php endif; ?>
</form>

<?php if ($error): ?><p class="error" role="alert"><?= h($error) ?></p><?php endif; ?>
<p role="status"><?= $total ?> álbum(ns) encontrado(s)<?= $q !== '' ? ' para “' . h($q) . '”' : '' ?><?= $pages > 1 ? " · página $page de $pages" : '' ?>.</p>

<?php if ($albums): ?>
<div class="table-wrap"><table>
  <caption>Álbuns do catálogo</caption>
  <thead><tr><th scope="col">Álbum</th><th scope="col">Artista</th><th scope="col">Ano</th><th scope="col">Faixas</th></tr></thead>
  <tbody>
  <?php foreach ($albums as $album): ?>
    <tr>
      <td><a href="/#/albums/<?= h($album['id']) ?>"><?= h($album['title']) ?></a></td>
      <td><a href="/#/artists/<?= h($album['artist_id']) ?>"><?= h($album['artist_name']) ?></a></td>
      <td><?= h($album['year'] ?? '—') ?></td>
      <td><?= (int) $album['track_count'] === 0 ? '<span class="badge">Em breve</span>' : h($album['track_count']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php if ($pages > 1): ?>
<nav class="pager" aria-label="Páginas do catálogo">
  <?php if ($page > 1): ?><a class="pill" href="<?= h($pageUrl($page - 1)) ?>" rel="prev">← Anterior</a><?php endif; ?>
  <?php for ($n = 1; $n <= $pages; $n++): ?>
    <a class="pill" href="<?= h($pageUrl($n)) ?>"<?= $n === $page ? ' aria-current="page"' : '' ?>><?= $n ?></a>
  <?php endfor; ?>
  <?php if ($page < $pages): ?><a class="pill" href="<?= h($pageUrl($page + 1)) ?>" rel="next">Próxima →</a><?php endif; ?>
</nav>
<?php endif; ?>
<?php else: ?>
<p class="empty">Nenhum álbum encontrado. Tente outro termo.</p>
<?php endif; ?>
<?php publicEnd(); ?>
