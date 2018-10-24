<?php
namespace Common\Mongo;

/**
 * mongo 封装类
 * @User mjf
 * @DateTime 2018年7月18日
 *
 */
class BaseMongo{
    private $_mongo = '';
    private $_db = '';
    private $_collection = '';
    
    public function __construct($dbName=NULL) {
        vendor('Mongo.autoload');
        
        $DBconfig = C('DB_MONGO');
        //是否有账号密码
        if($DBconfig['DB_USER'] != '' && $DBconfig['DB_PWD'] != ''){
            $server = sprintf("mongodb://%s:%s@%s:%s/%s", $DBconfig['DB_USER'], $DBconfig['DB_PWD'], $DBconfig['DB_HOST'], $DBconfig['DB_PORT'], $DBconfig['DB_NAME']);
        }else{
            $server = sprintf("mongodb://%s:%s", $DBconfig['DB_HOST'], $DBconfig['DB_PORT']);
        }
        
        try {
            $client = new \MongoDB\Client($server);
            
            // 命令前缀
            if(!isset($config['cmd'])){
                $this->_cmd = ini_get('mongo.cmd');
                if($this->_cmd == ''){
                    $this->_cmd = '$';
                }
            }
            
            $this->_mongo = $client;
            $this->_db = !empty($dbName) ? $dbName : $DBconfig['DB_NAME'];
            
            return $this->_mongo;
        }catch (\MongoDB\Exception $e){
            if(self::DEBUG) {
                echo $e->getMessage();
            }
            return false;
        }
    }
    
    /**
     * 设置数据库
     * 
     * @User mjf
     * @DateTime 2018年7月19日
     *
     * @param string $dbName
     */
    public function setdB($dbName){
        $this->_db = $dbName;
    }
    
    /**
     * 设置collection
     * 
     * @User mjf
     * @DateTime 2018年7月19日
     *
     * @param string $collectionName
     */
    public function setCollection($collectionName){
        $this->_collection = $this->_mongo->selectCollection($this->_db, $collectionName);
        return $this;
    }
    
    protected function _beforeFind(){
    
    }
    
    protected function _beforeInsert(){
    
    }
    
    protected function _beforeUpdate(){
    
    }
    
    protected function _afterFind(){
        
    }
    
    protected function _afterInsert(){
        
    }
    
    protected function _afterUpdate(){
    
    }
    
    /**
     * 查找一条记录
     *  $document = $collection->findOne([
            'game_id' => '71727339' 
        ], [
            'projection' => [
                'game_id' => 1  // 显示game_id字段
            ] 
        ]);
        
     * @User mjf
     * @DateTime 2018年7月18日
     *
     * @param string $collectionName
     * @param array $filter
     * @param array $options
     * @return Ambigous <multitype:, object, NULL>
     */
    public function findOne($filter = [], array $options = []) {
        return $this->_collection->findOne($filter, $options);
    }
    
    /**
     * 查找多条记录
     * 
     * @User mjf
     * @DateTime 2018年7月18日
     *
     * @param string $collectionName
     * @param array $filter
     * @param array $options=['projection'=>['id'=>1], 'limit'=>10,  'sort' => ['time' => -1]]
     */
    public function findAll($filter = [], array $options = []) {
        $data = $this->_collection->find($filter, $options);
        
        return $data;
    }
    
    public function count($filter = [], array $options = []){
        $data = $this->_collection->count($filter, $options);
        
        return $data;
    }
    
    public function insertOne($document, array $options = []){
        $data = $this->_collection->insertOne($document, $options);
        
        return $data;
    }
    
    public function insertMany(array $documents, array $options = []){
        $data = $this->_collection->insertMany($documents, $options);
    
        return $data;
    }
    
    public function updateOne($filter, $update, array $options = []) {
        $data = $this->_collection->updateOne($filter, $update, $options);
    
        return $data;
    }
    
    public function updateMany($filter, $update, array $options = []) {
        $data = $this->_collection->updateMany($filter, $update, $options);
    
        return $data;
    }
    
    public function findOneAndUpdate($filter, $update, array $options = []){
        $data = $this->_collection->findOneAndUpdate($filter, $update, $options);
        
        return $data;
    }
    
    
}