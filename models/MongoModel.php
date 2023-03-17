<?php
/**
 * mongoDB 基类
 */

namespace module\models;

use module\lib\DbManager;
use MongoDB\Client;

class MongoModel
{
    /**
     * @var Client
     */
    protected $client;

    protected $prefix = '';

    public function __construct()
    {
        $this->client = DbManager::getMongoDb();
    }

    public function dbName()
    {
        return '';
    }

    public function tableName()
    {
        return '';
    }

    public function realTableName()
    {
        return $this->prefix . $this->tableName();
    }

    public static function model()
    {
        return new static();
    }

    public function insertOne($document, array $options = [])
    {
        $collection = $this->client->{$this->dbName()}->{$this->realTableName()};
        $insertOneResult = $collection->insertOne($document, $options);
        return $insertOneResult->getInsertedCount();
    }

    public function insertMany($documents, array $options = [])
    {
        $collection = $this->client->{$this->dbName()}->{$this->realTableName()};
        $insertManyResult = $collection->insertMany($documents, $options);
        //printf("Inserted %d document(s)\n", $insertManyResult->getInsertedCount());
        //var_dump($insertManyResult->getInsertedIds());
        return $insertManyResult->getInsertedCount();
    }

    public function findOne($filter = [], array $options = [])
    {
        $collection = $this->client->{$this->dbName()}->{$this->realTableName()};
        $document = $collection->findOne($filter, $options);
        return $document;
    }

    //$cursor = $collection->find(['_id'=>''], ['limit' => 5, 'sort' => ['pop' => -1]]);
    public function findMany($filter = [], array $options = [])
    {
        $collection = $this->client->{$this->dbName()}->{$this->realTableName()};
        $documents = $collection->find($filter, $options);
        return $documents;
    }

    public function updateOne($filter, $update, array $options = [])
    {
        $collection = $this->client->{$this->dbName()}->{$this->realTableName()};
        $updateResult = $collection->updateOne($filter, ['$set' => $update], $options);
        return $updateResult->getModifiedCount();
    }

    public function updateMany($filter, $update, array $options = [])
    {
        $collection = $this->client->{$this->dbName()}->{$this->realTableName()};
        $updateResult = $collection->updateMany($filter, ['$set' => $update], $options);
        return $updateResult->getModifiedCount();
    }

    public function deleteOne($filter, array $options = [])
    {
        $collection = $this->client->{$this->dbName()}->{$this->realTableName()};
        $deleteResult = $collection->deleteOne($filter, $options);
        return $deleteResult->getDeletedCount();
    }

    public function deleteMany($filter, array $options = [])
    {
        $collection = $this->client->{$this->dbName()}->{$this->realTableName()};
        $deleteResult = $collection->deleteMany($filter, $options);
        return $deleteResult->getDeletedCount();
    }

}