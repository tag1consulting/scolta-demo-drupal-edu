/**
 * @file
 * Rich search result cards for Meridian AI.
 *
 * Registers two Scolta renderers: a result renderer that paints an item's lead
 * image alongside the title, highlighted excerpt and facet badges, and a
 * suggestion renderer that puts the same image on the search-as-you-type rows.
 * Everything they need comes from the search index — the thumbnail URL and the
 * badge labels ride along in the fragment's meta map, put there by
 * meridian_scolta_scolta_content_item_alter() — so neither a card nor a
 * suggestion costs a per-result server call.
 *
 * Load order matters. scolta.js defines window.Scolta when it executes and
 * Drupal's scolta bridge behavior calls Scolta.init() on DOMContentLoaded, so
 * this file must run after the former and before the latter. Declaring
 * scolta/search as a dependency and leaving the library in the footer puts it
 * exactly there; registering at top level (not inside a DOMContentLoaded
 * handler) keeps it there.
 */
(function (global) {
  'use strict';

  if (!global.Scolta || typeof global.Scolta.setResultRenderer !== 'function') {
    // A bundle without the render seam is not something to work around here.
    console.warn('[meridian] Scolta.setResultRenderer unavailable; leaving the built-in card in place.');
    return;
  }

  var ENTITIES = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
  };

  function escapeHtml(value) {
    return String(value === null || value === undefined ? '' : value)
      .replace(/[&<>"']/g, function (c) { return ENTITIES[c]; });
  }

  /**
   * Escapes a URL for an attribute and neutralizes non-http(s) schemes.
   *
   * The indexer already refuses anything that is not http(s) or root-relative,
   * but the URL still arrives here as raw index data, so it gets the same
   * treatment Scolta gives the result href rather than an assumption about who
   * wrote it.
   */
  function safeImageUrl(value) {
    var url = String(value === null || value === undefined ? '' : value).trim();
    if (url === '') {
      return '';
    }
    if (/^[a-z][a-z0-9+.-]*:/i.test(url) && !/^https?:/i.test(url)) {
      return '';
    }
    return escapeHtml(url);
  }

  /**
   * Drops the thumbnail when its image fails to load.
   *
   * Adding a class rather than removing the node keeps the handler cheap and
   * lets the stylesheet decide what an imageless rich card looks like. Two of
   * this corpus's images are still remote Unsplash URLs, so this is a path the
   * demo can actually take on a filtered network, not only a defensive guard.
   */
  global.meridianScoltaThumbFailed = function (img) {
    var card = img.closest ? img.closest('.meridian-result') : null;
    if (card) {
      card.classList.add('meridian-result--thumb-failed');
    }
  };

  /**
   * How many badges a card paints. Mirrors the indexer's own cap, which is
   * what actually bounds the string; this is the client-side belt to it.
   */
  var BADGE_LIMIT = 3;

  /**
   * Renders a result's facet badges.
   *
   * data.meta.badges is raw index data: a JSON-encoded array of facet values —
   * content type, school, level — already ordered and capped by
   * meridian_scolta_scolta_content_item_alter(). JSON and not a delimited
   * string because a taxonomy label is free text, and "School of Language &
   * Reasoning" is already carrying an ampersand.
   *
   * Anything that does not parse into an array counts as no badges. An item
   * without them simply shows none — the same graceful path a missing image
   * takes, not a broken card.
   */
  function badges(encoded) {
    if (!encoded) {
      return '';
    }
    var labels;
    try {
      labels = JSON.parse(encoded);
    } catch (e) {
      return '';
    }
    if (!Array.isArray(labels)) {
      return '';
    }
    var out = '';
    for (var i = 0; i < labels.length && i < BADGE_LIMIT; i++) {
      var label = String(labels[i] === null || labels[i] === undefined ? '' : labels[i]).trim();
      if (label !== '') {
        out += '<span class="meridian-result__badge">' + escapeHtml(label) + '</span>';
      }
    }
    return out;
  }

  /**
   * Renders one result.
   *
   * Escaping: every ctx value used here ends in Html, Attr or Text, or is
   * safeUrl, so Scolta has already escaped it exactly as its own card would.
   * Everything read from data.meta — image, image_alt, badges — is raw index
   * data and is escaped here. ctx.query and ctx.highlightTerms are raw and
   * never reach the markup.
   *
   * An item with no lead image gets the same card without the thumbnail, not
   * Scolta's built-in one. Of the 162 things indexed here, 90 have no image at
   * all — courses and resource articles carry none — and mixing two card
   * designs down one result list reads as a broken page rather than a designed
   * fallback.
   */
  global.Scolta.setResultRenderer(function (data, ctx) {
    var meta = (data && data.meta) || {};
    var imageUrl = safeImageUrl(meta.image);
    var alt = escapeHtml(meta.image_alt || '');
    var badgeHtml = badges(meta.badges);

    var metaRow = '';
    if (ctx.dateHtml || badgeHtml) {
      metaRow = '<div class="meridian-result__meta">'
        + (ctx.dateHtml ? '<span class="meridian-result__date">' + ctx.dateHtml + '</span>' : '')
        + badgeHtml
        + '</div>';
    }

    // The thumbnail is decorative: the title link beside it goes to the same
    // place, so it stays out of the tab order and out of the accessible tree.
    var thumb = imageUrl === '' ? ''
      : '<a class="meridian-result__thumb" href="' + ctx.safeUrl + '" target="_blank" rel="noopener"'
        + ' tabindex="-1" aria-hidden="true">'
        + '<img src="' + imageUrl + '" alt="' + alt + '" loading="lazy" decoding="async"'
        + ' onerror="meridianScoltaThumbFailed(this)">'
        + '</a>';

    // target/rel match the built-in card: within one result list, a card with
    // a thumbnail must not open differently from one without.
    return '<div class="scolta-result-card meridian-result">'
      + thumb
      + '<div class="meridian-result__body">'
      + '<a class="scolta-result-title meridian-result__title" href="' + ctx.safeUrl + '"'
      + ' target="_blank" rel="noopener" title="' + ctx.titleAttr + '">' + ctx.titleHtml + '</a>'
      + metaRow
      + '<div class="scolta-result-excerpt meridian-result__excerpt">' + ctx.excerptHtml + '</div>'
      + '</div>'
      + '</div>';
  });

  // Behind its own guard rather than the file-level one: this seam landed
  // after setResultRenderer, so a bundle old enough to lack it still gets the
  // rich cards above, and the dropdown degrades to the themed but imageless
  // rows instead of throwing.
  if (typeof global.Scolta.setSuggestionRenderer !== 'function') {
    return;
  }

  /**
   * Empties a suggestion thumbnail whose image fails to load.
   *
   * The box stays and becomes the same invisible spacer an imageless row uses,
   * rather than being removed: dropping it would pull the row's text leftwards
   * out of line with its neighbours, which is a worse artifact than a blank
   * gap. Nothing else in the row moves, so a failed image costs no layout
   * shift.
   */
  global.meridianScoltaSaytThumbFailed = function (img) {
    var box = img.closest ? img.closest('.meridian-sayt__thumb') : null;
    if (box) {
      box.removeChild(img);
      box.classList.add('meridian-sayt__thumb--empty');
    }
  };

  /**
   * Renders one search-as-you-type suggestion row.
   *
   * Returns the row's INNER markup only. The option element around it is the
   * bundle's, and it is what carries the combobox contract — role="option",
   * the stable id the input's aria-activedescendant points at, aria-selected,
   * the data-scolta-sayt-index the keyboard and click handlers dispatch on,
   * and the href in navigate mode. None of that is restated here, because a
   * renderer cannot break by omission what it never writes.
   *
   * Escaping: ctx.titleHtml and ctx.excerptHtml arrive pre-escaped, escaped
   * exactly as the built-in row escapes them. suggestion.meta.* is raw index
   * data and is escaped here. ctx.query is raw and never reaches the markup.
   *
   * A recent search is handed back to the built-in row by returning null: it
   * has no fragment, no image and nothing to add, and the built-in row is
   * already the themed glyph treatment this dropdown wants for history. A
   * title suggestion with no image gets this same row minus the thumbnail,
   * never the built-in one — mixing two row designs in one list reads as a
   * broken dropdown rather than a designed fallback, the lesson the cards
   * already learned.
   */
  global.Scolta.setSuggestionRenderer(function (suggestion, ctx) {
    if (!suggestion || suggestion.type !== 'title') {
      return null;
    }

    var meta = suggestion.meta || {};
    var imageUrl = safeImageUrl(meta.image);

    // Decorative, and deliberately not carrying meta.image_alt: an option's
    // accessible name is computed from its contents, so alt text here would be
    // announced in front of the title it illustrates — "Large language model
    // neural network visualization, MS in Large Language Model Engineering".
    // The title beside it already names the row.
    //
    // A title suggestion with no image still gets the box, empty and with its
    // border and fill removed. Most of this corpus carries no image, so
    // without the spacer a dropdown mixes indented and flush-left rows and
    // stops reading as one list — the same reason the cards do not fall back
    // to a second design. An invisible spacer buys that alignment without
    // painting an empty grey square for the rows that have nothing to show.
    var thumb = imageUrl === ''
      ? '<span class="meridian-sayt__thumb meridian-sayt__thumb--empty" aria-hidden="true"></span>'
      : '<span class="meridian-sayt__thumb" aria-hidden="true">'
        + '<img src="' + imageUrl + '" alt="" loading="lazy" decoding="async"'
        + ' onerror="meridianScoltaSaytThumbFailed(this)">'
        + '</span>';

    return '<span class="meridian-sayt">'
      + thumb
      // Both classes on purpose. The scolta-* one carries the look the theme
      // already gives a suggestion's title and excerpt, so a title row and a
      // recent-search row stay typographically identical; the meridian-* one
      // adds only the layout this row needs. Two classes at the same
      // specificity, resolved by source order, rather than a nested selector.
      + '<span class="meridian-sayt__text">'
      + '<span class="scolta-sayt-title meridian-sayt__title">' + ctx.titleHtml + '</span>'
      + (ctx.excerptHtml
        ? '<span class="scolta-sayt-excerpt meridian-sayt__excerpt">' + ctx.excerptHtml + '</span>'
        : '')
      + '</span>'
      + '</span>';
  });

})(window);
