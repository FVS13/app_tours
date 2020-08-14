$(window).on('load', function (){
	var sort_npp=0;
	var sort_city_name=0;
	var sort_city_code=0;
	var sort_shirota_np=0;
	var sort_dolgota_np=0;
	var sort_ot_gmt=0;
	var sort_aeroport_name=0;
	var sort_aeroport_rasst=0;
	var sort_aeroport_kod=0;
	var sort_napr=0;
	var sort_kod_napr=0;
	var sort_vid_sooobshen=0;
	var sort_rasst=0;
	var sort_istochniki=0;
	var sort_posled_obnov=0;
	var sort_transport=0;
	var sort_kol_napr=0;
	var sort_sost=0;
	var sort_fact_filov_zasutki=0;
	var sort_plan_filov_zasutki=0;
	var sort_papka_sbora=0;
	var selected_goroda_otprav="";
	var selected_goroda_prib="";
	var checkbox_length=$(".adm_form_block input[type=checkbox]").length;
	var doc_height=$(document).height();
	var tabl_height=doc_height-320;
	$(".adm_tabl_div_content").css("max-height",tabl_height);
	for(var i=0;i<checkbox_length;i++){
		if($(".adm_form_block input[type=checkbox]").eq(i).prop("checked")){
			$(".adm_form_block input[type=checkbox]").eq(i).parent(".adm_form_block").children(".perekl_knopka").addClass('active');
			if($(this).parent(".adm_form_block").children('label').html()=='Выкл'){
				$(this).parent(".adm_form_block").children('label').html('Вкл');
			} else {
				$(this).parent(".adm_form_block").children('label').html('Да');
			}
		}
	}

	$(".perekl_knopka").click(function(){
		if($(this).hasClass('active')){
			$(this).removeClass('active');
			$(this).parent(".adm_form_block").children("input[type=checkbox]").prop("checked", false);
			if($(this).parent(".adm_form_block").children('label').html()=='Вкл'){
				$(this).parent(".adm_form_block").children('label').html('Выкл');
			} else {
				$(this).parent(".adm_form_block").children('label').html('Нет');
			}
		} else {
			$(this).addClass('active');
			$(this).parent(".adm_form_block").children("input[type=checkbox]").prop("checked", true);
			if($(this).parent(".adm_form_block").children('label').html()=='Выкл'){
				$(this).parent(".adm_form_block").children('label').html('Вкл');
			} else {
				$(this).parent(".adm_form_block").children('label').html('Да');
			}
		}
	});

	var inp_transport="";
	var inp_istochn="";
	var inp_kol_filov="";
	var inp_papka_sbora="";
	$(".redact_transp_a").click(function(){
		$(".adm_popup").fadeIn(300);
		inp_city_id=$(this).parent(".dve_knopki").parent("td").parent("tr").children("td:nth-child(1)").text();
		inp_transport=$(this).parent(".dve_knopki").parent("td").parent("tr").children("td:nth-child(2)").attr("data-transport");
		inp_istochn=$(this).parent(".dve_knopki").parent("td").parent("tr").children("td:nth-child(3)").attr("data-istochn");
		inp_kol_filov=$(this).parent(".dve_knopki").parent("td").parent("tr").children("td:nth-child(7)").text();
		inp_papka_sbora=$(this).parent(".dve_knopki").parent("td").parent("tr").children("td:nth-child(8)").text();
		$(".popup_form input[name='city_id']").val(inp_city_id);
		$(".popup_form select[name='redact_transport']").val(inp_transport);
		$(".popup_form select[name='vibrat_istochnik']").val(inp_istochn);
		$(".popup_form input[name='filov_za_sutki']").val(inp_kol_filov);
		$(".popup_form input[name='papka_sbora']").val(inp_papka_sbora);
		return false;
	});
	$(".redact_transp_a").click(function(){
		$(".adm_popup").fadeIn(300);
		$(".popup_container").fadeOut(0);
		$(".popup_container.popup_redact_istochnik").fadeIn(0);
		return false;
	});
	$(".adm_head_buttons .dobavit_istochnik").click(function(){
		$(".adm_popup").fadeIn(300);
		$(".popup_container").fadeOut(0);
		$(".popup_container.popup_dobav_istochnik").fadeIn(0);
		return false;
	});
	// $(".otkrit_log").click(function(){
	// 	var log_city_id=$(this).parent("td").parent("tr").children("td:nth-child(1)").text();
	// 	var data={action:'log', city_id:log_city_id};
	// 	$.ajax({
	// 		type: "POST",
	// 		url: "/obrabotka.php",
	// 		data: data,
	// 		contentType: "application/x-www-form-urlencoded; charset=ISO-8859-1",
	// 		dataType: 'html',
	// 		success: function(html){
	// 			$(".popup_log .data_log").html(html);
	// 			$(".adm_popup").fadeIn(300);
	// 			$(".popup_container").fadeOut(0);
	// 			$(".popup_container.popup_log").fadeIn(0);
	// 		},
	// 		error: function(xhr, status, error) {
	// 			alert(xhr.responseText + '|\n' + status + '|\n' +error);
	// 		}
	// 	})
	// 	return false;
	// });
	// $(".delete_transp_a").click(function(){
	// 	var log_city_id=$(this).parent("td").parent("tr").children("td:nth-child(1)").text();
	// 	var data={action:'delete_transport', city_id:log_city_id};
	// 	$.ajax({
	// 		type: "POST",
	// 		url: "/obrabotka.php",
	// 		data: data,
	// 		contentType: "application/x-www-form-urlencoded; charset=ISO-8859-1",
	// 		dataType: 'html',
	// 		success: function(html){
	// 			alert(html);
	// 		},
	// 		error: function(xhr, status, error) {
	// 			alert(xhr.responseText + '|\n' + status + '|\n' +error);
	// 		}
	// 	})
	// 	return false;
	// });
	$("body").on('change', '.select_gorod_otpravleniya', function(){
		$( ".select_gorod_otpravleniya option:selected" ).each(function() {
	     	 var th=$(this);
	     	 th.insertBefore(".select_gorod_otpravleniya option:nth-child(1)").css("color","#2e5bff");
	     	 if($(".select_gorod_otpravleniya option:nth-child(2)").text()=="Выберите город"){
	     	 	$(".select_gorod_otpravleniya option:nth-child(2)").fadeOut(0);
	     	 }
	     	 if(selected_goroda_otprav!=""){
	     	 	selected_goroda_otprav+=",";
	     	 }
	     	 selected_goroda_otprav+=th.text();
	    });
	})
	$("body").on('change', '.select_gorod_pribitiya', function(){
		$( ".select_gorod_pribitiya option:selected" ).each(function() {
	     	 var th=$(this);
	     	 th.insertBefore(".select_gorod_pribitiya option:nth-child(1)").css("color","#2e5bff");
	     	 if($(".select_gorod_pribitiya option:nth-child(2)").text()=="Выберите город"){
	     	 	$(".select_gorod_pribitiya option:nth-child(2)").fadeOut(0);
	     	 }
	     	 if(selected_goroda_prib!=""){
	     	 	selected_goroda_prib+=",";
	     	 }
	     	 selected_goroda_prib+=th.text();
	    });
	})
	.trigger( "change" );
	// $("body").on('submit', '.vhod_dannie_filter_form', function(){
	// 	var th=$(this);
	// 	var data={action:'filters', page:page, sort_npp:sort_npp, sort_city_name:sort_city_name, sort_city_code:sort_city_code, sort_shirota_np:sort_shirota_np, sort_dolgota_np:sort_dolgota_np, sort_ot_gmt:sort_ot_gmt, sort_aeroport_name:sort_aeroport_name, sort_aeroport_rasst:sort_aeroport_rasst, sort_aeroport_kod:sort_aeroport_kod, sort_napr:sort_napr, sort_kod_napr:sort_kod_napr, sort_vid_sooobshen:sort_vid_sooobshen, sort_rasst:sort_rasst, sort_istochniki:sort_istochniki, sort_posled_obnov:sort_posled_obnov, sort_transport:sort_transport, sort_kol_napr:sort_kol_napr, sort_sost:sort_sost, sort_fact_filov_zasutki:sort_fact_filov_zasutki, sort_plan_filov_zasutki:sort_plan_filov_zasutki, sort_papka_sbora:sort_papka_sbora};
	// 	data.filters=th.serialize();
	// 	$.ajax({
	// 		type: "POST",
	// 		url: "/obrabotka.php",
	// 		data: data,
	// 		contentType: "application/x-www-form-urlencoded; charset=ISO-8859-15",
	// 		dataType: 'html',
	// 		success: function(html){
	// 			$(".napravl_table tbody").html(html);
	// 		},
	// 		error: function(xhr, status, error) {
	// 			alert(xhr.responseText + '|\n' + status + '|\n' +error);
	// 		}
	// 	})
	// 	return false;
	// });
	$("input[name='data_otprav_s'], input[name='data_otprav_po'], input[name='input_obnov_posle']").click(function(){
		$(this).parent(".input_data_otprav").children("table").fadeIn(100);
	});

	$(document).mouseup(function (e){ // событие клика по веб-документу
		var div = $(".input_data_otprav table"); // тут указываем ID элемента
		if (!div.is(e.target) // если клик был не по нашему блоку
		    && div.has(e.target).length === 0) { // и не по его дочерним элементам
			div.hide(); // скрываем его
		}
	});

	$("body").on('click', '.input_data_otprav table tbody td', function(){
		var inp_day=$(this).text();
		var inp_month=$(this).parent("tr").parent("tbody").parent("table").children("thead").children("tr:nth-child(1)").children("td:nth-child(2)").attr("data-month");
		var inp_year=$(this).parent("tr").parent("tbody").parent("table").children("thead").children("tr:nth-child(1)").children("td:nth-child(2)").attr("data-year");
		if(parseInt(inp_day)<10){ inp_day='0'+inp_day; }
		inp_month_num=parseInt(inp_month)+1;
		if(inp_month_num<10){ inp_month='0'+inp_month_num; } else { inp_month=inp_month_num; }
		if($(this).parent("tr").parent("tbody").parent("table").parent(".input_data_otprav").children("input").attr("name")!="input_obnov_posle"){
			$(this).parent("tr").parent("tbody").parent("table").parent(".input_data_otprav").children("input").val(inp_year+"-"+inp_month+"-"+inp_day);
		} else {
			var this_time=$(this).parent("tr").parent("tbody").parent("table").parent(".input_data_otprav").children("input").val().substring(11);
			$(this).parent("tr").parent("tbody").parent("table").parent(".input_data_otprav").children("input").val(inp_year+"-"+inp_month+"-"+inp_day+" "+this_time);
		}
		$(this).parent("tr").parent("tbody").parent("table").parent(".input_data_otprav").children("input").css("color","#2e5bff");
		$(this).parent("tr").parent("tbody").parent("table").fadeOut(100);
	});
	$(".input_data_otprav input").change(function(){
		$(this).css("color","#2e5bff");
	})
});
