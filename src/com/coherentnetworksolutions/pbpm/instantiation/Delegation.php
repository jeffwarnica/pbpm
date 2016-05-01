<?php
namespace com\coherentnetworksolutions\pbpm\instantiation;

use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\jpdl\xml\JpdlXmlReader;
use com\coherentnetworksolutions\pbpm\jpdl\xml\Parsable;

/**
 * @entity
 **/
class Delegation implements Parsable {

//     static {
//         instantiatorCache.put(null, new FieldInstantiator());
//         instantiatorCache.put("field", new FieldInstantiator());
//         instantiatorCache.put("bean", new BeanInstantiator());
//         instantiatorCache.put("constructor", new ConstructorInstantiator());
//         instantiatorCache.put("configuration-property", new ConfigurationPropertyInstantiator());
//     }

    /** 
     * @Column(type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     * @var int 
     * */
    public  $id;
    
    /**
     * @Column(type="string")
     * @var string
     */
    protected $className = null;
    /**
     * @Column(type="string")
     * @var string an XML chunk of text
     */
    protected $configuration = null;
    /**
     * @Column(type="string")
     * @var string
     */
    protected $configType = null;

    /**
     * @ManyToOne(targetEntity="com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition")
     * @var ProcessDefinition
     */
    protected $processDefinition = null;
    
    protected $instance = null;
    
    /**
     * @var \Logger
     */
    protected $log;

    public function __construct($classNameOrObjectOrNothing = null) {
        $this->log = \Logger::getLogger(__CLASS__);
        if (is_string($classNameOrObjectOrNothing)) {
            $this->className = $classNameOrObjectOrNothing;
        } elseif (is_object($classNameOrObjectOrNothing)) {
            $this->instance = $classNameOrObjectOrNothing;
        }
    }


    public function read(\DOMElement $delegateElement, JpdlXmlReader $jpdlReader) {
        $this->log->debug(">read()");
        
        $this->processDefinition = $jpdlReader->getProcessDefinition();
        $this->className = $delegateElement->getAttribute("class");
        $this->log->debug("\tGot className ={$this->className}");
        if ($this->className=="") {
            $jpdlReader->addWarning("no class specified in " . $delegateElement->ownerDocument->saveXML($delegateElement));
        }

        $this->configType = $delegateElement->getAttribute("config-type");

        /**
         * JWarnica:
         * 
         * A bit of magic here.
         * 
         * In the XML, an <action ...> can take arbitary configuration information inside of it, e.g.
         * 
         * <action name='foo' class='one'>Random <stuff/> here</action>
         * 
         * which is fine and good. But with PHP DOM, that is hard to save() later, without recursion. So *saved*
         * XML is wrapped in <config> tags. But we want ->configuration to be clean of that.
         */
        
        $config = "";
        foreach ($delegateElement->childNodes as $_) {
            $config .=  $_->ownerDocument->saveXML($_);
        }
        
        $cOpen = "<config>";
        $cClose = "</config>";
        if (substr($config, 0, strlen($cOpen)) == $cOpen) {
            $config = substr($config, strlen($cOpen));
        }
        if (substr($config, strlen($config)-strlen($cClose)) == $cClose) {
            $config = substr($config, 0, strlen($config)-strlen($cClose));
        }
        $this->configuration = $config;

        $this->log->debug("<read()");
    }

    public function write(\DOMElement $element) {
        $this->log->debug(">write()");
        $classNameAttr = new \DOMAttr("class", $this->className);
        $element->appendChild($classNameAttr);
        if ($this->configType != "") {
            $cfgTypeAttr = new \DOMAttr("config-type", $this->configType);
            $element->appendChild($cfgTypeAttr);
        }
        
        $this->log->debug("\t configuration: [{$this->configuration}])");
        if ($this->configuration != "") {
            $configurationAsDom = new \DOMDocument();
            $configurationAsDom->loadXML("<config>{$this->configuration}</config>");
            $element->appendChild($element->ownerDocument->importNode($configurationAsDom->documentElement,true));
        }
        
    }

    public function getInstance() {
    	$this->log->debug("EXEC >getInstance()");
        if (is_null($this->instance)) {
            $this->instance = $this->instantiate();
        }
        $this->log->debug("EXEC <getInstance()");
        return $this->instance;
    }

    public function instantiate() {
		$this->log->debug("EXEC instantiate");
        $newInstance = null;

        // find the classloader to use
//         ClassLoader classLoader = ClassLoaderUtil.getProcessClassLoader(processDefinition);

        // load the class that needs to be instantiated
//         $clazz = null;
        if (!class_exists($this->className)) {
        	$this->log->error("couldn't load delegation class [{$this->className}]");
        }

//         Instantiator instantiator = null;
//         try {
//         	@TODO : implement 	more than class loading
//             // find the instantiator
//             instantiator = (Instantiator) instantiatorCache.get(configType);
//             if (instantiator == null) {
//                 // load the instantiator class
//                 Class instantiatorClass = classLoader.loadClass(configType);
//                 // instantiate the instantiator with the default constructor
//                 instantiator = (Instantiator) instantiatorClass.newInstance();
//                 instantiatorCache.put(configType, instantiator);
//             }
//         } catch (Exception $e) {
//             $this->log->error(e);
//             throw new PbpmException("couldn't instantiate custom instantiator [{$this->configType}]");
//         }

        try {
            // instantiate the object
//             $newInstance = instantiator.instantiate(clazz, configuration);
			$newInstance = new $this->className();
        } catch (\Exception $e) {
            $this->log->error("couldn't instantiate delegation class [{$this->className}]");
        }
		
        $this->log->debug("\t returning instance of type:" . get_class($newInstance));	
        return $newInstance;
    }

//     // equals ///////////////////////////////////////////////////////////////////
//     // hack to support comparing hibernate proxies against the real objects
//     // since this always falls back to ==, we don't need to overwrite the hashcode
//     public boolean equals(Object o) {
//         return EqualsUtil.equals(this, o);
//     }

//     // getters and setters //////////////////////////////////////////////////////

    public function getClassName() {
        return $this->className;
    }
    public function setClassName($className) {
        $this->className = $className;
    }
    public function getConfiguration() {
        return $this->configuration;
    }
    public function setConfiguration($configuration) {
        $this->configuration = $configuration;
    }
    public function getConfigType() {
        return $this->configType;
    }
    public function setConfigType($instantiatorType) {
        $this->configType = $instantiatorType;
    }
    public function getId() {
        return $this->id;
    }
    public function getProcessDefinition() {
        return $this->processDefinition;
    }
    public function setProcessDefinition(ProcessDefinition $processDefinition) {
        $this->processDefinition = $processDefinition;
    }

}