<?php
namespace com\coherentnetworksolutions\pbpm\graph\exe;

/**
 * @entity
 * @author jwarnica
 */
class Comment  {

	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue(strategy="AUTO")
	 *
	 * @var int
	 */
	public $id;
// 	int version;

	/**
	 * @Column(type="string", nullable=true)
	 *
	 * @var string
	 */
	
  protected $actorId;
  /**
   * @Column(type="datetime", nullable=true)
   *
   * @var \DateTime
   */
  
  protected $time;
  /**
   * @Column(type="string", nullable=true)
   *
   * @var string
   */
  
  protected $message;
  /**
   * 
   * @var Token $token
   */
  protected $token;
  
  /**
   * @var TaskInstance
   */
  protected $taskInstance;

  public function __construct($messageOrActorId, $message) {
  	if ($message <> "") {
  		$this->message = $message;
  		$this->actorId = $messageOrActorId;
  	} else {
  		$this->message = $messageOrActorId;
  		//@todo magicly insert actorid from session
  	}
  	$this->time = new \DateTime();
  }

  public function __toString() {
    return "Comment({$this->message})";
  }

  // equals ///////////////////////////////////////////////////////////////////

  public function equals($o) {
    if ($this == $o) return true;
    if (!($o instanceof Comment)) return false;
	
    /**
     * @var Comment $other
     */
    $other = $o;
    if ($this->id != 0 && $this->id == $other->getId()) return true;

    return $this->message == $other->getMessage()
      && (!is_null($this->actorId) ? $this->actorId == $other->getActorId()
          : is_null($other->getActorId()))
      && (!is_null($this->taskInstance) ? $this->taskInstance->equals($other->getTaskInstance())
          : !is_null($this->token) ? $this->token->equals($other->getToken()) : false);
  }

//   public int hashCode() {
//     int result = 769046417 + message.hashCode();
//     result = 1770536419 * result + actorId != null ? actorId.hashCode() : 0;
//     if (taskInstance != null) {
//       result = 55354751 * result + taskInstance.hashCode();
//     }
//     else if (token != null) {
//       result = 55354751 * result + token.hashCode();
//     }
//     return result;
//   }

  // getters and setters //////////////////////////////////////////////////////

  public function getActorId() {
    return $this->actorId;
  }

  public function getId() {
    return $this->id;
  }

  public function getMessage() {
    return $this->message;
  }

  /**
   * @return \DateTime
   */
  public function getTime() {
    return $this->time;
  }

  /**
   * @return TaskInstance
   */
  public function getTaskInstance() {
    return $this->taskInstance;
  }

  /**
   * @return Token
   */
  public function getToken() {
    return $this->token;
  }

  public function setTaskInstance(TaskInstance $taskInstance) {
    $this->taskInstance = $taskInstance;
  }

  public function setToken(Token $token) {
    $this->token = $token;
  }

  public function setActorId($actorId) {
    $this->actorId = $actorId;
  }

  public function setMessage($message) {
    $this->message = $message;
  }

  public function  setTime(\DateTime $time) {
    $this->time = $time;
  }
}
