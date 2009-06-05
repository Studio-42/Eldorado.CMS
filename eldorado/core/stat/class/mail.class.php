<?
 /*****************************************************************
 * Класс отправки e-mail WEB_Count
 *
 * Copyright (c) 2000-2006 PHPScript.ru
 * Автор: Дмитрий Дементьев
 * info@phpscript.ru
 *
 ****************************************************************/
 class mail {

	 var $sendto;
	 var $from = "WEB_Count";
	 var $subject;
	 var $aattach = array();
	 var $xheaders = array();
	 var $priorities = array('1 (Highest)', '2 (High)', '3 (Normal)', '4 (Low)', '5 (Lowest)');
	 var $charset = "windows-1251";
	 var $ctencoding = "7bit";
	 var $receipt = 0;
	 var $content_type = 'text/html';

 function mail() { $this->boundary="--" . md5(uniqid("myboundary")); }

 function content_type($contenttype) { $this->content_type=$contenttype; }

 function replyto($address) {

	 if (!is_string($address)) return false;

	 $this->xheaders["Reply-To"]=$address;
 }

 function receipt() { $this->receipt=1; }

 function body($body, $charset = "") {

 	 $this->xheaders['From']=$this->from;
 	 $this->sendto=$this->sendto;
 	 $this->xheaders['Subject']=strtr($this->subject, "\r\n", "  ");

	 $this->body=$body;

	 if ($charset != "") {
		 $this->charset=strtolower($charset);
		 if ($this->charset != "us-ascii") $this->ctencoding="8bit";
	 }
 }

 function organization($org) {

	 if (trim($org != "")) $this->xheaders['Organization']=$org;
 }

 function priority($priority) {

	 if (!intval($priority)) return false;
	 if (!isset($this->priorities[$priority - 1])) return false;
	 $this->xheaders["X-Priority"]=$this->priorities[$priority - 1];

	 return true;
 }

 function attach($filename, $filetype = "", $disposition = "inline") {

	 if ($filetype == "") $filetype="application/x-unknown-content-type";
	 $this->aattach[]=$filename;
	 $this->actype[]=$filetype;
	 $this->adispo[]=$disposition;
 }

 function buildmail() {

	 $this->headers="";

	 if ($this->receipt) {
		 if (isset($this->xheaders["Reply-To"])) $this->xheaders["Disposition-Notification-To"]=$this->xheaders["Reply-To"];else  $this->xheaders["Disposition-Notification-To"]=$this->xheaders['From'];
	 }

	 if ($this->charset != "") {
		 $content_type=$this->content_type;

		 $this->xheaders["Mime-Version"]="1.0";
		 $this->xheaders["Content-Type"]="$content_type; charset=$this->charset";
		 $this->xheaders["Content-Transfer-Encoding"]=$this->ctencoding;
	 }

	 $this->xheaders["X-Mailer"]="PHP Mailer";

	 if (count($this->aattach) > 0) {
		 $this->_build_attachement();
	 } else {
		 $this->fullbody=$this->body;
	 }

	 reset ($this->xheaders);

	 while (list($hdr, $value)=each($this->xheaders)) {
		 if ($hdr != "Subject") $this->headers.="$hdr: $value\n";
	 }
 }

 function send() {

	 $this->buildmail();
	 $this->strto=$this->sendto;
	 $res=mail($this->strto, $this->xheaders['Subject'], $this->fullbody, $this->headers);
 }

 function get() {

	 $this->buildmail();
	 $mail="To: " . $this->strto . "\n";
	 $mail.=$this->headers . "\n";
	 $mail.=$this->fullbody;
	 return $mail;
 }

 function _build_attachement() {

	 $this->xheaders["Content-Type"]="multipart/mixed;\n boundary=\"$this->boundary\"";
	 $this->fullbody="This is a multi-part message in MIME format.\n--$this->boundary\n";
	 $this->fullbody.="Content-Type: " . $this->content_type . "; charset=$this->charset\nContent-Transfer-Encoding: $this->ctencoding\n\n" . $this->body . "\n";

	 $sep=chr(13). chr(10);
	 $ata=array();
	 $k=0;

	 for ($i=0; $i < count($this->aattach); $i++) {
		 $filename = $this->aattach[$i];
		 $basename=basename($filename);
		 $ctype=$this->actype[$i];
		 $disposition=$this->adispo[$i];

		 if (!file_exists($filename)) {
			 echo "<b>Ошибка</b>: прикрепляемый файл не найден.";
			 exit;
		 }

		 $subhdr="--$this->boundary\nContent-Type: $ctype;\n name=\"$basename\";\nContent-Transfer-Encoding: base64\nContent-Disposition: $disposition;\n  filename=\"$basename\"\n";
		 $ata[$k++]=$subhdr;
		 $linesz=filesize($filename) + 1;
		 $fp=fopen($filename, 'rb');
		 $ata[$k++]=chunk_split(base64_encode(fread($fp, $linesz)));

		 fclose ($fp);
	 }

	 $this->fullbody.=implode($sep, $ata);
 }
 }
?>