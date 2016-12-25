<?php
/**
 * MoveAdminAction class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.web.controllers.actions.QsActionAdminInternalDbTransaction', true);
 
/**
 * Admin panel action, which moves models according to {@link QsActiveRecordBehaviorPosition}.
 * If movement is successful, the browser will be redirected to the 'index' page.
 * Note: this action requires controller to provide method "loadModel(mixed $id)",
 * which should retrieve the model instance by its primary key.
 * You can use {@link QsControllerBehaviorAdminDataModel} behavior with this action.
 * @see QsActiveRecordBehaviorPosition
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.controllers.actions
 */
class QsActionAdminMove extends QsActionAdminInternalDbTransaction {
	/**
	 * @var mixed name of the attribute, which sort should be applied on
	 * models list page after the movement is complete.
	 */
	public $sortAttributeName = null;

	/**
	 * Runs the action.
	 * @param mixed $id - model primary key
	 * @param string $to - movement direction
	 */
	public function run($id, $to) {
		$controller = $this->getController();

		$model = $controller->loadModel($id);
		$this->moveModel($model, $to);

		$redirectUrl = $this->createSuccessRedirectUrl($model);
		$controller->redirect($redirectUrl);
	}

	/**
	 * Perform model movement to given direction.
	 * @param CModel $model model to be moved.
	 * @param string $direction movement direction name.
	 */
	protected function moveModel(CModel $model, $direction) {
		try {
			$this->beginInternalDbTransaction();

			switch ($direction) {
				case 'first': {
					$model->moveFirst();
					break;
				}
				case 'prev': {
					$model->movePrev();
					break;
				}
				case 'next': {
					$model->moveNext();
					break;
				}
				case 'last': {
					$model->moveLast();
					break;
				}
				case 'up': {
					$model->movePrev();
					break;
				}
				case 'down': {
					$model->moveNext();
					break;
				}
				default: {
					if (is_numeric($direction)) {
						$model->moveToPosition($direction);
					} else {
						throw new CHttpException(400,"Invalid request. Unrecognized move direction '{$direction}'.");
					}
				}
			}

			$this->commitInternalDbTransaction();
		} catch (Exception $exception) {
			$this->rollbackInternalDbTransaction();
			throw $exception;
		}
	}

	/**
	 * Creates the URL, to which should be applied after the successful model movement.
	 * @param CModel $model model instance.
	 * @return array URL configuration.
	 */
	protected function createSuccessRedirectUrl($model) {
		$getParameters = $_GET;
		unset($getParameters['id']);
		unset($getParameters['to']);

		$sortAttributeName = $this->sortAttributeName;
		if ($sortAttributeName!==false) {
			if (empty($sortAttributeName)) {
				$sortAttributeName = $model->getPositionAttributeName();
			}
			$sortGetParameterName = get_class($model).'_sort';
			$getParameters[$sortGetParameterName] = $sortAttributeName;
		}

		$url = array_merge(array('index'), $getParameters);
		return $url;
	}
}