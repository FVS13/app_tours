$(window).on('load', function () {
	$(".adm_head_selects input, .adm_head_selects select").change(function () {
		$(".adm_head_buttons_right button, .adm_head_buttons .primenit_istochnik").html('Применить').addClass('apply').removeClass('reset').prop('disabled', false);
	});

	$("body").on('submit', '.vhod_dannie_filter_form', function () {
		let $button = $('.adm_head_buttons .primenit_istochnik');

		if ($button.hasClass('reset')) {
			$('#select_transp').select2('val', 0);

			$button.html('Применить').addClass('apply').removeClass('reset').prop('disabled', true);
		} else {
			$button.html('Сбросить').addClass('reset').removeClass('apply')
		}

		let tours_types = $('#select_transp').val();
		let $elems = $('.napravl_table tr');
		let count = 0;
		for (let i = 1; i < $elems.length; i++) {
			const tour_type = $elems[i].dataset.tourType;

			$is_filtered = false;

			$is_filtered |= tours_types.length > 0
				&& -1 === tours_types.indexOf(`${tour_type}`);

			if ($is_filtered) {
				$($elems[i]).hide();
			} else {
				$($elems[i]).show();
				count++;
			}
		}
		$('#view-data-table_info').text('Показано записей ' + count);
		return false;
	});
});
