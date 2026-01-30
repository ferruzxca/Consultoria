(() => {
  const targets = document.querySelectorAll(
    '.card, .feature-card, .price-card, .kpi, .chart-card, .metric, .list .item, .timeline-step, .service-hero, .hero-visual, .table, .portal, .two-up'
  );

  if (!targets.length) {
    return;
  }

  targets.forEach((el, index) => {
    el.classList.add('reveal');
    el.style.transitionDelay = `${Math.min(index * 40, 240)}ms`;
  });

  if (!('IntersectionObserver' in window)) {
    targets.forEach((el) => el.classList.add('is-visible'));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
  );

  targets.forEach((el) => observer.observe(el));
})();
