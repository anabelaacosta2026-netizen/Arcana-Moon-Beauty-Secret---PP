<?php
/**
 * ARCANA — Services Catalog Page (services.php)
 */

$pageTitle = 'Servicios — Arcana Moon Beauty Secret';
$pageDescription = 'Explora nuestra amplia variedad de tratamientos de estética, belleza y relajación.';
$pageName = 'services';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="services-page" style="padding-top: 100px; padding-bottom: 3rem;">
  <div class="container">
    <h1 class="text-section text-section-xl" style="text-align: center; margin-bottom: 2rem;">Nuestros servicios</h1>
    
    <button class="btn-pill btn-pill--sm sidebar-toggle" style="margin-bottom: 1rem;">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="4" x2="20" y1="12" y2="12" />
        <line x1="4" x2="20" y1="6" y2="6" />
        <line x1="4" x2="20" y1="18" y2="18" />
      </svg>
      Filtros
    </button>

    <div class="services-page__layout">
      <aside class="services-sidebar">
        <div class="services-sidebar__inner">
          <div class="services-sidebar__search">
            <span class="services-sidebar__search-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.3-4.3" />
              </svg>
            </span>
            <input type="text" id="search-input" class="form-input" placeholder="Buscar servicio...">
          </div>

          <div class="services-sidebar__section">
            <h3 class="services-sidebar__heading">Categorías</h3>
            <div class="services-sidebar__categories"></div>
          </div>

      
          <div class="services-sidebar__section">
            <h3 class="services-sidebar__heading">Precio Máximo</h3>
            <input type="range" id="price-range" class="form-range" min="0" max="60000" value="60000" step="1000">
            <div class="services-sidebar__price-values">
              <span>$0</span>
              <span id="price-value">$60.000</span>
            </div>
          </div>
        </div>
      </aside>

      <div class="services-page__content">
        <div class="services-grid" id="services-grid"></div>
      </div>
    </div>
  </div>
</main>

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
      <p class="service-card__modalidad">Modalidad: <span class="modalidad-name"></span></p>
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
