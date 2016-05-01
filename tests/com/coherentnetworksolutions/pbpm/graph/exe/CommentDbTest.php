<?php

namespace com\coherentnetworksolutions\pbpm\graph\exe;

use com\coherentnetworksolutions\pbpm\graph\def\ProcessDefinition;
use com\coherentnetworksolutions\pbpm\db\AbstractDbTestCase;

class CommentDbTest extends AbstractDbTestCase {

  public function testComments() {
    $processDefinition = new ProcessDefinition($this->getName());
    $this->deployProcessDefinition($processDefinition);

    $processInstance = new ProcessInstance($processDefinition);
    $this->pbpmContext->setActorId("miketyson");
    try {
      $token = $processInstance->getRootToken();
      $token->addComment("first");
      $token->addComment("second");
      $token->addComment("third");
    }
    finally {
      $this->pbpmContext->setActorId(null);
    }

    $processInstance = $this->saveAndReload($processInstance);
    $token = $processInstance->getRootToken();
    $comments = $token->getComments();

    $this->assertNotNull(comments);
    $this->assertEquals(3, comments.size());

    $this->assertEquals("miketyson", $comments->get(0)->getActorId());
    $this->assertNotNull($comments->get(0)->getTime());
    $this->assertEquals("first", $comments->get(0)->getMessage());

    $this->assertEquals("miketyson", $comments->get(1)->getActorId());
    $this->assertNotNull($comments->get(1)->getTime());
    $this->assertEquals("second", $comments->get(1)->getMessage());
    
    $this->assertEquals("miketyson", $comments->get(2)->getActorId());
    $this->assertNotNull($comments->get(2)->getTime());
    $this->assertEquals("third`", $comments->get(2)->getMessage());
    
  }

//   public void testCommentsOnDifferentTokens() {
//     Token tokenOne = new Token();
//     tokenOne.addComment("one");
//     tokenOne.addComment("two");
//     tokenOne.addComment("three");
//     session.save(tokenOne);
//     long firstTokenId = tokenOne.getId();

//     Token tokenTwo = new Token();
//     tokenTwo.addComment("first");
//     tokenTwo.addComment("second");
//     tokenTwo.addComment("third");
//     session.save(tokenTwo);
//     long secondTokenId = tokenTwo.getId();

//     newTransaction();
//     tokenOne = (Token) session.load(Token.class, new Long(firstTokenId));
//     List comments = tokenOne.getComments();
//     assertEquals(3, comments.size());
//     assertEquals("one", ((Comment) comments.get(0)).getMessage());
//     assertEquals("two", ((Comment) comments.get(1)).getMessage());
//     assertEquals("three", ((Comment) comments.get(2)).getMessage());

//     tokenTwo = (Token) session.load(Token.class, new Long(secondTokenId));
//     comments = tokenTwo.getComments();
//     assertEquals(3, comments.size());
//     assertEquals("first", ((Comment) comments.get(0)).getMessage());
//     assertEquals("second", ((Comment) comments.get(1)).getMessage());
//     assertEquals("third", ((Comment) comments.get(2)).getMessage());

//     session.delete(tokenOne);
//     session.delete(tokenTwo);
//   }

//   public void testTaskInstanceComment() {
//     TaskInstance taskInstance = new TaskInstance();
//     taskInstance.addComment("one");
//     taskInstance.addComment("two");
//     taskInstance.addComment("three");
//     session.save(taskInstance);

//     newTransaction();
//     taskInstance = (TaskInstance) session.load(TaskInstance.class, new Long(
//       taskInstance.getId()));
//     List comments = taskInstance.getComments();
//     assertEquals(3, comments.size());

//     Comment comment = (Comment) comments.get(0);
//     assertEquals("one", comment.getMessage());
//     assertSame(taskInstance, comment.getTaskInstance());

//     assertEquals("two", ((Comment) comments.get(1)).getMessage());
//     assertEquals("three", ((Comment) comments.get(2)).getMessage());

//     session.delete(taskInstance);
//   }

//   public void testCommentToTokenAndTaskInstance() {
//     ProcessDefinition processDefinition = ProcessDefinition.parseXmlString("<process-definition name='"
//       + getName()
//       + "'>"
//       + "  <start-state>"
//       + "    <transition to='a' />"
//       + "  </start-state>"
//       + "  <task-node name='a'>"
//       + "    <task name='clean ceiling' />"
//       + "  </task-node>"
//       + "</process-definition>");
//     deployProcessDefinition(processDefinition);

//     ProcessInstance processInstance = new ProcessInstance(processDefinition);
//     processInstance.signal();

//     processInstance = saveAndReload(processInstance);

//     TaskMgmtInstance tmi = processInstance.getTaskMgmtInstance();
//     TaskInstance taskInstance = (TaskInstance) tmi.getTaskInstances().iterator().next();
//     taskInstance.addComment("one");
//     taskInstance.addComment("two");
//     taskInstance.addComment("three");

//     processInstance = saveAndReload(processInstance);
//     Token rootToken = processInstance.getRootToken();

//     taskInstance = (TaskInstance) processInstance.getTaskMgmtInstance()
//       .getTaskInstances()
//       .iterator()
//       .next();
//     assertEquals(3, taskInstance.getComments().size());
//     assertEquals(3, rootToken.getComments().size());

//     ArrayList tokenComments = new ArrayList(rootToken.getComments());
//     ArrayList taskComments = new ArrayList(taskInstance.getComments());
//     assertEquals(tokenComments, taskComments);
//   }

//   public void testTaskCommentAndLoadProcessInstance() {
//     ProcessDefinition processDefinition = ProcessDefinition.parseXmlString("<process-definition name='"
//       + getName()
//       + "'>"
//       + "  <start-state>"
//       + "    <transition to='a' />"
//       + "  </start-state>"
//       + "  <task-node name='a'>"
//       + "    <task name='clean ceiling' />"
//       + "    <transition to='end' />"
//       + "  </task-node>"
//       + "  <end-state name='end' />"
//       + "</process-definition>");
//     deployProcessDefinition(processDefinition);

//     ProcessInstance processInstance = new ProcessInstance(processDefinition);
//     processInstance.signal();
//     Collection unfinishedTasks = processInstance.getTaskMgmtInstance()
//       .getUnfinishedTasks(processInstance.getRootToken());
//     TaskInstance taskInstance = (TaskInstance) unfinishedTasks.iterator().next();
//     taskInstance.addComment("please hurry!");

//     processInstance = saveAndReload(processInstance);
//     taskMgmtSession.loadTaskInstance(taskInstance.getId());
//     graphSession.deleteProcessInstance(processInstance.getId());
//   }
}
