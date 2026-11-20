<?php
/**
 * ARCANA — Shared Footer Template
 */
?>
<!-- Footer -->
<footer class="footer">
  <div class="footer__inner">
    <div class="footer__brand">
      <a href="index.php" class="footer__logo">
        <svg class="navbar__logo-icon" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M28 18.5C27.5 24.5 22.5 29 16.5 29C9.6 29 4 23.4 4 16.5C4 10.5 8.5 5.5 14.5 5C12 8 11.5 12.5 13.5 16C15.5 19.5 19.5 21 23 20C21 19.5 28 18.5 28 18.5Z" fill="currentColor" />
          <circle cx="22" cy="8" r="1.5" fill="currentColor" opacity="0.6" />
          <circle cx="25" cy="12" r="1" fill="currentColor" opacity="0.4" />
        </svg>
        Arcana
      </a>
      <div class="footer__socials">
        <a href="https://www.instagram.com/arcana_mbs.ctes?stkn=YmJhdnQ0ang4b3Bl" target="_blank" rel="noopener" class="footer__social" aria-label="Instagram">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="20" height="20" x="2" y="2" rx="5" ry="5" />
            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
            <line x1="17.5" x2="17.51" y1="6.5" y2="6.5" />
          </svg>
        </a>
        <a href="https://wa.me/" target="_blank" rel="noopener" class="footer__social" aria-label="WhatsApp">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
          </svg>
        </a>
       
      </div>
    </div>
    <div>
      <h4 class="footer__heading">Servicios</h4>
      <a href="services.php" class="footer__link">Manicura</a>
      <a href="services.php" class="footer__link">Pedicura</a>
      <a href="services.php" class="footer__link">Uñas esculpidas</a>
      <a href="services.php" class="footer__link">Faciales & Spa</a>
    </div>
    <div>
      <h4 class="footer__heading">Empresa</h4>
      <a href="about-us.php" class="footer__link">Nosotros</a>
      <a href="agenda.php" class="footer__link">Agenda</a>
      <a href="contact.php" class="footer__link">Contacto</a>
    </div>
    <div>
      <h4 class="footer__heading">Recursos</h4>
      <a href="about-us.php" class="footer__link">Preguntas frecuentes</a>
      <a href="contact.php" class="footer__link">Política de privacidad</a>
      <a href="contact.php" class="footer__link">Términos y condiciones</a>
      <a href="services.php" class="footer__link">Cuidados personales</a>
    </div>
    <div>
      <h4 class="footer__heading">Recibe novedades</h4>
      <div class="footer__newsletter">
        <input type="email" class="footer__newsletter-input" placeholder="Tu correo electrónico...">
        <button class="footer__newsletter-btn" type="button">Suscribirse</button>
      </div>
    </div>
  </div>
  <div class="footer__bottom">
    <span><strong>Arcana Moon Beauty Secret</strong> — <?= date('Y') ?> &copy; Todos los derechos reservados</span>
  </div>
</footer>
<script>
  if (window.lucide) {
    lucide.createIcons();
  }
</script>
</body>
</html>
