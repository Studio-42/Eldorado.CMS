FCKConfig.SkinPath = FCKConfig.BasePath + 'skins/silver/' ;
FCKConfig.ToolbarSets["el"] = [
	['Source','-','Save','NewPage','Preview','-','Templates'],
	['Cut','Copy','Paste','PasteText','PasteWord','-','Print','SpellCheck'],
	['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
	['Bold','Italic','Underline','StrikeThrough','-','Subscript','Superscript'],
	['OrderedList','UnorderedList','-','Outdent','Indent','Blockquote','CreateDiv'],

	['JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],
	['Link','Unlink','Anchor'],
	['Image','Flash','Table','Rule','Smiley','SpecialChar','PageBreak'],
	['Style','FontFormat','FontName','FontSize'],
	['TextColor','BGColor'],
	['FitWindow','ShowBlocks','-','About']		// No comma for the last row.
] ;


FCKConfig.SpellChecker			= 'SpellerPages' ;	// 'ieSpell' | 'SpellerPages'
FCKConfig.IeSpellDownloadUrl	= 'http://www.iespell.com/download.php' ;
//FCKConfig.SpellerPagesServerScript = FCKConfig.BasePath +'dialog/fck_spellerpages/spellerpages/server-scripts/spellchecker.php' ;	// Available extension: .php .cfm .pl
FCKConfig.FirefoxSpellChecker	= true ;

FCKConfig.EnterMode = 'br' ;			// p | div | br
FCKConfig.ShiftEnterMode = 'p' ;