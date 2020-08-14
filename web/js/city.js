$(window).on('load', function () {
	$(".edit_city").click(function () {
		let $popup = $('.adm_popup.tracked-city');

		open_popup_window($popup);
		clean_popup($popup);
		fill_tracked_city_form($(this).closest('tr'), $popup, $(this).attr('data-action'));

		return false;
	});

	function edit_transit_city() {
		let $popup = $('.adm_popup.transit-city');

		open_popup_window($popup);
		clean_popup($popup);
		fill_transit_city_form(
			$(this).closest('tr'),
			$popup,
			$(this).attr('data-action'),
			$(this).attr('data-title'),
		);

		return false;
	}

	async function remove_transit_city() {
		let city_id = $(this).closest('tr').attr('data-city-id');
		let city_name = $(this).closest('tr').find('.city_name').html().trim();

		let is_confirmed = confirm(`Удалить аэропорт "${city_name}" ?`);

		if (!is_confirmed) {
			return false;
		}

		let action = $(this).attr('data-action');
		let response = await fetch('/citys/' + action, {
			method: 'POST',
			body: initFormData({
				'city_id': city_id
			})
		});

		let result = null;

		try {
			result = await response.json();
		} finally {
			if (response.ok) {
				document.body.dispatchEvent(new CustomEvent(action, { detail: result }));
			} else {
				if (result === null) {
					result = {};
				}

				display_errors_in_alert(result['errors']);
			}
		}
	}

	$(".edit_transit_city, .add_transit_city").click(edit_transit_city);
	$(".remove_transit_city").click(remove_transit_city);

	$("body").on('submit', '.adm_popup.tracked-city form, .adm_popup.transit-city form', async function (e) {
		e.preventDefault();
		e.stopPropagation();
		e.stopImmediatePropagation();
		let formData = new FormData($(this).toArray()[0]);

		let response = await fetch('/citys/' + formData.get('action'), {
			method: 'POST',
			body: formData
		});

		let result = null;

		try {
			result = await response.json();
		} finally {
			if (response.ok) {
				close_popup_window($(this).closest('.adm_popup'));
				document.body.dispatchEvent(new CustomEvent(formData.get('action'), { detail: result }));
			} else {
				if (result === null) {
					result = {};
				}

				display_errors_in_popup_window(result['errors'], $(this).closest('.adm_popup'));
			}
		}

		return false;
	});

	function display_errors_in_popup_window(errors, $popup) {
		if (typeof (errors) === 'null') {
			text = 'Возникла неизвестная ошибка';
			$popup.find('.errors').html('<div>' + text + '</div>');
			return false;
		}
		if (typeof (errors) === 'string') {
			$popup.find('.errors').html('<div>' + errors + '</div>');
			return false;
		}

		let errors_html = '';

		for (const attribute in errors) {
			if (errors.hasOwnProperty(attribute)) {
				const text = errors[attribute];

				errors_html += `<div>${text}</div>`;
			}
		}

		$popup.find('.errors').html(errors_html);
	}

	function display_errors_in_alert(errors) {
		if (typeof (errors) === 'null') {
			alert('Возникла неизвестная ошибка');
			return false;
		}

		if (typeof (errors) === 'string') {
			alert(errors);
			return false;
		}

		let errors_html = '';

		for (const attribute in errors) {
			if (errors.hasOwnProperty(attribute)) {
				const text = errors[attribute];

				errors_html += `${text}\n`;
			}
		}

		alert(errors_html);
	}

	function open_popup_window($popup) {
		$popup.fadeIn(300);
	}

	function close_popup_window($popup) {
		$popup.fadeOut(300);
	}

	function clean_popup($popup) {
		$popup.find('.errors').html('');
		$popup.find('input[type="text"], input[type="number"]').val('').prop('style', false);
		$popup.find('.select2').prev('select').select2('val', 0);
		$popup.find('button[type="submit"]').prop('disabled', true);
	}

	function fill_tracked_city_form($city, $form, action) {
		$form.prop('action', action);
		$form.find("input[name='action']").val(action);

		$form.find("input[name='city[id]']").val($city.attr('data-city-id'));
		$form.find("input[name='city[name]']").val($city.find('.city_name').html());
		$form.find("input[name='city[toponymic_analogue]']").val($city.find('.toponymic_analogue').html());
		$form.find("input[name='city[city_code]']").val($city.find('.city_code').html());
		$form.find("input[name='city[time_zone_gmt]']").val($city.find('.time_zone_gmt').html());

		$form.find("input[name='binded_airport[tracked_city_id]']").val($city.attr('data-city-id'));
		$form.find("input[name='binded_airport[distance_to_airport]']").val($city.find('.distance_to_airport').html());
		$form.find("input[name='binded_airport[is_tracked]']").prop('checked', +$city.attr('data-is-tracked-airport'));
		$("#binded_airports_list").select2('val', [$city.attr('data-airport-id')]);
	}

	function fill_transit_city_form($city, $form, action, form_title) {
		$form.find('.form-title').html(form_title);
		$form.prop('action', action);
		$form.find("input[name='action']").val(action);
		$form.find("input[name='city[id]']").val($city.attr('data-city-id'));
		$form.find("input[name='airport[location]']").val($city.attr('data-airport-location'));
		$form.find("input[name='city[name]']").val($city.find('.city_name').html());

		for (let i = 1; i <= 5; i++) {
			$form.find("input[name='alt_names[" + i + "]']").val($city.find('.alt-name-' + i).html());
		}

		$form.find("input[name='city[time_zone_gmt]']").val($city.find('.time_zone_gmt').html());
		$form.find("input[name='airport[airport_code]']").val($city.find('.airport_code').html());
	}

	$(".popup_close, .popup_over").click(function () {
		$(".adm_popup, .adm_popup_for_new_transit_city").fadeOut(300);
	});

	$(".popup_form input, #binded_airports_list").change(function () {
		$(".popup_form button").prop('disabled', false);
		$(this).css("color", "#2e5bff");
	});

	function get_attach_alt_city($elem) {
		let $select = $('#attach_alt_city');

		if ($select.length !== 0 && $select.length > 0) {
			return $select;
		}

		let rows = $('#transit-city-table tr').toArray();
		let options = '<option>Выберите аэропорт</option>';

		for (let i = 1; i < rows.length; i++) {
			const row = rows[i];
			const value = row.dataset.cityId;
			const text = row.children[0].innerHTML

			options += `<option value="${value}">${text}</option>`;
		}

		let select = document.createElement('select');
		select.innerHTML = options;
		select.id = 'attach_alt_city';
		select.style = "width: 200px;";

		if ($elem != null) {
			$elem.after(select);
		}

		$(select).select2({
			width: 'style',
		}).on('change', attach_alt_city);

		return $(select);
	}

	async function attach_alt_city() {
		let city_id = $('#attach_alt_city').val();
		let city_name = $('#attach_alt_city').find(':selected').text();
		let alt_name = $('#attach_alt_city').closest('tr').find('.city_name').html();

		let is_confirmed = confirm(`Прикрепить альт. название "${alt_name}" к городу "${city_name}" ?`);

		if (!is_confirmed) {
			return false;
		}

		let response = await fetch('/citys/attach-alt-name', {
			method: 'POST',
			body: initFormData({
				'city_id': city_id,
				'alt_name': alt_name,
			})
		});

		let result = null;

		try {
			result = await response.json();
		} finally {
			if (response.ok) {
				let $root = $(this).closest('tr');
				$(this).detach();
				let table = $('#new-city-table').DataTable();

				table.row($root).remove().draw();
				document.body.dispatchEvent(new CustomEvent('attach-alt-name', { detail: result }));
			} else {
				if (result === null) {
					result = {};
				}

				display_errors_in_alert(result['errors']);
			}
		}
	}

	$('.attach_alt_city_button').click(function () {
		$('#attach_alt_city').prev().show();
		$('#attach_alt_city').detach();

		get_attach_alt_city($(this));

		$(this).hide();
	});

	$('body').on('edit-tracked-city', function (e) {
		let city = e.detail.city;
		let airport = e.detail.airport;
		let binded_airport = e.detail.binded_airport;
		let $curr_city = $(`#tracked-city-table [data-city-id="${city.id}"]`);

		/**
		 * Изменение свойств самого города
		 */
		$curr_city.attr('data-is-tracked-airport', +binded_airport.is_tracked);
		$curr_city.attr('data-airport-location', binded_airport['location-city'].id);
		$curr_city.attr('data-airport-id', binded_airport.binded_airport_id);

		if (binded_airport.is_tracked) {
			$curr_city.removeClass('binded-airport-not-tracked');
		} else {
			$curr_city.addClass('binded-airport-not-tracked');
		}

		$curr_city.find('.distance_to_airport').html(binded_airport.distance_to_airport);
		$curr_city.find('.tracked_airport_name').html(binded_airport['location-city'].name);
		$curr_city.find('.tracked_airport_code').html(binded_airport['airport-code']);
		$curr_city.find('.city_name').html(city.name);
		$curr_city.find('.toponymic_analogue').html(city.toponymic_analogue);
		$curr_city.find('.city_code').html(city.city_code);
		$curr_city.find('.time_zone_gmt').html(city.time_zone_gmt);

		/**
		 * Изменение свойств городов, которые отслеживаются по аэропорту, находящемуся в этом городе
		 */
		let $citys_with_curr_location = $(`#tracked-city-table [data-airport-location="${city.id}"]`);
		$citys_with_curr_location.find('.tracked_airport_name').html(city.name);
		$citys_with_curr_location.find('.time_zone_gmt').html(city.time_zone_gmt);

		if (Number(city.id) === Number(airport.location)) {
			$(`#transit-city-table tr[data-airport-id="${airport.id}"] .city_name `).html(city.name);
			update_selects(city, airport);
		}

		reloadDataTables();
	});

	$('body').on('edit-transit-city', function (e) {
		let city = e.detail.city;
		let airport = e.detail.airport;
		let alt_names = e.detail.alt_names;
		let $curr_airport = $(`#transit-city-table tr[data-airport-id="${airport.id}"]`);

		$curr_airport.find('.city_name').html(city.name);
		$curr_airport.find('.time_zone_gmt').html(city.time_zone_gmt);
		$curr_airport.find('.airport_code').html(airport.airport_code);

		$curr_airport.find('.alt_name').html('');

		for (let i = 0; i < alt_names.length; i++) {
			const alt_name = alt_names[i]['alt_name'];

			$curr_airport.find(`.alt_name.alt-name-${i + 1}`).html(alt_name);
		}

		let $citys_with_curr_location = $(`#tracked-city-table [data-airport-location="${airport.location}"]`);
		$citys_with_curr_location.find('.tracked_airport_name').html(city.name);
		$citys_with_curr_location.find('.tracked_airport_code').html(airport.airport_code);

		let $curr_tracked_city = $(`#tracked-city-table [data-city-id="${airport.location}"]`);
		$curr_tracked_city.find('.city_name').html(city.name);
		$curr_tracked_city.find('.time_zone_gmt').html(city.time_zone_gmt);

		update_selects(city, airport);
		reloadDataTables();
	});

	$('body').on('add-new-transit-city', function (e) {
		let new_table = $('#new-city-table').DataTable();
		let city = e.detail.city;
		let airport = e.detail.airport;
		let alt_names = e.detail.alt_names;
		let columns = new Array(9).fill('');

		columns[0] = city.name;

		new_table.row($(`#new-city-table tbody td.city_name:contains("${city.name}")`).parent('tr'))
			.remove()
			.draw();

		for (let i = 0; i < alt_names.length; i++) {
			const alt_name = alt_names[i]['alt_name'];
			columns[i + 1] = alt_name;
			new_table.row($(`#new-city-table tbody td.city_name:contains("${alt_name}")`).parent('tr'))
				.remove()
				.draw();
		}

		columns[6] = city.time_zone_gmt;
		columns[7] = airport.airport_code;
		columns[8] = `
			<a class="edit_transit_city" data-action="edit-transit-city">
				<img src="images/karandash.png">
			</a>
			<a class="remove_transit_city" data-action="remove-transit-city">
				<img src="images/remove-icon.png">
			</a>`;

		$('#transit-city-table').DataTable().row.add(columns).draw(false);
		let $added_row = $('#transit-city-table tbody tr:not([data-airport-id])');
		$added_row.find('.edit_transit_city').click(edit_transit_city);
		$added_row.find('.remove_transit_city').click(remove_transit_city);
		$added_row.attr('data-city-id', city.id);
		$added_row.attr('data-airport-id', airport.id);
		$added_row.attr('data-airport-location', airport.location);

		$added_row.find('td:nth-child(1)').addClass('city_name');
		$added_row.find('td:nth-child(2)').addClass('alt_name alt-name-1');
		$added_row.find('td:nth-child(3)').addClass('alt_name alt-name-2');
		$added_row.find('td:nth-child(4)').addClass('alt_name alt-name-3');
		$added_row.find('td:nth-child(5)').addClass('alt_name alt-name-4');
		$added_row.find('td:nth-child(6)').addClass('alt_name alt-name-5');
		$added_row.find('td:nth-child(7)').addClass('time_zone_gmt');
		$added_row.find('td:nth-child(8)').addClass('airport_code');

		update_selects(city, airport);
		reloadDataTables();
	});

	$('body').on('attach-alt-name', function (e) {
		let alt_name_data = e.detail.alt_name;

		let $curr_city = $(`#transit-city-table tr[data-city-id="${alt_name_data.city}"]`);

		for (let i = 1; i < 6; i++) {
			if ('' === $curr_city.find(`.alt-name-${i}`).text().trim()) {
				$curr_city.find(`.alt-name-${i}`).html(alt_name_data.alt_name);
				break;
			}
		}
	});

	$('body').on('remove-transit-city', function (e) {
		let city = e.detail.city;
		let airport = e.detail.airport;

		$('#transit-city-table').DataTable()
			.row($(`#transit-city-table tr[data-airport-id="${airport.id}"]`))
			.remove()
			.draw();

		get_attach_alt_city().find(`option[value="${city.id}"]`).remove();
		$('#binded_airports_list').find(`option[value="${airport.id}"][data-city-id="${city.id}"]`).remove();

		reload_selects();
		reloadDataTables();
	});

	function initFormData(params) {
		let csrf_name = $('meta[name="csrf-param"]').prop('content');
		let csrf_value = $('meta[name="csrf-token"]').prop('content');

		let formData = new FormData();
		formData.append(csrf_name, csrf_value);

		for (const param in params) {
			if (params.hasOwnProperty(param)) {
				const value = params[param];
				formData.append(param, value);
			}
		}

		return formData;
	}

	function reloadDataTables() {
		$('#tracked-city-table, #transit-city-table, #new-city-table').DataTable().destroy();
		$('#tracked-city-table, #transit-city-table, #new-city-table').DataTable({
			"paging": false,
			"order": [[0, "asc"]],
			"language": {
				"search": "Поиск по вкладке:",
				"info": "Показано записей _TOTAL_",
				"infoEmpty": 'Не найдено ни одной записи',
				"emptyTable": 'Нет записей',
				"infoFiltered": '(отфильтровано из 200 общих записей)',
			},
		});
	}

	function update_selects(city, airport) {
		let $curr_city = get_attach_alt_city().find(`option[value="${city.id}"]`);

		if ($curr_city.length === 0) {
			get_attach_alt_city().append(new Option(city.name, city.id, false, false));
		} else {
			$curr_city.html(city.name);
		}

		$curr_city = null;
		$curr_city = $('#binded_airports_list').find(`option[value="${airport.id}"][data-city-id="${city.id}"]`);

		if ($curr_city.length === 0) {
			$new_option = $(new Option(city.name, airport.id, false, false)).attr('data-city-id', city.id);
			$('#binded_airports_list').append($new_option);
		} else {
			$curr_city.html(city.name);
		}

		reload_selects();
	}

	function reload_selects() {
		sort_select(get_attach_alt_city());
		sort_select($('#binded_airports_list'));

		get_attach_alt_city().select2('destroy').select2({
			width: 'style',
		});

		$('#binded_airports_list').select2('destroy').select2({
			width: 'style',
		});
	}

	function sort_select($select) {
		var listitems = $select.children('option').get();
		listitems.sort(function (a, b) {
			var compA = $(a).text().toUpperCase();
			var compB = $(b).text().toUpperCase();
			return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
		})
		$.each(listitems, function (idx, itm) { $select.append(itm); });
	}

});
