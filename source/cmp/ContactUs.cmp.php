<?php
BASIC::init()->imported('ModuleSettings.cmp', 'cms/controlers/back');
/**
 * @author Evgeni Baldzhiyski
 * @version 1.2
 * @since 10.01.2012
 */
class ContactUs extends CmsComponent implements ModuleSettingsInterface{

	public $base = 'contacts';
	
	public $contact_emails = array();
	public $copy_to_base = false;
	public $allow_html = false;
	
	public $use_attach_file = false;
	public $max_attach_file = '1M';
	public $upload_folder = 'upload/contacts/';
	public $attach_file_types = 'jpeg,jpg,gif,swf,png';
	/**
	 * If this value is '' will put directly user body in mail else will set in variable CONTENT
	 * 
	 * @var string
	 */
	public $template_email = '';
	
	public $capcha_settings = array(
		'ttf,alger.ttf',
		'width,110',
		'height,30',
		'lenght,6',
		'mode,2',
		'mime,png',
		'text_size,17',
		'bg_color,#F1F1F1',
		'text_color,#6F6F6F',
		'line_color,#D7D7D7',
		'noise_color,#D7D7D7',
		'num_lines,5',
		'noise_level,3',
	); 
	
	function main(){
		parent::main();
		
		$this->setField('name', array(
			'text' => BASIC_LANGUAGE::init()->get('contact_user_name'),
			'perm' => '*'
		));
		$this->setField('email', array(
			'text' => BASIC_LANGUAGE::init()->get('contact_user_email'),
			'perm' => '*',
			'messages' => array(
				2 => BASIC_LANGUAGE::init()->get('invalid_email')
			)
		));
		$this->setField('subject', array(
			'text' => BASIC_LANGUAGE::init()->get('contact_user_subject'),
			'perm' => '*'
		));

		$this->setField('attach_file', array(
			'text' 		=> BASIC_LANGUAGE::init()->get('contact_user_attach_file'),
			'formtype' 	=> 'file',
			'messages' 	=> array(
				1  => BASIC_LANGUAGE::init()->get('upoad_error_1'),
				2  => BASIC_LANGUAGE::init()->get('upoad_error_2'),
				3  => BASIC_LANGUAGE::init()->get('upoad_error_3'),
				4  => BASIC_LANGUAGE::init()->get('upoad_error_4'),
				10 => BASIC_LANGUAGE::init()->get('upoad_error_10'),
				11 => BASIC_LANGUAGE::init()->get('upoad_error_11'),
				12 => BASIC_LANGUAGE::init()->get('upoad_error_12'),
				13 => BASIC_LANGUAGE::init()->get('upoad_error_13'),
				14 => BASIC_LANGUAGE::init()->get('upoad_error_14'),
				15 => BASIC_LANGUAGE::init()->get('upoad_error_15'),
				16 => BASIC_LANGUAGE::init()->get('upoad_error_16'),
			),
			'attributes' => array(
				'max' 	 		=> $this->max_attach_file,
				'rand'   		=> 'true',
				'as' 	 		=> 'CNT',
				'dir' 	 		=> $this->upload_folder,
				'preview' 		=> '200,200',    
				'perm' 	 		=> $this->attach_file_types,
				'delete_btn' 	=> array(
					'text' => BASIC_LANGUAGE::init()->get('delete')
				)	
			)
		));
		
		$this->setField('body', array(
			'text' => BASIC_LANGUAGE::init()->get('contact_user_body'),
			'formtype' => 'textarea',
			'dbtype' => 'longtext',
			'attributes' => array(
				'allow_html' => $this->allow_html,
				'navigation' => false,
				'buttons' => 'btnXHTMLSource:false;btnTable:false;btnAbsolute:false;btnStyleFormatting:false;btnBookmark:false'
			)
		));
		$this->setField('sec_code', array(
			'text' => BASIC_LANGUAGE::init()->get('contact_user_capcha'),
			'formtype' => 'capcha',
			'dbtype' => 'none',
			'perm' => '*',
			'attributes' => array(
		
			),
			'messages' => array(
				2 => BASIC_LANGUAGE::init()->get('invalid_sec_code')
			)
		));
		
		$this->updateAction('save',null,BASIC_LANGUAGE::init()->get('contact_send'));
		
		$this->specialTest = 'validator';
		$this->errorAction = 'add';
		
		if(!$this->contact_emails){
			$this->contact_emails[] = CMS_SETTINGS::init()->get('SITE_EMAIL');
		}
	}
	function validator(){
		$err = false;
		if(!BASIC::init()->validEmail($this->getDataBuffer('email'))){
			$err = $this->setMessage("email", 2);
		}
		if(!$this->allow_html){
			$this->setDataBuffer('body', strip_tags($this->getDataBuffer('body')));
		}
		if(strtolower($this->getDataBuffer('sec_code')) != strtolower(BASIC_GENERATOR::init()->getControl('capcha')->code('sec_code'))){
			$this->setMessage('sec_code', 2);
		}		
		return $err;
	}
	
	protected function backStartPanelSettings(){
		$this->delAction('add');
		$this->delAction('save');
		
		$this->updateAction('edit', null, BASIC_LANGUAGE::init()->get('preview'));
			
		$this->template_form = 'cmp-form.tpl';
			
		$this->unsetField('sec_code');
	}
	protected function frontStartPanelSettings(){
		$this->updateAction('list', 'ActionFormAdd');
		
		$this->delAction('edit');
		$this->delAction('delete');
		
		if(!$this->use_attach_file){
			$this->unsetField('attach_file');
		}else{
			$this->updateField('attach_file', array(
				'attributes' => array(
					'max' 	 => $this->max_attach_file,
					'dir' 	 => $this->upload_folder,
					'perm' 	 => $this->attach_file_types,
				)
			));
		}
		if($this->allow_html){
			$this->updateField('body', array(
				'attributes' => array(
					'allow_html' => true
				)
			));
		}
		$csettings = array(); foreach($this->capcha_settings as $v){
			$ex = explode(",", $v);
			
			if($ex[1]) $csettings[$ex[0]] = $ex[1];
		}
		$this->updateField("sec_code", array(
			'attributes' => $csettings
		));		
	}
	function startPanel(){
		if($this->pdata){
			$this->frontStartPanelSettings();
		}else{
			$this->backStartPanelSettings();
		}
		return parent::startPanel();
	}
	function ActionFormAdd(){
		$this->delAction('cancel');
		
		if(BASIC_URL::init()->cookie('contact_sended')){
			BASIC_URL::init()->un('contact_sended');
			
			BASIC_ERROR::init()->setMessage(BASIC_LANGUAGE::init()->get('contact_send_success'));
		}
		return parent::ActionFormAdd();
	}
	function ActionFormEdit($id){
		if(!$this->pdata){
			$this->disabled();
		}
		return parent::ActionFormEdit($id);
	}
	function ActionList(){
		$this->sorting = new BasicSorting('name', $this->prefix);
		
		$this->map('name', BASIC_LANGUAGE::init()->get('contact_user_subject'), 'formatter');
		$this->map('email', BASIC_LANGUAGE::init()->get('contact_user_email'), 'formatter');
		$this->map('subject', BASIC_LANGUAGE::init()->get('contact_user_subject'), 'formatter');
		$this->map('attach_file', BASIC_LANGUAGE::init()->get('contact_user_attach_file'), 'formatter', null, false);
		
		return parent::ActionList();
	}
	function formatter($val, $name, $row){
		if($name == 'email'){
			return '<a href="mailto:&lt;'.$row['name'].'&gt; '.$val.'">'.$val.'</a>';
		}
		if($name == 'attach_file'){
			return BASIC_GENERATOR::init()->image($val, array(
				'width' => 100,
				'height' => 100
			));
		}
		return $val;
	}
	protected function disabled(){
		foreach ($this->fields as $k => $v){
			$this->updateField($k, array(
				'attributes' => array(
					'readonly' => true
				)
			));
		}
	}
	function ActionSave($id){	
		BASIC::init()->imported('spam.mod');
		
		$cleaner = $this->cleanerDecision($this->fields['subject'][3], false, $this->fields['subject'][7]);
		$mail = new BasicMail($this->getDataBuffer('email'), $this->getDataBuffer('name'), array(
			'subject' => $cleaner($this->getDataBuffer('subject'))
		));
		
		$cleaner = $this->cleanerDecision($this->fields['body'][3], false, $this->fields['body'][7]);
		$mail->body(!$this->template_email ? 
			$cleaner($this->getDataBuffer('body')) :
			BASIC_TEMPLATE2::init()->set('CONTENT', $cleaner($this->getDataBuffer('body')), $this->template_email)->parse($this->template_email)
		);
		if($this->use_attach_file && $this->getDataBuffer('attach_file')->tmpName){
			$mail->attach($this->getDataBuffer('attach_file')->tmpName, $this->getDataBuffer('attach_file')->fullName, null, $this->getDataBuffer('attach_file')->type);
		}
		
		if(!$mail->send($this->contact_emails)){
			BASIC_ERROR::init()->setError(BASIC_LANGUAGE::init()->get('contact_send_fail'));
			return false;
		}else{
			BASIC_URL::init()->set('contact_sended', 1, 'cookie');
		}
		
		if($this->copy_to_base){
			$id = parent::ActionSave();
		}
		return $id;
	}
	
	function settingsData(){
		// create db-table
		$this->getRecord(0);
		
		return array(
			'template_form' => $this->template_form,
			'template_email' => $this->template_email,
			'allow_html' 	=> $this->allow_html,
			'contact_emails'=> $this->contact_emails,
			'copy_to_base' 	=> $this->copy_to_base ? 1 : 0,
			'base' 			=> $this->base,
			'prefix' 		=> $this->prefix,
		
			'use_attach_file' 	=> $this->use_attach_file ? 1 : 0,
			'max_attach_file' 	=> $this->max_attach_file,
			'upload_folder' 	=> $this->upload_folder,
			'attach_file_types' => $this->attach_file_types,
			'capcha_settings' 	=> $this->capcha_settings
		);
	}
	function settingsUI(){
		
		return array(
			'copy_to_base' => array(
				'text' => BASIC_LANGUAGE::init()->get('copy_to_base'),
				'formtype' => 'radio',
				'attributes' => array(
					'data' => array(
						BASIC_LANGUAGE::init()->get('no'),
						BASIC_LANGUAGE::init()->get('yes')
					)
				)
			),
			'base' => array(
				'text' => BASIC_LANGUAGE::init()->get('table_name')
			),			
			'template_form' => array(
				'text' => BASIC_LANGUAGE::init()->get('template_form')
			),
			'template_email' => array(
				'text' => BASIC_LANGUAGE::init()->get('template_email')
			),
			'allow_html' => array(
				'text' => BASIC_LANGUAGE::init()->get('allow_html_email'),
				'formtype' => 'radio',
				'attributes' => array(
					'data' => array(
						BASIC_LANGUAGE::init()->get('no'),
						BASIC_LANGUAGE::init()->get('yes')
					)
				)
			),
			'prefix' => array(
				'text' => BASIC_LANGUAGE::init()->get('prefix')
			),
			'contact_emails' => array(
				'text' => BASIC_LANGUAGE::init()->get('contact_emails'),
				'formtype' => 'selectmanage',
				'perm' => '*',
				'attributes' => array(
					'data' => array(''),
					'buttons' => 'del:'.BASIC_LANGUAGE::init()->get('delete')
				)
			),
			'capcha_settings' => array(
				'text' => BASIC_LANGUAGE::init()->get('capcha_settings'),
				'formtype' => 'selectmanage',
				'attributes' => array(
					'data' => array(
						BASIC_LANGUAGE::init()->get('variable'),
						BASIC_LANGUAGE::init()->get('value')
					),
				)
			),
			'use_attach_file' => array(
				'text' => BASIC_LANGUAGE::init()->get('use_attach_file'),
				'formtype' => 'radio',
				'attributes' => array(
					'data' => array(
						BASIC_LANGUAGE::init()->get('no'),
						BASIC_LANGUAGE::init()->get('yes')
					)
				)
			),
			'max_attach_file' => array(
				'text' => BASIC_LANGUAGE::init()->get('max_attach_file')
			),
			'upload_folder' => array(
				'text' => BASIC_LANGUAGE::init()->get('upload_folder')
			),
			'attach_file_types' => array(
				'text' => BASIC_LANGUAGE::init()->get('attach_file_types')
			)
		);
	}
	function settingsFormat($data){
		return $data;
	}
}