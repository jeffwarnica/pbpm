<?php
namespace com\coherentnetworksolutions\pbpm\context\def;

/** @entity **/
class Access {

    /**
     * @Id @Column(type="integer")
     * @var int
     * */
    public $id;

    /**
     * @Column(type="string")
     * @var string
     * */
    protected $access = "read,write";

    public function __construct($access = null) {
        $this->access = $access;
    }

    public function isReadable() {
        return $this->hasAccess("read");
    }

    public function isWritable() {
        return $this->hasAccess("write");
    }

    public function isRequired() {
        return $this->hasAccess("required");
    }

    public function isLock() {
        return $this->hasAccess("lock");
    }

    /**
     * verifies if the given accessLiteral is included in the access text.
     */
    public function hasAccess($accessLiteral) {
        return preg_match("/$accessLiteral/i", $this->access);
    }

    public function __toString() {
        return $this->access;
    }

//     public boolean equals(Object object) {
//         if (object instanceof Access) {
//             Access other = (Access) object;
//             return (isReadable()==other.isReadable())
//             && (isWritable()==other.isWritable())
//             && (isRequired()==other.isRequired())
//             && (isLock()==other.isLock());
//         } else {
//             return false;
//         }
//     }
}