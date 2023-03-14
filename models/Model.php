<?php
/**
 * mongoDB 基类
 */

namespace module\models;

use module\lib\MongoClient;

class Model
{

    protected $collectionName;
    protected $mongoClient;

    public function __construct()
    {
        $this->mongoClient = (new MongoClient())->getClient();
    }

    public function dbName()
    {
        return '';
    }

    public function tableName()
    {
        return '';
    }

    public function insertOne($document, array $options = [])
    {
        $collection = ($this->mongoClient)->{$this->dbName()}->{$this->tableName()};
        $insertOneResult = $collection->insertOne($document, $options);
        //printf("Inserted %d document(s)\n", $insertOneResult->getInsertedCount());
        //var_dump($insertOneResult->getInsertedId());
        return $insertOneResult->getInsertedCount();
    }

    public function insertMany(array $documents, array $options = [])
    {
        $collection = ($this->mongoClient)->{$this->dbName()}->{$this->tableName()};
        $insertManyResult = $collection->insertMany($documents, $options);
        //printf("Inserted %d document(s)\n", $insertManyResult->getInsertedCount());
        //var_dump($insertManyResult->getInsertedIds());
        return $insertManyResult->getInsertedCount();
    }

    public function findOne($filter = [], array $options = [])
    {
        $collection = ($this->mongoClient)->{$this->dbName()}->{$this->tableName()};
        $document = $collection->findOne($filter, $options);
        return $document;
    }

    public function findMany($filter = [], array $options = [])
    {
        $collection = ($this->mongoClient)->{$this->dbName()}->{$this->tableName()};
        $documents = $collection->find($filter, $options);
        //foreach ($documents as $document) {
        //    echo $document['_id'], "\n";
        //}
        return $documents;
    }

    public function updateOne($filter, $update, array $options = [])
    {
        $collection = ($this->mongoClient)->{$this->dbName()}->{$this->tableName()};
        $updateResult = $collection->updateOne($filter, $update, $options);
        return $updateResult->getModifiedCount();
    }

    public function updateMany($filter, $update, array $options = [])
    {
        $collection = ($this->mongoClient)->{$this->dbName()}->{$this->tableName()};
        $updateResult = $collection->updateMany($filter, $update, $options);
        return $updateResult->getModifiedCount();
    }


    public function deleteOne($filter, array $options = [])
    {
        $collection = ($this->mongoClient)->{$this->dbName()}->{$this->tableName()};
        $deleteResult = $collection->deleteOne($filter, $options);
        return $deleteResult->getDeletedCount();
    }

    public function deleteMany($filter, array $options = [])
    {
        $collection = ($this->mongoClient)->{$this->dbName()}->{$this->tableName()};
        $deleteResult = $collection->deleteMany($filter, $options);
        return $deleteResult->getDeletedCount();
    }

}