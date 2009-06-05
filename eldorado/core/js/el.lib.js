el = {
	lang  : typeof el_lang != 'undefined' ? el_lang : 'en',
	conf  : { langDomain : 'common' },
	utils : {
		langDomain : function(domain) {
			if (domain!='' && el.langs[el.lang][domain] != 'undefined') {
				el.conf.langDomain = domain;
			} 
			return el.conf.langDomain;
		},
		
		translate : function(msg, domain) { 
			domain = domain!='' && typeof el.langs[el.lang][domain] != 'undefined' ? domain : el.conf.langDomain;
			return typeof el.langs[el.lang][domain] != 'undefined' && el.langs[el.lang][domain][msg] ? el.langs[el.lang][domain][msg] : msg; 
			}
	},
	
	langs : {
		en : {},
		ru : {
			common : {
				'Yes'  : 'Да',
				'No'   : 'Нет',
				'Send' : 'Отправить'
				},
			forum : {
				'Please, enter URL'           : 'Пожалуйста, укажите URL ссылки',
				'Please, enter image URL'     : 'Пожалуйста, укажите URL изображения',
				"Please, enter spoiler title" : "Пожалуйста, укажите заголовок спойлера",
				'Attach file'                 : 'Добавить файл к сообщению',
				'Delete attachment'           : 'Удаление файла',
				'Do You realy want to delete file?' : 'Хотите удалить файл?',
				'Data transfer error'         : 'Ошибка передачи данных',
				'Please, select file to attach to post' : 'Пожалуйста, выберите файл, чтобы добавить его к сообщению'
				 
			}
		}
	}
	
}