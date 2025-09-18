<?php foreach (flashes() as $f): ?>
  <div class="alert <?=htmlspecialchars($f['t'])?>"><?=htmlspecialchars($f['m'])?></div>
<?php endforeach; ?>
