<?php
/**
 * @author xiaoxia xu <x_824@sina.com> 2010-12-3
 * @link http://www.phpwind.com
 * @copyright Copyright &copy; 2003-2110 phpwind.com
 * @license 
 */
L::import('WIND:component.form.base.WindActionForm');
class UserForm extends WindActionForm {
	protected $_isValidate = true;//是否执行该form中的验证方法
	protected $username;
	protected $password;
	public function __construct() {
		parent::__construct();
		$this->setErrorAction('controllers.ErrorControllers');
	}
	/**
	 * 验证函数
	 */
	public function UserNameValidate() {
		if ($this->username == '') {
			$this->addError('输入的用户名为空', 'username');
		}
	}
	
	public function PasswordValidate() {
		if (strlen($this->password) < 6 ) {
			$this->password = '123456';
			$this->addError('输入的用户密码小于6位，重设为123456！', 'password');
		}
		return true;
	}
}