<?php
/**
 * QsOpenAuthApi class file.
 *
 * @author Alexander Khromychenko <sanekfl@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2012 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * Component that implements OpenAuth protocol.
 *
 * Configuration example:<br />
 * <code>
 * 'oAuth' => array(
 *        'class' => 'ext.local.TestOAuth',
 *         'appAccessControlFilter' => array(
 *             'class' => 'ext.qs.oauth.QsAppAccessControlFilter',
 *             'appAccessRule' => array(
 *                 'class' => 'ext.qs.oauth.QsAppAccessRule'
 *             ),
 *         ),
 * ),
 *
 * class MyOAuth extends QsOpenAuth
 * {
 *        public function postUserName()
 *        {
 *            return $this->userModel->name;
 *        }
 * }
 *
 * </code>
 *
 * User login usage:
 * <code>
 * if ($model->login())
 * {
 *         Yii::app()->oAuth->getAccessToken(Yii::app()->user->id);
 *         $this->redirect( Yii::app()->user->getReturnUrl( array('account/index') ) );
 * }
 * </code>
 *
 * Listener usage:
 * <code>
 * Yii::app()->getComponent('oAuth')->listen();
 * </code>
 *
 * Migration for the {@link userModelClassName} table:
 * m110317_140100_create_tables_user
 *
 * Migration for the {@link accessTokenModelClassName} table:
 * m121009_110215_create_table_token
 *
 * Migration for the {@link applicationModelClassName} table:
 * m121010_113143_create_table_application
 *
 *
 * @author Alexander Khromychenko <sanekfl@quartsoft.com>
 */
abstract class QsOpenAuthApi extends CApplicationComponent {
	/**
	 * @var string name of the {@link CActiveRecord} model, which stores users.
	 */
	protected $_userModelClassName = 'User';
	/**
	 * @var string name of the {@link CActiveRecord} model, which stores access tokens.
	 */
	protected $_accessTokenModelClassName = 'AccessToken';
	/**
	 * @var integer lifetime of the access token in seconds.
	 */
	protected $_accessTokenLifeTime = 3600;
	/**
	 * @var string name of the {@link CActiveRecord} model, which stores applications.
	 */
	protected $_applicationModelClassName = 'Application';
	/**
	 * @var string salt for generating token
	 */
	protected $_salt = '#dD3^';
	/**
	 * @var \QsAppAccessControlFilter
	 */
	protected $_appAccessControlFilter = array(
		'class' => 'ext.qs.oauth.QsAppAccessControlFilter'
	);
	/**
	 * @var CActiveRecord Application model.
	 */
	protected $_applicationModel;
	/**
	 * @var CActiveRecord User model.
	 */
	protected $_userModel;
	/**
	 * @var CActiveRecord Access token model.
	 */
	protected $_accessTokenModel;
	/**
	 * @var mixed query condition or criteria.
	 */
	protected $_applicationFindCondition = array();
	/**
	 * @var mixed query condition or criteria.
	 */
	protected $_accessTokenFindCondition = array();
	/**
	 * @var mixed query condition or criteria.
	 */
	protected $_userFindCondition = array();
	/**
	 * @var string Default params set name for actions
	 */
	protected $_defaultParamsSet = 'request';
	/**
	 * @var boolean
	 */
	public $prolongAccessTokenLifeTime = false;

	// Set / Get :

	public function getUserModelClassName() {
		return $this->_userModelClassName;
	}

	public function setUserModelClassName($userModelClassName) {
		$this->_userModelClassName = $userModelClassName;
	}

	public function getAccessTokenModelClassName() {
		return $this->_accessTokenModelClassName;
	}

	public function setAccessTokenModelClassName($accessTokenModelClassName) {
		$this->_accessTokenModelClassName = $accessTokenModelClassName;
	}

	public function getAccessTokenLifeTime() {
		return $this->_accessTokenLifeTime;
	}

	public function setTokenLifeTime($accessTokenLifeTime) {
		$this->_accessTokenLifeTime = $accessTokenLifeTime;
	}

	public function getApplicationModelClassName() {
		return $this->_applicationModelClassName;
	}

	public function setApplicationModelClassName($applicationModelClassName) {
		$this->_applicationModelClassName = $applicationModelClassName;
	}

	public function getSalt() {
		return $this->_salt;
	}

	public function setSalt($salt) {
		$this->_salt = $salt;
	}

	public function getUserModel() {
		return $this->_userModel;
	}

	public function getApplicationFindCondition() {
		return $this->_applicationFindCondition;
	}

	public function setApplicationFindCondition($applicationFindCondition) {
		$this->_applicationFindCondition = $applicationFindCondition;
	}

	public function getAccessTokenFindCondition() {
		return $this->_accessTokenFindCondition;
	}

	public function setAccessTokenFindCondition($accessTokenFindCondition) {
		$this->_accessTokenFindCondition = $accessTokenFindCondition;
	}

	public function getUserFindCondition() {
		return $this->_userFindCondition;
	}

	public function setUserFindCondition($userFindCondition) {
		$this->_userFindCondition = $userFindCondition;
	}

	public function getApplicationModel() {
		return $this->_applicationModel;
	}

	public function getAppAccessControlFilter() {
		return $this->_appAccessControlFilter;
	}

	public function setAppAccessControlFilter($appAccessControlFilter) {
		$this->_appAccessControlFilter = $appAccessControlFilter;
	}

	public function getAccessTokenModel() {
		return $this->_accessTokenModel;
	}

	/**
	 * Finds application model by ID
	 *
	 * @param type $appId application ID
	 * @return CActiveRecord Application model.
	 */
	public function findApplicationModel($appId) {
		$applicationModelFinder = CActiveRecord::model($this->getApplicationModelClassName());

		return $applicationModelFinder->findByPk($appId, $this->getApplicationFindCondition());
	}

	public function init() {
		if ($this->accessRules()) {
			$this->_appAccessControlFilter = Yii::createComponent($this->_appAccessControlFilter);
			$this->_appAccessControlFilter->rules = $this->accessRules();
		}
	}

	/**
	 * Sets of required param fields in request
	 *
	 * @return array
	 */
	protected function requiredParamsSets() {
		return array(
			'login' => array('timestamp', 'appId', 'hash'),
			'request' => array('timestamp', 'appId', 'hash', 'accessToken', 'cmd'),
		);
	}

	/**
	 * Sets required params set for actions
	 *
	 * @return array
	 */
	protected function actionParamsSets() {
		return array();
	}

	protected function getActionParamsSet($action) {
		$actionParamsSets = $this->actionParamsSets();
		foreach ($actionParamsSets as $paramsSet => $actions) {
			if (in_array($action, $actions)) {
				return $paramsSet;
			}
		}

		return $this->_defaultParamsSet;
	}

	/**
	 * Access rules for applications
	 *
	 * @return array rules
	 */
	public function accessRules() {
		return array();
	}

	/**
	 * @param integer $userId
	 * @param integer $appId
	 * @return CActiveRecord access token model.
	 */
	protected function findAccessTokenModel($userId, $appId) {
		$modelFinder = CActiveRecord::model($this->getAccessTokenModelClassName());
		$accessTokenModel = $modelFinder->findByAttributes(
			array(
				'user_id' => $userId,
				'application_id' => $appId,
			),
			$this->getAccessTokenFindCondition()
		);

		return $accessTokenModel;
	}

	/**
	 *
	 * @param type $userId
	 * @return CActiveRecord user model
	 */
	protected function findUser($userId) {
		$userModelFinder = CActiveRecord::model($this->getUserModelClassName());

		return $userModelFinder->findByPk($userId, $this->getUserFindCondition());
	}

	/**
	 * Generates access token for user.
	 * Requires "appId", "timestamp", and "hash" parameters
	 * Hash is the sha1 hash from values of sorted with ksort array of "appId",
	 * "timestamp" and application secret key parameters. "." is separator.
	 *
	 * @see calculateHash()
	 * @return string access token
	 * @throws CHttpException when unauthorized user try to get token
	 */
	public function generateAccessToken($userId, array $requestParams) {
		if ($this->findUser($userId)) {
			if ($this->validateRequestParams($requestParams, 'login')) {
				$accessTokenModel = $this->createToken($this->applicationModel->id, $userId);

				return $accessTokenModel->token;
			}
		}

		throw new CHttpException(403, 'You are not authorized to perform this action');
	}

	/**
	 * Creates new access token.
	 *
	 * @param integer $appId ID of the application for wich token is created
	 * @param integer $userId ID of the logged in user
	 * @param CActiveRecord $accessTokenModel Access token model
	 * @return CActiveRecord Access token model
	 */
	protected function createToken($appId, $userId) {
		$accessTokenModel = $this->findAccessTokenModel($userId, $appId);
		if (!$accessTokenModel) {
			$accessTokenModelClassname = $this->getAccessTokenModelClassName();
			$accessTokenModel = new $accessTokenModelClassname();
		}

		$accessTokenModel->user_id = $userId; // @todo explicit user id use
		$accessTokenModel->application_id = $appId;
		$accessTokenModel->expire_date = $this->getNewTokenExpireDate();
		$accessTokenModel->token = $this->generateRandomHash();

		return $this->saveToken($accessTokenModel);
	}

	/**
	 *
	 * @param type $accessTokenModel
	 * @return CActiveRecord Access token model
	 */
	protected function saveToken($accessTokenModel) {
		if ($accessTokenModel->validate()) {
			$accessTokenModel->save();

			return $accessTokenModel;
		}

		throw new CException('Token doesn\'t correct');
	}

	/**
	 *
	 * @return string Date in format 'Y-m-d H:i:s'.
	 */
	protected function getNewTokenExpireDate() {
		return date('Y-m-d H:i:s', time() + $this->getAccessTokenLifeTime());
	}

	/**
	 * Prolongs accesstoken lifetime
	 *
	 * @param CActiveRecord $accessTokenModel Access token model.
	 * @return CActiveRecord Access token model.
	 */
	protected function prolongAccessTokenLifeTime($accessTokenModel) {
		if ($accessTokenModel) {
			$accessTokenModel->expire_date = $this->getNewTokenExpireDate();
			$accessTokenModel->save(true);

			return $accessTokenModel;
		}

		return false;
	}

	/**
	 * Validate request parameters before executing requests.
	 *
	 * @param array $params request parameters
	 * @return true when params valid
	 * @throws CHttpException when request params not valid
	 */
	protected function validateRequestParams(array $params, $requiredParamsSetName) {
		$requiredParams = $this->requiredParamsSets();
		$requiredParams = $requiredParams[$requiredParamsSetName];
		$allParamsPresent = true;
		foreach ($requiredParams as $param) {
			if (!array_key_exists($param, $params)) {
				$allParamsPresent = false;
			}
		}

		if ($allParamsPresent) {
			$appId = $params['appId'];
			$applicationModel = $this->findApplicationModel($appId);
			$this->_applicationModel = $applicationModel;
			if ($applicationModel) {

				if (array_key_exists('hash', $params)) {
					$hash = $params['hash'];
					unset($params['hash']);
				}

				if ($this->calculateHash($params, $applicationModel) == $hash) {
					//if its usual request, not login (when login accessToken not exists yet)
					if (array_key_exists('accessToken', $params)) {
						$tokenModelFinder = CActiveRecord::model($this->getAccessTokenModelClassName());
						$accessTokenFindCondition = $this->getAccessTokenFindCondition();
						$tokenModel = $tokenModelFinder->findByAttributes(array('token' => $params['accessToken']), $accessTokenFindCondition);
						if ($tokenModel != null) {
							if ($this->checkExpireDate($tokenModel)) {
								$this->_userModel = $this->findUser($tokenModel->user_id);
								$this->_accessTokenModel = $tokenModel;

								return true;
							}

							throw new CHttpException(403, 'Token lifetime expired');
						}

						throw new CHttpException(403, 'Token is not valid');
					}

					return true;
				}

				throw new CHttpException(403, 'Hash is not valid');
			}

			throw new CHttpException(403, 'Application doesn\'t exists');
		}

		throw new CHttpException(403, 'Forbidden');
	}

	/**
	 * Sort $params array with ksort and calculates sha1 hash
	 * of array values.
	 * @param array $params
	 * @param CActiveRecord $applicationModel
	 * @return string
	 */
	public function calculateHash(array $params, $applicationModel) {
		$params['secretKey'] = $applicationModel->secret_key;
		ksort($params);
		$paramString = implode('.', $params);

		return sha1($paramString);
	}

	/**
	 *
	 * @param CActiveRecord $tokenModel Access token model.
	 * @return boolean true if token lifetime not expired
	 */
	protected function checkExpireDate($accessTokenModel) {
		$expireDate = strtotime($accessTokenModel->expire_date);

		return ($expireDate >= time());
	}

	/**
	 *
	 * @return string sha1 hash
	 */
	public function generateRandomHash() {
		return sha1(microtime() . $this->getSalt());
	}

	/**
	 * Parses GET, POST, PUT, DELETE params, validates request and calls user
	 * function with name consist of <request type><cmd param> (for example if
	 * $_GET['cmd'] == 'DoSomething' function name will be "getDoSomething")
	 * and passes it all parameters from request.
	 * Params "appId", "timestamp", "hash", "accessToken", and "cmd" are required.
	 * "hash" is sha1 hash of values of sorted with ksort array of these params
	 * and application secret key with "." separator.
	 *
	 * @see calculateHash
	 * @see composeResult
	 * @param boolean $extendTokenLifetime Resets access token lifetime
	 * @return string result of callback composed by composeResult function
	 * @throws CHttpException
	 */
	public function listen() {
		$requestType = $this->getRequestType();
		$prefix = '';
		$params = array();
		switch ($requestType) {
			case 'GET':
				$prefix = 'get';
				$params = $_GET;
				break;

			case 'POST':
				$prefix = 'post';
				$params = $_POST;
				break;

			case 'PUT':
				$prefix = 'put';
				$params = $this->getRestParams();
				break;

			case 'DELETE':
				$prefix = 'delete';
				$params = $this->getRestParams();
				break;
			default:
				throw new CHttpException(400, 'Invalid request');
		}

		try {
			$paramsSet = $this->getActionParamsSet($params['cmd']);
			if ($this->validateRequestParams($params, $paramsSet)) {
				$accessTokenModel = $this->getAccessTokenModel();
				$method = $prefix . $params['cmd'];
				if (method_exists($this, $method)) {
					if ($this->accessRules()) {
						$this->_appAccessControlFilter->checkFiler($this->getApplicationModel(), $method);
					}

					if ($this->prolongAccessTokenLifeTime) {
						$this->prolongAccessTokenLifeTime($accessTokenModel);
					}

					$result = call_user_func(array($this, $method), $params);

					return $this->composeResult($result, 200);
				} else {
					throw new CHttpException(400, 'Unknown command');
				}
			}
		} catch (CHttpException $exc) {
			return $this->composeResult(null, $exc->statusCode, $exc->getMessage());
		}
		catch (CException $exc) {
			return $this->composeResult(null, 500, $exc->getMessage());
		}
	}

	/**
	 * @return string
	 */
	protected function getRequestType() {
		return Yii::app()->getRequest()->requestType;
	}

	/**
	 * Returns the PUT or DELETE request parameters.
	 * @return array the request parameters
	 */
	protected function getRestParams() {
		$result = array();
		if (function_exists('mb_parse_str')) {
			mb_parse_str(file_get_contents('php://input'), $result);
		} else {
			parse_str(file_get_contents('php://input'), $result);
		}

		return $result;
	}

	/**
	 * Creates new application
	 *
	 * @param string $applicationName
	 * @return array application model attributes
	 */
	public function createApplication($applicationName) {
		$applicationModelClassName = $this->getApplicationModelClassName();
		$applicationModel = new $applicationModelClassName();
		$applicationModel->name = $applicationName;
		$applicationModel->secret_key = $this->generateRandomHash();
		if ($applicationModel->validate()) {
			$applicationModel->save();

			return $applicationModel->attributes;
		}

		return null;
	}

	/**
	 * $response['hash'] is the sha1 hash of timestamp, status and application
	 * secret key with "." separator.
	 *
	 * @see calculateHash
	 * @param mixed $result
	 * @return string JSON string
	 */
	protected function composeResult($result, $status, $errorMessage = null) {
		$response = array(
			'timestamp' => time(),
			'result' => $result,
			'status' => $status,
		);
		if ($errorMessage) {
			$response['errorMessage'] = $errorMessage;
		}
		$responseForHash = $response;
		unset($responseForHash['result']);
		$response['hash'] = $this->calculateHash($responseForHash, $this->getApplicationModel());

		return $this->encode($response);
	}

	/**
	 * encodes $data to JSON
	 *
	 * @param mixed $data
	 * @return string JSON encoded $data
	 */
	public function encode($data) {
		return CJSON::encode($data);
	}

}
