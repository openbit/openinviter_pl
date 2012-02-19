<?php
$_pluginInfo=array(
	'name'=>'Wp.pl',
	'version'=>'1.0.5',
	'description'=>"Get the contacts from an Wp.pl account, Plugin developed by Piotr Loposzko",
	'base_version'=>'1.6.5',
	'type'=>'email',
	'check_url'=>'http://poczta.wp.pl/',
	'requirement'=>'user',
	'allowed_domains'=>false,
	'imported_details'=>array('first_name','last_name','email_1'),
	);
/**
 * Wp.pl Plugin
 * 
 * Imports user's contacts from Wp.pl account
 * 
 * @author Piotr Loposzko
 * @version 1.0.0
 */
class wpl extends openinviter_base
{
	private $login_ok=false;
	public $showContacts=true;
	public $internalError=false;
	protected $timeout=30;		
	
	public $debug_array=array('initial_get'=>'zaloguj');

	/**
	 * Login function
	 * 
	 * Makes all the necessary requests to authenticate
	 * the current user to the server.
	 * 
	 * @param string $user The current user.
	 * @param string $pass The password for the current user.
	 * @return bool TRUE if the current user was authenticated successfully, FALSE otherwise.
	 */
	public function login($user, $pass)
	{
		$this->resetDebugger();
		$this->service='wp';
		$this->service_user=$user;
		$this->service_password=$pass;
		if (!$this->init()) return false;
		
		$res = $this->get("http://poczta.wp.pl/",true);
	
		$form_action="http://profil.wp.pl/login_poczta.html";
		$post_elements=array('login_username'=>$user,
							 'login_password'=>$pass,
                'url'=>'http://poczta.wp.pl/index.html',
                'idu'=>'99',
                '_action'=>'login'
							); 
		$res=$this->post($form_action,$post_elements, true);
  
		$url_adress='http://ksiazka-adresowa.wp.pl/csv.html';
   
		$this->login_ok=$url_adress;
		return true;		
	} 

	/**
	 * Get the current user's contacts
	 * 
	 * Makes all the necesarry requests to import
	 * the current user's contacts
	 * 
	 * @return mixed The array if contacts if importing was successful, FALSE otherwise.
	 */	
	public function getMyContacts()
		{
		if (!$this->login_ok)
			{
			$this->debugRequest();
			$this->stopPlugin();
			return false;
			}
		else $url=$this->login_ok;
		$res=$this->post($url,array('gr_id'=>'0', 'program'=>'oeof'));
    $temp=$this->parseCSV($res);
		$contacts=array();
		foreach ($temp as $values)
			{
			if (!empty($values[5]))
				$contacts[$values[5]]=array('first_name'=>(!empty($values[0])?$values[0]:false),
												'last_name'=>(!empty($values[1])?$values[1]:false),
												'email_1'=>(!empty($values[5])?$values[5]:false),
											   );
			}		
		foreach ($contacts as $email=>$name) if (!$this->isEmail($email)) unset($contacts[$email]);
		return $this->returnContacts($contacts);
		}

	/**
	 * Terminate session
	 * 
	 * Terminates the current user's session,
	 * debugs the request and reset's the internal 
	 * debudder.
	 * 
	 * @return bool TRUE if the session was terminated successfully, FALSE otherwise.
	 */	
	public function logout()
		{
		if (!$this->checkSession()) return false;
		$res=$this->get('http://m.poczta.wp.pl/');
		$url_logout='http://m.poczta.wp.pl/index.html?logout=1&ticaid='.$this->getElementString($res,'index.html?logout=1&ticaid=','"');
		$res=$this->get($url_logout);
		$this->debugRequest();
		$this->resetDebugger();
		$this->stopPlugin();
		}
	}
?>