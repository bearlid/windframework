<?php
/**
 * 操作控制器,管理一组用于处理用户的请求的处理操作.
 * 
 * 该类继承自'WindSimpleController',用于管理处理用户请求的操作,该类区别于'WindSimpleController'通过覆盖
 * 'resolvedActionMethod'方法,实现多处理管理.
 * @author Qiong Wu <papa0924@gmail.com>
 * @copyright ©2003-2103 phpwind.com
 * @license http://www.windframework.com
 * @version $Id$
 * @package web
 */
abstract class WindController extends WindSimpleController {

	/* (non-PHPdoc)
	 * @see WindSimpleController::run()
	 */
	public function run() {}

	/* (non-PHPdoc)
	 * @see WindAction::resolvedActionMethod()
	 */
	protected function resolvedActionMethod($handlerAdapter) {
		$action = $handlerAdapter->getAction();
		if ($action !== 'run') $action = $this->resolvedActionName($action);
		if (in_array($action, array('doAction', 'beforeAction', 'afterAction', 'forwardAction'))) throw new WindException(
			'[web.WindController.resolvedActionMethod]', WindException::ERROR_CLASS_METHOD_NOT_EXIST);
		$method = new ReflectionMethod($this, $action);
		if ($method->isAbstract() || !$method->isPublic()) throw new WindException(
			'[web.WindController.resolvedActionMethod]', WindException::ERROR_CLASS_METHOD_NOT_EXIST);
		return $action;
	}

	/**
	 * 解析action操作方法名称
	 * 
	 * 默认解析规则,在请求的action名称后加'Action'后缀<code>
	 * 请求的action为 'add',则对应的处理方法名为 'addAction',可以通过覆盖本方法,修改解析规则</code>
	 * @param string $action
	 * @return void
	 */
	protected function resolvedActionName($action) {
		return $action . 'Action';
	}
}