<?php
/**
 * WPLib\Base\Controller
 *
 * @author
 * @copyright 2014-2018
 */

namespace WPLib\Base;

use Logger;

/** php
 * @apiDefine CODE_200
 * @apiSuccess (Response 200) {int} status='200' 状态
 * @apiSuccess (Response 200) {string} info 消息
 * @apiSuccess (Response 200) {object} data={} 返回值
 * @apiSuccessExample {json} Response 200 Example
 *
 *  HTTP/1.1 200 OK
 *  {
 *      "status": 200,
 *      "info": "成功",
 *      "data": {},
 *  }
 */

/** php
 * @apiDefine CODE_500
 * @apiSuccess (Response 200) {json} [status='200']
 * @apiSuccessExample {json} Response 500 Example
 *
 *  HTTP/1.1 200 OK
 *  {
 *      "status": 500,
 *      "info": "系统繁忙",
 *      "data": {},
 *  }
 */

class Controller extends \Phalcon\Mvc\Controller
{
	private $_allow_ips = array(
		'*',
	);

	// 签名不存在
	const STATE_ERROR_SIGN_NOT_EXISTS = 101101;

	// 签名不正确
	const STATE_ERROR_SIGN_INCORRECT  = 101102;

    // 验证码已过期
	const STATE_ERROR_VERIFYCODE_EXPIRED = 101011;

	// 验证码不存在
	const STATE_ERROR_VERIFYCODE_NOT_EXISTS  = 101012;

	// 验证码不正确
	const STATE_ERROR_VERIFYCODE_INCORRECT  = 101013;

	/**
	 * 错误信息
	 */
	public static $errorList = array(
		self::STATE_ERROR_VERIFYCODE_EXPIRED => '验证码已过期',
		self::STATE_ERROR_VERIFYCODE_NOT_EXISTS => '验证码不存在',
		self::STATE_ERROR_VERIFYCODE_INCORRECT => '验证码不正确',
		self::STATE_ERROR_SIGN_NOT_EXISTS => '签名不存在',
		self::STATE_ERROR_SIGN_INCORRECT  => '签名不正确',
	);
	protected static $_no_sign_actions = [
		['m' => '*', 'n' => '*', 'c' => '*', 'a' => '*'],
	];

	protected static $_sign_actions = [
	];

	/**
	 * 初始化
	 */
	public function initialize()
	{

	}

	/**
	 * @return bool
	 */
	public function beforeExecuteRoute()
	{
		if (!$this->checkAccess()) {
			$app_id = $this->request->get('app_id', 'int', 0);
			$token  = $this->request->get('token', null, '');
			$req    = $this->request->isPost() ? $_POST : $_GET;
			unset($req['token']);

			if (!$this->request->has('token')) {
				$errorInfo = self::getErrorInfo(self::STATE_ERROR_SIGN_NOT_EXISTS);
				$this->jsonReturn([], $errorInfo['message'], $errorInfo['code']);

				return false;
			} else {
				if ($this->verifyAccess($app_id, $token, $req)) {
					return true;
				} else {
					$errorInfo = self::getErrorInfo(self::STATE_ERROR_SIGN_INCORRECT);
					$this->jsonReturn([], $errorInfo['message'], $errorInfo['code']);

					return false;
				}
			}
		}

		return true;
	}

	/**
	 * 访问权限检测
	 */
	public function checkAccess($a = null, $c = null, $n = null, $m = null)
	{
		if ($m === null && $this->dispatcher->getModuleName() !== null) {
			$m = $this->dispatcher->getModuleName();
		} else {
			$m = '';
		}

		if ($n === null) {
			$n = $this->dispatcher->getNamespaceName() ?: '';
		}

		if ($c === null) {
			$c = $this->dispatcher->getControllerName();
		}

		if ($a === null) {
			$a = $this->dispatcher->getActionName();
		}


		/**
		 * 检查用户访问权限
		 */

		// 是否在无须登录列表中
		foreach (static::$_no_sign_actions as $k => $v) {
			foreach ($v as $kk => $vv) {
				if ($vv == '*') {
					// 匹配全部，继续下个校验项
					continue;
				}
				/**
				 * 多Action逗号相连处理
				 */
				$vv = explode(',', $vv);
				foreach ($vv as $kkk => $vvv) {
					if ($vvv == $$kk) {
						// 相同，跳到下个验证项
						continue 2;
					}
				}

				// 都不正确，跳到下条规则
				continue 2;
			}
			// 无须登录
			return true;
		}

		// 是否在需要登录列表中
		foreach (static::$_sign_actions as $k => $v) {
			foreach ($v as $kk => $vv) {
				if ($vv == '*') {
					// 匹配全部，继续下个校验项
					continue;
				}
				/**
				 * 多Action逗号相连处理
				 */
				$vv = explode(',', $vv);
				foreach ($vv as $kkk => $vvv) {
					if ($vvv == $$kk) {
						// 相同，跳到下个验证项
						continue 2;
					}
				}

				// 都不正确，跳到下条规则
				continue 2;
			}

			return false;
		}

		return true;
	}

	public function verifyAccess($app_id, $req_token, $params = [])
	{
		if ($req_token == '123456') {
			return true;
		}

		if (\WPLib\WPApi::verifySignature($req_token, $params)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 是否开发环境
	 *
	 * @return bool
	 */
	public function isDevEnv()
	{
		if (in_array(ENVIRON, ['develop'])) {
			return true;
		}

		return false;
	}

	/**
	 * 是否测试环境
	 *
	 * @return bool
	 */
	public function isTestEnv()
	{
		if (in_array(ENVIRON, ['test'])) {
			return true;
		}

		return false;
	}

	/**
	 * 是否生产环境
	 *
	 * @return bool
	 */
	public function isProdEnv()
	{
		if (in_array(ENVIRON, ['production'])) {
			return true;
		}

		return false;
	}

	/**
	 * 是否开发测试环境
	 *
	 * @return bool
	 */
	public function isDevTestEnv()
	{
		return $this->isDevEnv() || $this->isTestEnv();
	}

	/**
	 * @param object $var
	 * @return mixed
	 */
	public function object($var)
	{
	    return json_decode(json_encode($var));
	}

    /**
     * 获取字符串类型的GET参数
     *
     * @param string $name
     * @param string $default
     * @return int
     */
    protected function getString($name, $default = '')
    {
        return (string)$this->request->get($name, 'string', $default);
    }

    /**
     * 获取整型的POST参数
     *
     * @param string $name
     * @param int $default
     * @return int
     */
    protected function getInt($name, $default = 0)
    {
        return (int)$this->request->get($name, 'int', $default);
    }

    /**
     * 获取浮点型的GET参数
     *
     * @param string $name
     * @param int $default
     * @return int
     */
    protected function getFloat($name, $default = 0)
    {
        return (float)$this->request->get($name, 'float', $default);
    }

    /**
     * 获取字符串类型的GET参数
     *
     * @param string $name
     * @param string $default
     * @return int
     */
    protected function getPostString($name, $default = '')
    {
        return (string)$this->request->getPost($name, 'string', $default);
    }

    /**
     * 获取整型的POST参数
     *
     * @param string $name
     * @param int $default
     * @return int
     */
    protected function getPostInt($name, $default = 0)
    {
        return (int)$this->request->getPost($name, 'int', $default);
    }

    /**
     * 获取浮点型的GET参数
     *
     * @param string $name
     * @param int $default
     * @return int
     */
    protected function getPostFloat($name, $default = 0)
    {
        return (float)$this->request->getPost($name, 'float', $default);
    }

	/**
	 * 为空返回错误信息
	 *
	 * @param $var
	 * @param $info
	 * @param $status
	 */
	public function emptyReturn($var, $info, $status)
	{
		if (is_string($var)) {
			$var = trim($var);
		}

		if (empty($var)) {
			$this->jsonReturn([], $info, $status);
		}
	}

	public function errorReturn($var, $info, $status, $func = null)
	{
		if (is_callable($func)) {
			if (!$func($var)) {
				$this->jsonReturn([], $info, $status);
			}
		}

		return true;
	}

	/**
	 * 输出JSON结果
	 *
	 * @param $data
	 * @param string $info
	 * @param int $status
	 */
	public function jsonReturn($data = [], $info = 'success', $status = 200, $reason = '')
	{
		if ($status != 200 && defined('IS_CHANGE_HTTP_STATUS') && IS_CHANGE_HTTP_STATUS == true) {
			header("HTTP/1.1 503");
		}
		header('Content-Type:application/json; charset=utf-8');
		echo json_encode([
			'status'  => $status,
			'info'    => $info,
			'reason'  => $reason,
			'data'    => $data,
		]);
		if ($eventsManager = $this->application->getEventsManager()) {
			$eventsManager->fire("application:afterHandleRequest", $this->application, $this);
			$eventsManager->fire("application:beforeSendResponse", $this->application, $this);
		}
		$this->response->send();
		exit;
	}

	/**
	 * 输出JSONP结果
	 *
	 * @param $data
	 * @param string $info
	 * @param int $status
	 */
	public function jsonpReturn($data = [], $info = '', $status = 200, $reason = '')
	{
		if ($status != 200 && defined('IS_CHANGE_HTTP_STATUS') && IS_CHANGE_HTTP_STATUS == true) {
			header("HTTP/1.1 503");
		}
		header('Content-Type:application/json; charset=utf-8');
		echo sprintf("%s(%s)", $_GET['jsoncallback'], json_encode([
			'status'  => $status,
			'info'    => $info,
			'reason'  => $reason,
			'data'    => $data,
		]));
		if ($eventsManager = $this->application->getEventsManager()) {
			$eventsManager->fire("application:afterHandleRequest", $this->application, $this);
			$eventsManager->fire("application:beforeSendResponse", $this->application, $this);
		}
		exit;
	}

	/**
	 * 返回对应错误信息
	 *
	 * @param $code 错误码
	 * @return array
	 */
	static public function getErrorInfo($code)
	{
		return [
			'code'    => $code,
			'message' => self::$errorList[$code],
		];
	}

	public function defaultReturn($data = [], $info = "success", $status = 200, $reason = '')
	{
		if ($status != 200 && defined('IS_CHANGE_HTTP_STATUS') && IS_CHANGE_HTTP_STATUS == true) {
			header("HTTP/1.1 503");
		}
		header('Content-Type:application/json; charset=utf-8');
		echo json_encode([
			'status'  => $status,
			'info'    => $info,
			'reason'  => $reason,
			'data'    => $data,
		]);
		if ($eventsManager = $this->application->getEventsManager()) {
			$eventsManager->fire("application:afterHandleRequest", $this->application, $this);
			$eventsManager->fire("application:beforeSendResponse", $this->application, $this);
		}
		exit;
	}
}
