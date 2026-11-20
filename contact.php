<?php
/**
 * ARCANA — Contact Page (contact.php)
 */

$pageTitle = 'Contacto — Arcana Moon Beauty Secret';
$pageDescription = 'Contáctanos para dudas, consultas o presupuestos personalizados.';
$pageName = 'contact';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="contact-page" style="padding-top: 100px; padding-bottom: 3rem;">
  <div class="container">
    <div class="glass contact-page__inner">
      <div class="contact-page__form">
        <h1 class="contact-page__title text-section text-section-xl">Contáctanos</h1>
        <p class="contact-page__subtitle text-body">Si tienes preguntas, envíanos un mensaje y te responderemos pronto.</p>
        <form id="contact-form">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="contact-nombre">Nombre</label>
              <input type="text" id="contact-nombre" class="form-input" placeholder="Escribe tu nombre" required value="<?= htmlspecialchars($currentUser['nombre'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label" for="contact-apellido">Apellido</label>
              <input type="text" id="contact-apellido" class="form-input" placeholder="Escribe tu apellido" required value="<?= htmlspecialchars($currentUser['apellido'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label" for="contact-email">Correo Electrónico</label>
            <input type="email" id="contact-email" class="form-input" placeholder="correo@gmail.com" required value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label" for="contact-telefono">Teléfono (opcional)</label>
            <input type="tel" id="contact-telefono" class="form-input" placeholder="Ej: 3794009000" value="<?= htmlspecialchars($currentUser['telefono'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label" for="contact-direccion">Dirección (opcional)</label>
            <input type="text" id="contact-direccion" class="form-input" placeholder="Escribe tu dirección">
          </div>
          <div class="form-group">
            <label class="form-label" for="contact-mensaje">Mensaje</label>
            <textarea id="contact-mensaje" class="form-textarea" placeholder="Escribe tu mensaje o consulta..." required></textarea>
          </div>
          <button type="submit" class="btn-pill btn-pill--primary btn-pill--full" style="margin-top: 1rem;">Enviar mensaje</button>
        </form>
      </div>
      <img src="assets/images/salon-treatment-room.jpg" alt="Sala de tratamiento Arcana" class="contact-page__image">
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
