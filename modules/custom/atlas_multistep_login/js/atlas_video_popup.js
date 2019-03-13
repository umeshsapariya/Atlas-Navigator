Drupal.behaviors.atlas_multisteplogin = {
  attach: function (context, settings) {
    jQuery('.node--type-page .popup-youtube').magnificPopup({
      disableOn: 700,
      type: 'iframe',
      mainClass: 'mfp-fade',
      removalDelay: 160,
      preloader: false,
      fixedContentPos: false
    });
    jQuery(".node--type-page .popup-youtube").trigger("click");
  }
};
