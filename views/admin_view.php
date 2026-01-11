<section class="section-intro">
  <h1>Admin toegang</h1>
  <p>
    Gebruik deze pagina om tijdens de previewfase rechtstreeks naar de verborgen onderdelen van Volume 75 te bladeren.
    Deel de links hieronder alleen met collega-beheerders.
  </p>
</section>

<section class="panel">
  <p>Alle pagina's zijn nu voor iedereen toegankelijk. Alleen het fotobeheer hieronder blijft beperkt tot admins.</p>
  <p>Gebruik onderstaande link uitsluitend om foto-updates te beheren.</p>
  <h2>Admin-only pagina's</h2>
  <ul class="link-list">
    <?php foreach ($restrictedPages as $page): ?>
      <li><a href="<?= $basePath . $page['path'] ?>"><?= htmlspecialchars($page['label']) ?></a></li>
    <?php endforeach; ?>
  </ul>
</section>

<section class="panel">
  <h2>Volledige stemlijst</h2>
  <p>Download een CSV voor Excel met rank, titel, artiest en aantal stemmen. De telling bevat eventuele handmatige correcties.</p>

  <?php if ($voteError): ?>
    <div class="alert alert--error"><?= htmlspecialchars($voteError) ?></div>
  <?php elseif (!empty($fullTrackList)): ?>
    <div class="table-actions">
      <button type="button" data-export-table="vote-table" data-export-format="csv">CSV (alle)</button>
      <button type="button" data-export-table="vote-table" data-export-format="csv" data-export-filter="with-votes">CSV (alleen stemmen)</button>
      <button type="button" data-export-table="vote-table" data-export-format="xlsx">XLSX (alle)</button>
      <button type="button" data-export-table="vote-table" data-export-format="xlsx" data-export-filter="with-votes">XLSX (alleen stemmen)</button>
    </div>
    <div class="table-scroll">
      <table id="vote-table" class="data-table">
        <thead>
          <tr>
            <th>Rank</th>
            <th>Titel</th>
            <th>Artiest</th>
            <th>Stemmen</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($fullTrackList as $index => $track): ?>
            <tr>
              <td><?= $index + 1 ?></td>
              <td><?= htmlspecialchars($track['track'] ?? 'Onbekende track') ?></td>
              <td><?= htmlspecialchars($track['artist'] ?? 'Onbekende artiest') ?></td>
              <td><?= (int) ($track['votes'] ?? 0) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p>Geen stemmen gevonden.</p>
  <?php endif; ?>
</section>

<script>
(() => {
  const buttons = document.querySelectorAll('[data-export-table]');

  const buildRows = (table, filterMode) => {
    const rows = Array.from(table.querySelectorAll('tr'));
    return rows
      .map((row, index) => {
        if (filterMode === 'with-votes' && index > 0) {
          const voteCell = row.querySelector('td:last-child');
          const voteCount = voteCell ? Number.parseInt((voteCell.textContent || '').trim(), 10) : 0;
          if (!Number.isFinite(voteCount) || voteCount <= 0) {
            return null; // skip rows zonder stemmen
          }
        }

        const cells = Array.from(row.querySelectorAll('th,td'));
        return cells.map((cell) => (cell.textContent || '').trim());
      })
      .filter(Boolean);
  };

  const download = (data, mimeType, filename) => {
    const blob = new Blob([data], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
  };

  const toCsv = (rows) =>
    rows
      .map((row) =>
        row
          .map((value) => '"' + value.replace(/"/g, '""') + '"')
          .join(',')
      )
      .join('\n');

  const toSpreadsheetXml = (rows) => {
    const escapeXml = (value) =>
      value.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

    const header =
      '<?xml version="1.0" encoding="UTF-8"?>' +
      '<?mso-application progid="Excel.Sheet"?>' +
      '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' +
      '<Worksheet ss:Name="Stemmen"><Table>';

    const body = rows
      .map((row) => {
        const cells = row
          .map((value) => {
            const isNumber = /^-?\d+(?:\.\d+)?$/.test(value);
            const type = isNumber ? 'Number' : 'String';
            const cellValue = escapeXml(value);
            return '<Cell><Data ss:Type="' + type + '">' + cellValue + '</Data></Cell>';
          })
          .join('');
        return '<Row>' + cells + '</Row>';
      })
      .join('');

    const footer = '</Table></Worksheet></Workbook>';
    return header + body + footer;
  };

  buttons.forEach((button) => {
    button.addEventListener('click', () => {
      const tableId = button.getAttribute('data-export-table');
      const table = tableId ? document.getElementById(tableId) : null;
      if (!table) return;

      const filterMode = button.getAttribute('data-export-filter');
      const format = (button.getAttribute('data-export-format') || 'csv').toLowerCase();
      const rows = buildRows(table, filterMode);
      const suffix = filterMode === 'with-votes' ? '-alleen-stemmen' : '-alle';

      if (!rows.length) return;

      if (format === 'xlsx') {
        const xml = toSpreadsheetXml(rows);
        download(xml, 'application/vnd.ms-excel', 'volume75-stemmen' + suffix + '.xlsx');
        return;
      }

      const csv = toCsv(rows);
      download(csv, 'text/csv;charset=utf-8;', 'volume75-stemmen' + suffix + '.csv');
    });
  });
})();
</script>
