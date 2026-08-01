  </main>

  <footer class="app-footer">
    © QOS Balmon Jogja Team <?= date('Y') ?>
  </footer>

</div><!-- .app-wrapper -->

<script src="<?= APP_BASE_URL ?>/assets/js/app.js?v=<?= file_exists(__DIR__ . '/../assets/js/app.js') ? filemtime(__DIR__ . '/../assets/js/app.js') : '0' ?>"></script>
</body>
</html>
