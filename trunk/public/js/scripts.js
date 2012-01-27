function poptastic(url, service) {
	popUp = window.open(url, 'name', 'height=600,width=800');
	if (window.focus) {
		popUp.focus()
	}
	window.location = '/welcome/connect/service/' + service;
}

/* Start Flash Message Functions */
$(document).ready(function() {
	$('#flashes div').each(function() {
		$.pnotify({
			pnotify_title: $(this).find('p.title').html(),
			pnotify_text: $(this).find('p.message').html(),
			pnotify_type: $(this).find('p.type').html(),
			pnotify_hide: $(this).find('p.sticky').html(),
		});
	});
});