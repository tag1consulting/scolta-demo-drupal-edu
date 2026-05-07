(function (Drupal) {
  Drupal.behaviors.cardLink = {
    attach: function (context) {
      context.querySelectorAll('.node--view-mode-teaser').forEach(function (card) {
        if (card.dataset.cardLinkAttached) return;
        card.dataset.cardLinkAttached = '1';
        var link = card.querySelector('.node__title a');
        if (!link) return;
        card.style.cursor = 'pointer';
        card.addEventListener('click', function (e) {
          if (e.target.closest('a')) return;
          window.location.href = link.href;
        });
      });
    }
  };
})(Drupal);
