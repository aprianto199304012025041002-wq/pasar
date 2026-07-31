</main>

  <footer class="app-footer">
    <div class="footer-inner">
      <span>© QOS Balmon Jogja Team <?= date('Y') ?></span>
    </div>
  </footer>

</div><!-- .app-wrapper -->

<script src="<?= APP_BASE_URL ?>/assets/js/app.js?v=<?= file_exists(__DIR__ . '/../assets/js/app.js') ? filemtime(__DIR__ . '/../assets/js/app.js') : '0' ?>"></script>
</body>
</html>