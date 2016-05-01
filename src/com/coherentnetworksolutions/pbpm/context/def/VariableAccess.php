<?php
namespace com\coherentnetworksolutions\pbpm\context\def;

/**
 * specifies access to a variable.
 * Variable access is used in 3 situations:
 * 1) process-state
 * 2) script
 * 3) task controllers
 * 
 * @entity
 */
class VariableAccess {
    /**
     * @Id @Column(type="integer")
     * @var int
     **/
    public $id;
    
    /**
     * @Column(type="string")
     * @var string
     **/
    protected $variableName = null;
    
    /**
     * @OneToOne(targetEntity="Access")
     * @var Access
     */
    protected $access;
    
    /**
     * @Column(type="string")
     * @var string
     **/
    protected $mappedName = null;

    // constructors /////////////////////////////////////////////////////////////

    public function __construct($variableName, $access, $mappedName) {
        $this->variableName = $variableName;
        if ($access!="") $access = strtolower($access);
        $this->access = new Access($access);
        $this->mappedName = $mappedName;
    }

//     // getters and setters //////////////////////////////////////////////////////

    /**
     * the mapped name.  The mappedName defaults to the variableName in case
     * no mapped name is specified.
     */
    public function getMappedName() {
        if ($this->mappedName=="") {
            return $this->variableName;
        }
        return $this->mappedName;
    }

    /**
     * specifies a comma separated list of access literals {read, write, required}.
     * @return Access
     */
    public function getAccess() {
        return $this->access;
    }
    public function getVariableName() {
        return $this->variableName;
    }

    public function isReadable() {
        return $this->access->isReadable();
    }

    public function isWritable() {
        return $this->access->isWritable();
    }

    public function isRequired() {
        return $this->access->isRequired();
    }

    public function isLock() {
        return $this->access->isLock();
    }
}
