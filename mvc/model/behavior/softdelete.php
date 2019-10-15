<?php
namespace Phalcon\Mvc\Model\Behavior;

use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\Behavior;
use Phalcon\Mvc\Model\Exception;

class SoftDelete extends Behavior
{
	public function notify($type, $model)
	{

		if ($type == "beforeDelete")
		{
			$options = $this->getOptions();

			if (!(function() { if(isset($options["value"])) {$value = $options["value"]; return $value; } else { return false; } }()))
			{
				throw new Exception("The option 'value' is required");
			}

			if (!(function() { if(isset($options["field"])) {$field = $options["field"]; return $field; } else { return false; } }()))
			{
				throw new Exception("The option 'field' is required");
			}

			$model->skipOperation(true);

			if ($model->readAttribute($field) <> $value)
			{
				$modelsManager = $model->getModelsManager();

				$updateModel = clone $model;

				$updateModel->writeAttribute($field, $value);

				if (!($updateModel->save()))
				{
					foreach ($updateModel->getMessages() as $message) {
						$model->appendMessage($message);
					}

					return false;
				}

				$model->writeAttribute($field, $value);

				if ($modelsManager->isKeepingSnapshots($model) && globals_get("orm.update_snapshot_on_save"))
				{
					$metaData = $model->getModelsMetaData();

					$model->setSnapshotData($updateModel->getSnapshotData());

					$model->setOldSnapshotData($updateModel->getOldSnapshotData());

				}

			}

		}

	}


}