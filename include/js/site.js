jQuery(document).ready(function($) {
  $( document ).ajaxSend(function( event, jqxhr, settings ) {
    settings.url += ((settings.url.indexOf("?") > -1) ? "&" : "?") + sublanguage.qv+"="+sublanguage.current;
  });
});