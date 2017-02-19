(function($) {
	if (sublanguage) {
		$.ajaxSetup({
			beforeSend: function (jqXHR, settings) {
				if (settings.type=='POST') {
					settings.data = (settings.data ? settings.data+'&' : '')+sublanguage.query_var+"="+sublanguage.current;
				} else {
					settings.url += (settings.url.indexOf('?') > -1 ? '&' : '?')+sublanguage.query_var+"="+sublanguage.current;
				}
			}
		});
	}
}(jQuery))