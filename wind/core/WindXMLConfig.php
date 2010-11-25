<?php
/**
 * @author xiaoxia xu <x_824@sina.com> 2010-11-19
 * @link http://www.phpwind.com
 * @copyright Copyright &copy; 2003-2110 phpwind.com
 * @license
 */
L::import('WIND:core.base.IWindConfig');
L::import('WIND:utility.xml.xml');

/**
 * xml格式配置文件的解析类
 *
 * the last known user to change this file in the repository  <$LastChangedBy$>
 * @author xiaoxia xu <x_824@sina.com>
 * @version $Id$
 * @package
 */
class WindXMLConfig extends XML {
	private $xmlArray;
    private $childConfig;
    private $isCheck;
    private $GAM;
	/**
	 * 构造函数，设置输出编码及变量初始化
	 * @param string $data
	 * @param string $encoding
	 */
	public function __construct($data = '', $encoding = 'gbk') {
		parent::__construct($data, $encoding);
		$this->GAM = array();
	}

	/**
	 * 内容解析
	 *
	 * 内容的解析依赖于配置文件中配置项的格式进行，每个配置项对应的在IWindConfig中都必须有对应的常量声明
	 * 对应的解析格式调用对应的解析函数。
	 *
	 * @return boolean
	 */
	public function parser() {
		$this->createParser();
		$parseArray = trim(IWindConfig::PARSERARRAY, ',');
		$_parseTags = (strpos($parseArray, ',') === false) ? array($parseArray) : explode(',', $parseArray);
		$_array = array();
		foreach($_parseTags as $tag) {
			$elements = $this->getElementByXPath($tag);
			$this->isCheck = true;
			foreach($elements as $element) {
				list($key, $value) = $this->getContent($element);
				$_array[$key] = $value;
			}
		}
		$this->xmlArray = $_array;
		return true;
	}

	/**
	 * 根据标签的形式进行分发
	 *
	 * @param SimpleXMLElement $element
	 * @return array
	 */
	private function getContent($element) {
		$attributes = self::hasAttributes($element);
		$child = self::hasChildren($element);
		if ($attributes && $child) {
			return $this->getContentHasAttAndChild($element);
		}
		if ($attributes) {
			return $this->getContentHasAttributes($element);
		}
		if ($child) {
			return $this->getContentHasChildren($element);
		}
		return $this->getContentNone($element);
	}

	/**
	 * 得到如下规则的标签内容：
	 * <tag>value</tag>
	 * 并且返回形式为array(tag, value)
	 *
	 * @param SimpleXMLElement $element
	 * @return array
	 */
	private function getContentNone($element) {
		$tagName = $element->getName();
		$value = self::getValue($element);
		return array($tagName, $value);
	}

	/**
	 * 获得含有子标签的标签内容：
	 * <AA>
	 *    <BB>Bvalue</BB>
	 *    <CC>Cvalue</CC>
	 * </AA>
	 * 返回结果array(AA, array(BB => Bvalue, CC => Cvalue))
	 *
	 * @param SimpleXMLElement $element
	 * @param array
	 */
	private function getContentHasChildren($element) {
		$tag = $element->getName();
		$childs = $element->Children();
		$childArray = array();
		foreach ($childs as $child) {
			list($childTag, $childValue) = $this->getContent($child);
			$childArray[$childTag] = $childValue;
		}
		return array($tag, $childArray);
	}

	/**
	 * 获得含有子标签的标签内容：
	 * <AA>
	 *    <BB name='key1' value='key1Value' attri3='attribute1'/>
	 *    <BB value='key2Value' attri3='attribute2'/>
	 * </AA>
	 * 如果含有属性name，则将该name作为key
	 * 返回结果array(AA, array(key1 => array(tagName = BB, name => key1, value=>key1Value, attri3 => attribute1),
	 * 						  BB => array(tagName => BB, value=>key2Value, attri3 => attribute2)
	 * 					))
	 *
	 * @param SimpleXMLElement $element
	 * @param array
	 */
	private function getContentHasAttributes($element) {
		$tag = $element->getName();
		$attributes = self::getAttributes($element);
		$attributes['tagName'] = $tag;
		(isset($attributes[IWindConfig::ATTRINAME])) && $tag = $attributes[IWindConfig::ATTRINAME];
		$this->setGAM($attributes);
		return array($tag, array());
	}
	
	/**
	 * 设置全局的标签和需要合并的标签
	 * 
	 * @param array $attributes
	 * @return boolean; 
	 */
	private function setGAM($attributes) {
		if (!$this->isCheck) return false;
		$tag = $attributes['tagName'];
		$name = isset($attributes[IWindConfig::ATTRINAME]) ? $attributes[IWindConfig::ATTRINAME] : $tag;
		(isset($attributes[IWindConfig::GLOBALATTR]) && $attributes[IWindConfig::GLOBALATTR] == 'true') && $this->GAM[IWindConfig::GLOBALATTR][$name] = $tag;
		(isset($attributes[IWindConfig::MERGEATTR]) && $attributes[IWindConfig::MERGEATTR] == 'true') && $this->GAM[IWindConfig::MERGEATTR][$name] = $tag;
		$this->isCheck = false;
		return true;
	}
	
	/**
	 * 获得含有属性和子标签的标签内容，规则如下<pre/>:
	 * <bbbb name='aaa1' attrib1='dddd'>
  	 * 	  <filterName>windFilter1</filterName>
  	 *	  <filterPath>/filter1</filterPath>
  	 * </bbbb>
	 * 该方法对上述的这种情形，根据需求会解析出最后的结果是：
	 * return array(aaa1,
	 *       	       array(name => aaa1,
	 *       				 attrib1 => dddd,
	 *      	       		 filterName => windFilter1,
	 *      	       		 filterPath => /filter1,
	 *      				 tagName = bbbb,
	 *      			)
	 *         )
	 *         
	 * @access private
	 * @param SimpleXMLElement $element
	 * @return array
	 */
	private function getContentHasAttAndChild($element) {
		list($tag, $atttrValue) = $this->getContentHasAttributes($element);
		list($tag1, $childValue) = $this->getContentHasChildren($element);
		$contents = array_merge($atttrValue, $childValue);
		return array($tag, $contents);
	}
	
    /*
	 * 返回解析的结果
	 * @param boolean $isCheck 是否需要检查配置
	 * @return array 返回解析后的数据信息
	 */
	public function getResult() {
		if (!$this->xmlArray) $this->parser();
		return $this->xmlArray;
	}
	
    
	/**
	 * 返回需要设置全局的标签
	 * 
	 * @return array; 
	 */
	public function getGAM($key = '') {
		if (in_array($key, array_keys($this->GAM))) return $this->GAM[$key];
		return $this->GAM;
	}

	/**
	 * 创建解析器
	 * @access private
	 * @return XML object
	 */
	private function createParser() {
		if (is_object($this->object)) return $this;
		$this->ceateParser();
		return $this;
	}
}
