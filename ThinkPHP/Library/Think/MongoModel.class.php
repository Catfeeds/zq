<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2013 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think;
/**
 * ThinkPHP Model模型类
 * 实现了ORM和ActiveRecords模式
 */
class MongoModel {
    // 操作状态
    const MODEL_INSERT          =   1;      //  插入模型数据
    const MODEL_UPDATE          =   2;      //  更新模型数据
    const MODEL_BOTH            =   3;      //  包含上面两种方式
    const MUST_VALIDATE         =   1;      // 必须验证
    const EXISTS_VALIDATE       =   0;      // 表单存在字段则验证
    const VALUE_VALIDATE        =   2;      // 表单值不为空则验证

    // 当前数据库操作对象
    protected $db               =   null;
    // 主键名称
    protected $pk               =   'id';
    // 主键是否自动增长
    protected $autoinc          =   false;
    // 数据表前缀
    protected $tablePrefix      =   null;
    // 模型名称
    protected $name             =   '';
    // 数据库名称
    protected $dbName           =   '';
    //数据库配置
    protected $connection       =   '';
    // 数据表名（不包含表前缀）
    protected $tableName        =   '';
    // 实际数据表名（包含表前缀）
    protected $trueTableName    =   '';
    // 最近错误信息
    protected $error            =   '';
    // 字段信息
    protected $fields           =   array();
    // 数据信息
    protected $data             =   array();
    // 查询表达式参数
    protected $options          =   array();
    protected $_validate        =   array();  // 自动验证定义
    protected $_auto            =   array();  // 自动完成定义
    protected $_map             =   array();  // 字段映射定义
    protected $_scope           =   array();  // 命名范围定义
    // 是否自动检测数据表字段信息
    protected $autoCheckFields  =   true;
    // 是否批处理验证
    protected $patchValidate    =   false;
    // 链操作方法列表
    protected $methods          =   array('order','alias','having','group','lock','distinct','auto','filter','validate','result','token','master');

    /**
     * 架构函数
     * 取得DB类的实例对象 字段检查
     * @access public
     * @param string $name 模型名称
     * @param string $tablePrefix 表前缀
     * @param mixed $connection 数据库连接信息
     */
    public function __construct($name='',$tablePrefix='',$connection='',$reConn=false) {
        // 模型初始化
        $this->_initialize();
        // 获取模型名称
        if(!empty($name)) {
            if(strpos($name,'.')) { // 支持 数据库名.模型名的 定义
                list($this->dbName,$this->name) = explode('.',$name);
            }else{
                $this->name   =  $name;
                $this->options['table'] = $name;
            }
        }elseif(empty($this->name)){
            $this->name =   $this->getModelName();
        }
        // 设置表前缀
        if(is_null($tablePrefix)) {// 前缀为Null表示没有前缀
            $this->tablePrefix = '';
        }elseif('' != $tablePrefix) {
            $this->tablePrefix = $tablePrefix;
        }elseif(!isset($this->tablePrefix)){
            $this->tablePrefix = C('DB_PREFIX');
        }

        // 数据库初始化操作
        // 获取数据库操作对象
        // 当前模型有独立的数据库连接信息
        $this->db(0,empty($this->connection)?$connection:$this->connection,true,$reConn);
    }

    /**
     * 自动检测数据表信息
     * @access protected
     * @return void
     */
    protected function _checkTableInfo() {
        // 如果不是Model类 自动记录数据表信息
        // 只在第一次执行记录
        if(empty($this->fields)) {
            // 如果数据表字段没有定义则自动获取
            if(C('DB_FIELDS_CACHE')) {
                $db   =  $this->dbName?$this->dbName:C('DB_NAME');
                $fields = F('_fields/'.strtolower($db.'.'.$this->name));
                if($fields) {
                    $this->fields   =   $fields;
                    $this->pk       =   $fields['_pk'];
                    return ;
                }
            }
            // 每次都会读取数据表信息
            $this->flush();
        }
    }

    /**
     * 获取字段信息并缓存
     * @access public
     * @return void
     */
    public function flush() {
        // 缓存不存在则查询数据表信息
        $this->db->setModel($this->name);
        $fields =   $this->db->getFields($this->getTableName());
        if(!$fields) { // 无法获取字段信息
            return false;
        }
        $this->fields   =   array_keys($fields);
        foreach ($fields as $key=>$val){
            // 记录字段类型
            $type[$key]     =   $val['type'];
            if($val['primary']) {
                $this->pk   =   $key;
                $this->fields['_pk']   =   $key;
                if($val['autoinc']) $this->autoinc   =   true;
            }
        }
        // 记录字段类型信息
        $this->fields['_type'] =  $type;

        // 2008-3-7 增加缓存开关控制
        if(C('DB_FIELDS_CACHE')){
            // 永久缓存数据表信息
            $db   =  $this->dbName?$this->dbName:C('DB_NAME');
            F('_fields/'.strtolower($db.'.'.$this->name),$this->fields);
        }
    }

    /**
     * 设置数据对象的值
     * @access public
     * @param string $name 名称
     * @param mixed $value 值
     * @return void
     */
    public function __set($name,$value) {
        // 设置数据对象属性
        $this->data[$name]  =   $value;
    }

    /**
     * 获取数据对象的值
     * @access public
     * @param string $name 名称
     * @return mixed
     */
    public function __get($name) {
        return isset($this->data[$name])?$this->data[$name]:null;
    }

    /**
     * 检测数据对象的值
     * @access public
     * @param string $name 名称
     * @return boolean
     */
    public function __isset($name) {
        return isset($this->data[$name]);
    }

    /**
     * 销毁数据对象的值
     * @access public
     * @param string $name 名称
     * @return void
     */
    public function __unset($name) {
        unset($this->data[$name]);
    }

    /**
     * 利用__call方法实现一些特殊的Model方法
     * @access public
     * @param string $method 方法名称
     * @param array $args 调用参数
     * @return mixed
     */
    public function __call($method,$args) {
        if(in_array(strtolower($method),$this->methods,true)) {
            // 连贯操作的实现
            $this->options[strtolower($method)] =   $args[0];
            return $this;
        }elseif(in_array(strtolower($method),array('count','sum','min','max','avg'),true)){
            // 统计查询的实现
            $field =  isset($args[0])?$args[0]:'*';
            return $this->getField(strtoupper($method).'('.$field.') AS tp_'.$method);
        }elseif(strtolower(substr($method,0,5))=='getby') {
            // 根据某个字段获取记录
            $field   =   parse_name(substr($method,5));
            $where[$field] =  $args[0];
            return $this->where($where)->find();
        }elseif(strtolower(substr($method,0,10))=='getfieldby') {
            // 根据某个字段获取记录的某个值
            $name   =   parse_name(substr($method,10));
            $where[$name] =$args[0];
            return $this->where($where)->getField($args[1]);
        }elseif(isset($this->_scope[$method])){// 命名范围的单独调用支持
            return $this->scope($method,$args[0]);
        }else{
            E(__CLASS__.':'.$method.L('_METHOD_NOT_EXIST_'));
            return;
        }
    }
    // 回调方法 初始化模型
    protected function _initialize() {}

    /**
     * 查询数据
     * @access public
     * @param mixed $options 表达式参数
     * @return mixed
     */
    public function find($options=array()) {
        $result = $this->db->find($this->options);
        if(false === $result) {
            return false;
        }
        if(empty($result)) {// 查询结果为空
            return null;
        }else{
            $this->checkMongoId($result);
        }
        $this->data = $result;
        return $this->data;
    }

    /**
     * 查询数据集
     * @access public
     * @param array $options 表达式参数
     * @return mixed
     */
    public function select($options=array()) {
        $resultSet  = $this->db->select($this->options);
        $this->_after_select($resultSet,$options);
        return $resultSet;
    }

    // 查询成功后的回调方法
    protected function _after_select(&$resultSet,$options) {
        $resultSet = array_values($resultSet);
        array_walk($resultSet,array($this,'checkMongoId'));
    }

    /**
     * 获取MongoId
     * @access protected
     * @param array $result 返回数据
     * @return array
     */
    protected function checkMongoId(&$result){
        if(is_object($result['_id'])) {
            $result['_id'] = $result['_id']->__toString();
        }
        return $result;
    }

    /**
     * 切换当前的数据库连接
     * @access public
     * @param integer $linkNum  连接序号
     * @param mixed $config  数据库连接信息
     * @param boolean $force 强制重新连接
     * @return Model
     */
    public function db($linkNum='',$config='',$force=false,$reConn=false) {
        if('' === $linkNum && $this->db) {
            return $this->db;
        }

        static $_db = array();
        if(!isset($_db[$linkNum]) || $force ) {
            // 创建一个新的实例
            if(!empty($config) && is_string($config) && false === strpos($config,'/')) { // 支持读取配置参数
                $config  =  C($config);
            }
            $_db[$linkNum]            =    Db::getInstance($config,$reConn);
        }elseif(NULL === $config){
            $_db[$linkNum]->close(); // 关闭数据库连接
            unset($_db[$linkNum]);
            return ;
        }

        // 切换数据库连接
        $this->db   =    $_db[$linkNum];
        $this->_after_db();
        // 字段检测
        if(!empty($this->name) && $this->autoCheckFields)    $this->_checkTableInfo();
        return $this;
    }

    /**
     * 关闭数据库连接
     */
    public function close()
    {
        $this->db->free();
        $this->db->close();
    }

    // 数据库切换后回调方法
    protected function _after_db() {}

    /**
     * 得到当前的数据对象名称
     * @access public
     * @return string
     */
    public function getModelName() {
        if(empty($this->name)){
            $name = substr(get_class($this),0,-5);
            if ( $pos = strrpos($name,'\\') ) {//有命名空间
                $this->name = substr($name,$pos+1);
            }else{
                $this->name = $name;
            }
        }
        return $this->name;
    }

    /**
     * 得到完整的数据表名
     * @access public
     * @return string
     */
    public function getTableName() {
        if(empty($this->trueTableName)) {
            $tableName  = !empty($this->tablePrefix) ? $this->tablePrefix : '';
            if(!empty($this->tableName)) {
                $tableName .= $this->tableName;
            }else{
                $tableName .= parse_name($this->name);
            }
            $this->trueTableName    =   strtolower($tableName);
        }
        return (!empty($this->dbName)?$this->dbName.'.':'').$this->trueTableName;
    }

    /**
     * 返回模型的错误信息
     * @access public
     * @return string
     */
    public function getError(){
        return $this->error;
    }

    /**
     * 返回数据库的错误信息
     * @access public
     * @return string
     */
    public function getDbError() {
        return $this->db->getError();
    }

    /**
     * 获取主键名称
     * @access public
     * @return string
     */
    public function getPk() {
        return $this->pk;
    }

    /**
     * 查询缓存
     * @access public
     * @param mixed $key
     * @param integer $expire
     * @param string $type
     * @return Model
     */
    public function cache($key=true,$expire=null,$type=''){
        if(false !== $key)
            $this->options['cache']  =  array('key'=>$key,'expire'=>$expire,'type'=>$type);
        return $this;
    }

    /**
     * 指定查询字段 支持字段排除
     * @access public
     * @param mixed $field
     * @param boolean $except 是否排除
     * @return Model
     */
    public function field($field,$except=false){
        if(true === $field) {// 获取全部字段
            $fields     =  $this->getDbFields();
            $field      =  $fields?$fields:'*';
        }elseif($except) {// 字段排除
            if(is_string($field)) {
                $field  =  explode(',',$field);
            }
            $fields     =  $this->getDbFields();
            $field      =  $fields?array_diff($fields,$field):$field;
        }

        $this->options['field']   =   str_replace(' ', '', $field);

        return $this;
    }


    /**
     * 指定查询条件 支持安全过滤
     * @access public
     * @param mixed $where 条件表达式
     * @return Model
     */
    public function where($where){
        if(isset($this->options['where'])){
            $this->options['where'] =   array_merge($this->options['where'],$where);
        }else{
            $this->options['where'] =   $where;
        }

        return $this;
    }

    /**
     * 指定查询数量
     * @access public
     * @param mixed $offset 起始位置
     * @param mixed $length 查询数量
     * @return Model
     */
    public function limit($offset,$length=null){
        $this->options['limit'] =   is_null($length)?$offset:$offset.','.$length;
        return $this;
    }

    /**
     * 指定分页
     * @access public
     * @param mixed $page 页数
     * @param mixed $listRows 每页数量
     * @return Model
     */
    public function page($page,$listRows=null){
        $this->options['page'] =   is_null($listRows)?$page:$page.','.$listRows;
        return $this;
    }

}