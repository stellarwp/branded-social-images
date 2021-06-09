(function($) {
	$.fn.isInViewport = function() {
		var elementTop = $(this).offset().top;
		var elementBottom = elementTop + $(this).outerHeight();

		var viewportTop = $(window).scrollTop();
		var viewportBottom = viewportTop + $(window).height();

		return elementBottom > viewportTop && elementTop < viewportBottom;
	};
	var container = $('#carbon_fields_container_og_image');
	if (container.length > 0) {
		container.after('<div id="cls-og-preview"></div>');
		var og_preview = {'_preview': 1};

		var loading = function () {
			return 'data:image/gif;base64,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';
		};

		var og_preview_url = function () {
			return cls_og.preview_url + '?' + $.param(og_preview);
		};

		var update_delay = false;

		$(document).on('carbonFields.apiLoaded', function (e, api) {
			$(document).on('carbonFields.fieldUpdated', function (e, fieldName) {
				// console.log(fieldName);
				if (update_delay) {
					clearTimeout(update_delay);
				}
				update_delay = setTimeout(function () {
					var v = api.getFieldValue(fieldName), p = $("#cls-og-preview");
					if (fieldName === 'cls_og_image') {
						og_preview.image = v; // this is an ID
					}
					if (fieldName === 'cls_og_text') {
						og_preview.text = encodeURIComponent(v);
					}
					if (fieldName === 'cls_og_text_position') {
						og_preview.text_position = encodeURIComponent(v);
					}
					if (fieldName === 'cls_og_color') {
						og_preview.color = encodeURIComponent(v);
					}
					if (fieldName === 'cls_og_background_color') {
						og_preview.background_color = encodeURIComponent(v);
					}
					if (fieldName === 'cls_og_text_stroke') {
						og_preview.text_stroke = encodeURIComponent(v);
					}
					if (fieldName === 'cls_og_text_stroke_color') {
						og_preview.text_stroke_color = encodeURIComponent(v);
					}
					if (fieldName === 'cls_og_text_shadow_color') {
						og_preview.text_shadow_color = encodeURIComponent(v);
					}
					if (fieldName === 'cls_og_text_shadow_left') {
						og_preview.text_shadow_left = encodeURIComponent(v);
					}
					if (fieldName === 'cls_og_text_shadow_top') {
						og_preview.text_shadow_top = encodeURIComponent(v);
					}
					if (fieldName === 'cls_og_text_enabled') {
						og_preview.text_enabled = v ? 'yes' : 'no';

						$("[name='_cls_og_text'],[name='_cls_og_text_position']").each(function () {
							console.log($(this).closest('div.carbon-field').toggle(v));
						});
					}
					if (fieldName === 'cls_og_logo_position') {
						og_preview.logo_position = encodeURIComponent(v);
					}
					if (fieldName === 'cls_og_logo_enabled') {
						og_preview.logo_enabled = v ? 'yes' : 'no';

						$("[name='_cls_og_logo_position']").each(function () {
							$(this).closest('div.carbon-field').toggle(v);
						});
					}

					var img = p.find('img');
					if (img.length === 0) {
						p.append('<img/>');
						img = p.find('img');
					}
					img.attr('src', loading());
					img.attr('src', og_preview_url());
				}, 1000);
			}).trigger('carbonFields.fieldUpdated', ['cls_og_logo_enabled']);
		});

		$(window).on('resize scroll', function () {
			var c = $('#carbon_fields_container_og_image');
			if (c.length > 0) {
				c.toggleClass('inView', c.isInViewport());
			}
		});
	}
})(jQuery);
