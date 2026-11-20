<?php
/**
 * ARCANA — Home Page (index.php)
 */

$pageTitle = 'Arcana — Tu momento de belleza única';
$pageDescription = 'Arcana Beauty Secret — Estética premium y bienestar. Manicura, pedicura, faciales, masajes y más.';
$pageName = 'home';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Hero -->
<section class="hero">
  <div class="container">
    <div class="hero__inner">
      <div class="hero__content">
        <p class="hero__label">Estética &amp; Bienestar</p>
        <h1 class="hero__title text-display">Arcana: Tu momento de belleza única</h1>

        <div class="hero__phrases">
          <div class="hero__phrase hero__phrase--active">
            <span class="hero__phrase-line">Relajación profunda,</span>
            <span class="hero__phrase-line">masajes que renuevan</span>
            <span class="hero__phrase-line">cuerpo y mente.</span>
          </div>
          <div class="hero__phrase">
            <span class="hero__phrase-line">Belleza radiante,</span>
            <span class="hero__phrase-line">faciales que iluminan</span>
            <span class="hero__phrase-line">tu piel natural.</span>
          </div>
          <div class="hero__phrase">
            <span class="hero__phrase-line">Manos y pies perfectos,</span>
            <span class="hero__phrase-line">manicura y pedicura</span>
            <span class="hero__phrase-line">con amor.</span>
          </div>
        </div>

        <p class="hero__subtitle">Experiencias de estética para combinar bienestar, cuidado personal y resultados que realmente se notan.</p>
        <div class="hero__cta">
          <a href="agenda.php" class="btn-pill">Reserva tu cita</a>
        </div>
      </div>
      <div class="hero__image-wrap reveal">
        <img src="assets/images/hero-esthetician.jpg" alt="Esteticista profesional trabajando" class="hero__image">
      </div>
    </div>
  </div>
</section>

<!-- Why Choose Us -->
<section class="why">
  <div class="container">
    <div class="glass" style="padding: 3rem;">
      <h2 class="text-section text-section-lg" style="text-align: center;">¿Por qué elegirnos?</h2>
      <div class="why__grid">
        <div class="why__card reveal" data-reveal-delay="0">
          <div class="why__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
              <circle cx="12" cy="10" r="3" />
            </svg>
          </div>
          <h3 class="why__card-title">Ubicación Privilegiada</h3>
          <p class="why__card-text">Céntrico y accesible en Salta 929</p>
        </div>
        <div class="why__card reveal" data-reveal-delay="0.1">
          <div class="why__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M2 20a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8l-7 5V8l-7 5V4a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z" />
              <path d="M17 18h1" /><path d="M12 18h1" /><path d="M7 18h1" />
            </svg>
          </div>
          <h3 class="why__card-title">Lugar Relajante</h3>
          <p class="why__card-text">Ambiente de paz y armonía</p>
        </div>
        <div class="why__card reveal" data-reveal-delay="0.2">
          <div class="why__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z" />
              <path d="M5 3v4" /><path d="M19 17v4" /><path d="M3 5h4" /><path d="M17 19h4" />
            </svg>
          </div>
          <h3 class="why__card-title">Atención Personalizada</h3>
          <p class="why__card-text">Tratamientos y asesoría únicos</p>
        </div>
        <div class="why__card reveal" data-reveal-delay="0.3">
          <div class="why__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
              <path d="m9 12 2 2 4-4" />
            </svg>
          </div>
          <h3 class="why__card-title">Higiene Garantizada</h3>
          <p class="why__card-text">Protocolos de bioseguridad y esterilización</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Featured Services -->
<section class="featured">
  <div class="container">
    <h2 class="text-section text-section-lg" style="text-align: center;">Servicios destacados</h2>
    <div class="featured__grid" id="featured-grid"></div>
    <div class="featured__more-wrap">
      <a href="services.php" class="featured__more">
        Ver todos los servicios
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M5 12h14" />
          <path d="m12 5 7 7-7 7" />
        </svg>
      </a>
    </div>
  </div>
</section>

<!-- Contact Info -->
<section class="contact-info">
  <div class="container">
    <div class="glass contact-info__inner">
      <div class="contact-info__map reveal">
        <iframe
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1770.0274772048322!2d-58.839911789802926!3d-27.467548455593004!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94456ca4bddaca8b%3A0x83b1aa5fa5c1d53d!2sSalta%20929%2C%20W3400%20BLL%2C%20Corrientes!5e0!3m2!1ses-419!2sar!4v1782698567454!5m2!1ses-419!2sar"
          width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"
          referrerpolicy="strict-origin-when-cross-origin"></iframe>
      </div>
      <div class="contact-info__details">
        <div class="contact-info__item">
          <div class="contact-info__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
              <circle cx="12" cy="10" r="3" />
            </svg>
          </div>
          <span class="contact-info__text">Salta 929, Corrientes</span>
        </div>
        <div class="contact-info__item">
          <div class="contact-info__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect width="20" height="20" x="2" y="2" rx="5" ry="5" />
              <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
              <line x1="17.5" x2="17.51" y1="6.5" y2="6.5" />
            </svg>
          </div>
          <span class="contact-info__text">@arcana_mbs.ctes</span>
        </div>
        <div class="contact-info__item">
          <div class="contact-info__icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
            </svg>
          </div>
          <span class="contact-info__text">3794-928407</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA Banner -->
<section class="cta-banner">
  <div class="container">
    <div class="glass cta-banner__inner reveal">
      <h2 class="text-section" style="font-style: italic;">¡Reserva tu cita hoy y siente la diferencia!</h2>
      <a href="agenda.php" class="btn-pill btn-pill--outline" style="margin-top: 1.5rem;">Agendar ahora</a>
    </div>
  </div>
</section>

<!-- Service Card Template -->
<template id="service-card-template">
  <article class="service-card glass--hover">
    <a class="service-card__link-wrap" href="#">
      <div class="service-card__image-wrap">
        <img class="service-card__image" src="" alt="">
      </div>
    </a>
    <div class="service-card__content">
      <h3 class="service-card__name"><a class="service-card__title-link" href="#" style="color: inherit; text-decoration: none;"></a></h3>
      <p class="service-card__description"></p>
      <p class="service-card__specialist">Especialista: <span class="specialist-name"></span></p>
      <div class="service-card__divider"></div>
      <div class="service-card__footer">
        <span class="service-card__price"></span>
        <span class="service-card__duration"></span>
      </div>
      <div style="margin-top: 0.85rem;">
        <a class="service-card__btn btn-pill btn-pill--sm btn-pill--outline" href="#" style="width: 100%; text-align: center; text-decoration: none; display: block; font-size: 0.82rem;">
          Ver Cuidados y Detalles →
        </a>
      </div>
    </div>
  </article>
</template>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
