$(window).on('load', function () {
	$(".sbor_vbazu, .vigruzka_izbazi, .halt-tasks, .export_icaos").submit(async function (e) {
		e.preventDefault();
		e.stopPropagation();
		e.stopImmediatePropagation();

		let csrf_name = $('meta[name="csrf-param"]').prop('content');
		let csrf_value = $('meta[name="csrf-token"]').prop('content');
		let confirm_text = $(this).attr('data-confirm-text');
		let action = $(this).attr('action');

		is_confirmed = confirm(confirm_text);

		if (!is_confirmed) {
			return false;
		}

		let formData = new FormData($(this).toArray()[0]);
		console.dir($(this));
		console.dir($(this).toArray()[0]);
		formData.append(csrf_name, csrf_value);

		let response = await fetch('/processing-data/' + action, {
			method: 'POST',
			body: formData,
		});

		let result = null;

		try {
			result = await response.text();
		} finally {
			alert(result);

			setTimeout(function() {
				window.location = window.location.href;
			}, 2500); // Чтобы сервер успел перезагрузиться и завершить задачи
		}
	});

	$('input[type="checkbox"]').on('change', async function () {
		let csrf_name = $('meta[name="csrf-param"]').prop('content');
		let csrf_value = $('meta[name="csrf-token"]').prop('content');
		let setting_name = $(this).attr('name');
		let is_checked = $(this).prop('checked');
		let action = 'change-setting';

		let formData = new FormData();
		formData.append(csrf_name, csrf_value);
		formData.append('setting_name', setting_name);
		formData.append('is_checked', is_checked);

		let response = await fetch('/processing-data/' + action, {
			method: 'POST',
			body: formData,
		});

		try {
			result = await response.text();
		} finally {
			if (!response.ok) {
				alert(result)
			}
		}
	});
});
