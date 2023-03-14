<?php
/**
 * mongoDB 基类
 */

namespace module\models;

use module\lib\MongoClient;

class Model
{

    protected static $client;

    public function __construct()
    {
        if (is_null(self::$client) || !self::$client instanceof \MongoDB\Client) {
            self::$client = (new MongoClient())->getClient();
        }
    }

    public function dbName()
    {
        return '';
    }

    public function tableName()
    {
        return '';
    }

    public static function model()
    {
        return new static();
    }

    public function insertOne($document, array $options = [])
    {
        $collection = (self::$client)->{$this->dbName()}->{$this->tableName()};
        $insertOneResult = $collection->insertOne($document, $options);
        //printf("Inserted %d document(s)\n", $insertOneResult->getInsertedCount());
        //var_dump($insertOneResult->getInsertedId());
        return $insertOneResult->getInsertedCount();
    }

    public function insertMany(array $documents, array $options = [])
    {
        $collection = (self::$client)->{$this->dbName()}->{$this->tableName()};
        $insertManyResult = $collection->insertMany($documents, $options);
        //printf("Inserted %d document(s)\n", $insertManyResult->getInsertedCount());
        //var_dump($insertManyResult->getInsertedIds());
        return $insertManyResult->getInsertedCount();
    }

    public function findOne($filter = [], array $options = [])
    {
        $collection = (self::$client)->{$this->dbName()}->{$this->tableName()};
        $document = $collection->findOne($filter, $options);
        return $document;
    }

    //$cursor = $collection->find(['_id'=>''], ['limit' => 5, 'sort' => ['pop' => -1]]);
    public function findMany($filter = [], array $options = [])
    {
        $collection = (self::$client)->{$this->dbName()}->{$this->tableName()};
        $documents = $collection->find($filter, $options);
        //foreach ($documents as $document) {
        //    echo $document['_id'], "\n";
        //}
        return $documents;
    }

    public function updateOne($filter, $update, array $options = [])
    {
        $collection = (self::$client)->{$this->dbName()}->{$this->tableName()};
        $updateResult = $collection->updateOne($filter, $update, $options);
        return $updateResult->getModifiedCount();
    }

    public function updateMany($filter, $update, array $options = [])
    {
        $collection = (self::$client)->{$this->dbName()}->{$this->tableName()};
        $updateResult = $collection->updateMany($filter, $update, $options);
        return $updateResult->getModifiedCount();
    }

    public function deleteOne($filter, array $options = [])
    {
        $collection = (self::$client)->{$this->dbName()}->{$this->tableName()};
        $deleteResult = $collection->deleteOne($filter, $options);
        return $deleteResult->getDeletedCount();
    }

    public function deleteMany($filter, array $options = [])
    {
        $collection = (self::$client)->{$this->dbName()}->{$this->tableName()};
        $deleteResult = $collection->deleteMany($filter, $options);
        return $deleteResult->getDeletedCount();
    }

}