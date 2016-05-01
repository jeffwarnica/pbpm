<?php
namespace com\coherentnetworksolutions\pbpm\graph\def;
use com\coherentnetworksolutions\pbpm\db\AbstractDbTestCase;
use com\coherentnetworksolutions\pbpm\graph\exe\ProcessInstance;
use com\coherentnetworksolutions\pbpm\context\def\ContextDefinition;
use com\coherentnetworksolutions\pbpm\context\exe\ContextInstance;

class VariableInstanceDbTest extends AbstractDbTestCase {

	/**
	 * @var ProcessInstance $processInstance
	 */
  private $processInstance;
  /**
   * @var ContextInstance $contextInstance
   */
  private $contextInstance;


  public function setUp() {
    parent::setUp();

    $this->processDefinition = new ProcessDefinition($this->getName());
    $this->processDefinition->addDefinition(new ContextDefinition());
    $this->deployProcessDefinition($this->processDefinition);

    $this->processInstance = new ProcessInstance($this->processDefinition);
    $this->contextInstance = $this->processInstance->getContextInstance();
  }

  /**
   * @test
   */
  public function testVariableInstanceString() {
    $this->contextInstance->setVariable("comment", "it's not the size that matters, it's how you use it.");
    $this->assertEquals("it's not the size that matters, it's how you use it.", $this->contextInstance->getVariable("comment"));
    
// var_dump($this->contextInstance);
    $this->processInstance = $this->saveAndReload($this->processInstance);
    
    $this->assertEquals("it's not the size that matters, it's how you use it.", $this->contextInstance->getVariable("comment"));
  }

//   public void testVariableInstanceLong() {
//     contextInstance.setVariable("new salary", new Long(500000));

//     processInstance = saveAndReload(processInstance);
//     contextInstance = processInstance.getContextInstance();

//     assertEquals(new Long(500000), contextInstance.getVariable("new salary"));
//   }

//   public void testVariableInstanceByteArray() {
//     String text = "oh, what a wonderful world";
//     for (int i = 0; i < 10; i++)
//       text += text;
//     byte[] bytes = text.getBytes();
//     assertEquals(text, new String(bytes));
//     contextInstance.setVariable("a lot of bytes", bytes);

//     processInstance = saveAndReload(processInstance);
//     contextInstance = processInstance.getContextInstance();
//     bytes = (byte[]) contextInstance.getVariable("a lot of bytes");
//     assertEquals(text, new String(bytes));
//   }

//   public void testString() {
//     contextInstance.setVariable("a", new String("3"));
//     processInstance = saveAndReload(processInstance);
//     contextInstance = processInstance.getContextInstance();
//     assertEquals("3", contextInstance.getVariable("a"));
//   }

//   public void testBoolean() {
//     contextInstance.setVariable("a", Boolean.TRUE);
//     processInstance = saveAndReload(processInstance);
//     contextInstance = processInstance.getContextInstance();
//     assertEquals(Boolean.TRUE, contextInstance.getVariable("a"));
//   }

//   public void testCharacter() {
//     contextInstance.setVariable("a", new Character('c'));
//     processInstance = saveAndReload(processInstance);
//     contextInstance = processInstance.getContextInstance();
//     assertEquals(new Character('c'), contextInstance.getVariable("a"));
//   }

//   public void testFloat() {
//     contextInstance.setVariable("a", new Float(3.3));
//     processInstance = saveAndReload(processInstance);
//     contextInstance = processInstance.getContextInstance();
//     assertEquals(new Float(3.3), contextInstance.getVariable("a"));
//   }

//   public void testDouble() {
//     contextInstance.setVariable("a", new Double(3.3));
//     processInstance = saveAndReload(processInstance);
//     contextInstance = processInstance.getContextInstance();
//     assertEquals(new Double(3.3), contextInstance.getVariable("a"));
//   }

//   public void testCustomTypeSerializable() {
//     contextInstance.setVariable("a", new MySerializableClass(4));
//     processInstance = saveAndReload(processInstance);
//     contextInstance = processInstance.getContextInstance();
//     assertEquals(new MySerializableClass(4), contextInstance.getVariable("a"));
//   }

//   public void testLong() {
//     contextInstance.setVariable("a", new Long(3));
//     processInstance = saveAndReload(processInstance);
//     contextInstance = processInstance.getContextInstance();
//     assertEquals(new Long(3), contextInstance.getVariable("a"));
//   }

//   public void testByte() {
//     contextInstance.setVariable("a", new Byte("3"));
//     processInstance = saveAndReload(processInstance);
//     contextInstance = processInstance.getContextInstance();
//     assertEquals(new Byte("3"), contextInstance.getVariable("a"));
//   }

//   public void testShort() {
//     contextInstance.setVariable("a", new Short("3"));
//     processInstance = saveAndReload(processInstance);
//     contextInstance = processInstance.getContextInstance();
//     assertEquals(new Short("3"), contextInstance.getVariable("a"));
//   }

//   public void testInteger() {
//     contextInstance.setVariable("a", new Integer(3));
//     processInstance = saveAndReload(processInstance);
//     contextInstance = processInstance.getContextInstance();
//     assertEquals(new Integer(3), contextInstance.getVariable("a"));
//   }

//   public void testDate() {
//     // discard milliseconds as some databases have second precision only
//     Calendar calendar = Calendar.getInstance();
//     calendar.set(Calendar.MILLISECOND, 0);
//     Date now = calendar.getTime();
//     contextInstance.setVariable("a", now);
//     processInstance = saveAndReload(processInstance);
//     contextInstance = processInstance.getContextInstance();

//     Date result = (Date) contextInstance.getVariable("a");
//     assertEquals(now, result);
//   }

//   public void testNullUpdate() {
//     contextInstance.setVariable("a", "blablabla");
//     processInstance = saveAndReload(processInstance);
//     contextInstance = processInstance.getContextInstance();
//     contextInstance.setVariable("a", null);
//     processInstance = saveAndReload(processInstance);
//     contextInstance = processInstance.getContextInstance();
//     assertNull(contextInstance.getVariable("a"));
//   }

//   public void testChangeTypeWithDeleteIsAllowed() {
//     contextInstance.setVariable("a", new String("3"));
//     processInstance = saveAndReload(processInstance);
//     contextInstance = processInstance.getContextInstance();
//     contextInstance.deleteVariable("a");
//     processInstance = saveAndReload(processInstance);
//     contextInstance = processInstance.getContextInstance();
//     contextInstance.setVariable("a", new Integer(3));
//   }

//   public void testSerializableCollection() {
//     List l = new ArrayList();
//     l.add("one");
//     l.add("two");
//     l.add("three");
//     contextInstance.setVariable("l", l);
//     processInstance = saveAndReload(processInstance);
//     contextInstance = processInstance.getContextInstance();
//     assertEquals(l, contextInstance.getVariable("l"));
//   }

//   public void testNonStorableType() {
//     contextInstance.setVariable("t", new Thread());
//     try {
//       jbpmContext.save(processInstance);
//       fail("expected exception");
//     }
//     catch (JbpmException e) {
//       // OK
//       // let's make sure the auto flushing of hibernate doesn't explode
//       contextInstance.deleteVariable("t");
//     }
//   }
}
