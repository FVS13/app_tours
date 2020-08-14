$(window).on('load', function () {
	function template_spoiler(details) {
		let $result_html = '';
		let details_ordering = ['Y', 'C', 'BUS'];

		for (let i = 0; i < details_ordering.length; i++) {
			const tour_type = details_ordering[i];
			let tour_type_label = '';

			switch (tour_type) {
				case 'C':
					tour_type_label = 'А-Б';
					break;
				case 'Y':
					tour_type_label = 'А-Э';
					break;
				case 'BUS':
					tour_type_label = 'Авто';
					break;
			}

			let collected_gmt = details[tour_type]['collected-gmt'] || '-';
			let validities = details[tour_type]['validities'].join(' ');

			$result_html += `
				<div class="naprav_podtablica">
					<div class="naprav_podtablica_row">${tour_type_label}</div>
					<div class="naprav_podtablica_row">${details[tour_type]['distance']}</div>
					<div class="naprav_podtablica_row">
						${details[tour_type]['count-all-dates']}/${details[tour_type]['count-target-dates']}
					</div>
					<div class="naprav_podtablica_row">${collected_gmt}</div>
					<div class="naprav_podtablica_row">${validities}</div>
				</div>`;
		}
		for (const key in details) {
			if (details.hasOwnProperty(key)) {

			}
		}

		return $result_html;
	}

	async function load_route_detail(route_id, $root, callback) {
		let response = await fetch('/routes/get-detail-by-route?route_id=' + route_id);
		let details_arr = await response.json();

		if (details_arr !== null) {
			details_arr = details_arr['details'];
		}

		callback(details_arr, $root);
	}

	var is_loaded_routes_details = {};


	$("body").on('click', '.adm_tabl_spoiler .adm_spoiler_plus, .adm_tabl_spoiler .adm_tab_spoiler_block:nth-child(3)', function () {
		let th = $(this);

		if (th.parent(".adm_tabl_spoiler_otkrito").children(".adm_spoiler_plus").html() == "-") {
			th.parent(".adm_tabl_spoiler_otkrito").parent(".adm_tabl_spoiler").children(".adm_tabl_spoiler_skrito").removeClass("spoiler_otkrit");
			th.parent(".adm_tabl_spoiler_otkrito").children(".adm_spoiler_plus").removeClass('spoiler_minus');
			th.parent(".adm_tabl_spoiler_otkrito").children(".adm_spoiler_plus").html("+");
		} else if (th.parent(".adm_tabl_spoiler_otkrito").children(".adm_spoiler_plus").html() == "+") {
			let $root = th.closest(".adm_tabl_spoiler");
			let roure_id = $root.data('route-id');

			if (is_loaded_routes_details[roure_id] !== true) {
				is_loaded_routes_details[roure_id] = true;

				load_route_detail(roure_id, $root, function (details_arr) {
					details_html = template_spoiler(details_arr);
					$root.find(".adm_tabl_spoiler_skrito").html(details_html);
				});

			}

			$root.children(".adm_tabl_spoiler_skrito").addClass("spoiler_otkrit");
			$root.find(".adm_spoiler_plus").html("-");
			$root.find(".adm_spoiler_plus").addClass('spoiler_minus');
		}
		return false;
	});

	/**
	 * Фильтры
	 */
	$(".adm_head_selects input, .adm_head_selects select").change(function () {
		$(".adm_head_buttons_right button").html('Применить').addClass('apply').removeClass('reset').prop('disabled', false);
	});

	$("body").on('submit', '.napr_form', function () {
		let $button = $('.adm_head_buttons_right button');

		if ($button.hasClass('reset')) {
			$('#select_gorod_otpravleniya').select2('val', 0);
			$('#select_gorod_pribitiya').select2('val', 0);
			$('#select_zapisi').select2('val', 0);
			$('#select_istochn').select2('val', 0);

			$button.html('Применить').addClass('apply').removeClass('reset').prop('disabled', true);
		} else {
			$button.html('Сбросить').addClass('reset').removeClass('apply')
		}

		let gorod_otpravleniya_array = $('#select_gorod_otpravleniya').val();
		let gorod_pribitiya_array = $('#select_gorod_pribitiya').val();
		let zapisi = $('#select_zapisi').val();
		let istochn = $('#select_istochn').val();
		let $elems = $('.adm_tabl_div_content .adm_tabl_spoiler');

		console.dir(zapisi);

		window.last_state = [
			gorod_otpravleniya_array,
			gorod_pribitiya_array,
			zapisi,
			istochn,
		];
		let count = 0;
		for (let i = 0; i < $elems.length; i++) {
			const departure_city_id = $elems[i].dataset.departure_city_id;
			const arrival_city_id = $elems[i].dataset.arrival_city_id;
			const all_count = $elems[i].dataset.all_count;
			const count_auto = $elems[i].dataset.count_auto;
			const count_air = $elems[i].dataset.count_air;
			const validities = $elems[i].dataset.validities.split(' ');

			// const child_elements = $elems[i].getElementsByClassName('adm_tabl_spoiler_otkrito')[0];

			$is_filtered = false;

			$is_filtered |= gorod_otpravleniya_array.length > 0
				&& -1 === gorod_otpravleniya_array.indexOf(`${departure_city_id}`);

			$is_filtered |= gorod_pribitiya_array.length > 0
				&& -1 === gorod_pribitiya_array.indexOf(`${arrival_city_id}`);

			$not_is_count_filtered = false;

			if (zapisi.length > 0) {
				zapisi.forEach(elem => {
					switch (elem) {
						case 'isset_air':
							$not_is_count_filtered |= count_air > 0;
							break;
						case 'isset_auto':
							$not_is_count_filtered |= count_auto > 0;
							break;
						case 'empty':
							$not_is_count_filtered |= 0 == all_count;
							break;
					}
				});
			} else {
				$not_is_count_filtered = true;
			}

			$is_filtered |= !$not_is_count_filtered;
			$is_filtered |= 0 != istochn && 0 === istochn.filter(value => ~validities.indexOf(value)).length;

			if ($is_filtered) {
				$($elems[i]).hide();
			} else {
				$($elems[i]).show();
				count++;
			}
		}

		renumbering.call($('.adm_tabl_spoiler:visible').toArray());

		$('#view-data-table_info').text('Показано записей ' + count);
		return false;
	});
	$(document).ready(function(){
		$('#view-data-table_info').text('Показано записей ' + $('.adm_tabl_div_content .adm_tabl_spoiler:visible').length);
	})
	/**
	 * Сортировки
	 */
	$("table th a.sort, .adm_tab_head_block a.sort").click(function () {
		let sorted_elems = $('.adm_tabl_spoiler').toArray();
		let is_up = $(this).hasClass('sort_vverh');
		let target_class = '';

		switch ($(this).data('sort')) {
			case 'napr':
				target_class = '.route_name';
				break;
			case 'kod_napr':
				target_class = '.route_code';
				break;
		}

		target_class = '.adm_tab_spoiler_block' + target_class;

		sortRows.call(sorted_elems, is_up, target_class);

		/**
		 * Для удаления результата всех предыдущих сортировок
		 * Например, по другим колонкам
		 */
		$(this).closest('.adm_tabl_head').find('.adm_tab_head_block a.sort').removeClass('sort_vverh sort_vniz');

		if (is_up) {
			$(this).addClass('sort_vniz');
		} else {
			$(this).addClass('sort_vverh');
		}

		updateSortedRows(sorted_elems);
	});

	function renumbering() {
		let $i = 1;

		this.forEach(elem => {
			$(elem).find('.route_id').html($i++);
		});
	}

	function sortRows(is_sort_asc, target_class) {
		this.sort(function (a, b) {
			var compA = $(a).find(target_class).text().toUpperCase();
			var compB = $(b).find(target_class).text().toUpperCase();

			if (is_sort_asc) {
				return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
			}

			return (compA > compB) ? -1 : (compA < compB) ? 1 : 0;
		});
	}

	function updateSortedRows(sorted_elems) {
		renumbering.call(sorted_elems);
		$('.adm_tabl_div_content').append(sorted_elems);
	}

	(() => {
		let sorted_elems = $('.adm_tabl_spoiler').toArray();
		let is_up = true;
		let target_class = '.adm_tab_spoiler_block.route_name';

		sortRows.call(sorted_elems, is_up, target_class);
		updateSortedRows(sorted_elems);
	})();
});
