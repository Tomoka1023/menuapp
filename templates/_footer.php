</main>
<footer class="site-footer">© <?=date('Y')?> Menu App</footer>
<script>
  // JSからもBASE_URLを使えるようにする
  window.BASE_URL = "<?= BASE_URL ?>";
</script>
<script src="<?= BASE_URL ?>/assets/js/week.js" defer></script>

<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/menuapp/public/sw.js').catch(console.error);
  });
}
</script>

</body>
</html>
