<?php
namespace DB\Mongodb;

/**
 * mongodb 实体事件接口
 * Interface EntityEvent
 *
 * @package DB\Mongodb
 */
interface EntityEvent {
	public function insertBefore(MongodbEntity $entityBase);

	public function insertAfter( MongodbEntity $entityBase );

	public function deleteBefore( MongodbEntity $entityBase );

	public function deleteAfter( MongodbEntity $entityBase );

	public function updateBefore( MongodbEntity $entityBase );

	public function updateAfter( MongodbEntity $entityBase );

}