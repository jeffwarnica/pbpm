<?php
namespace com\coherentnetworksolutions\pbpm\db;

use Doctrine\ORM\EntityManager;
class pbpmSession {
    /**
     * @var EntityManager
     */
    private $entityManager = null;
//     Transaction transaction = null;
    
    /**
     * @var GraphSession
     */
    private $graphSession = null;
//     ContextSession contextSession = null;
//     TaskMgmtSession taskMgmtSession = null;
//     LoggingSession loggingSession = null;
//     SchedulerSession schedulerSession = null;

    public function __construct(EntityManager $entityManager) {
        $this->entityManager = $entityManager;
        $this->graphSession = new GraphSession($this);
    }
    
//     public JbpmSessionFactory getJbpmSessionFactory() {
//         return jbpmSessionFactory;
//     }
    
//     public Connection getConnection() {
//         try {
//             return session.connection();
//         } catch (Exception e) {
//             log.error(e);
//             handleException();
//             throw new JbpmException( "couldn't get the jdbc connection from hibernate", e );
//         }
//     }
    
    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager() {
        return $this->entityManager;
    }
    
//     public Transaction getTransaction() {
//         return transaction;
//     }
    
//     public void beginTransaction() {
//         try {
//             transaction = session.beginTransaction();
//         } catch (Exception e) {
//             log.error(e);
//             handleException();
//             throw new JbpmException( "couldn't begin a transaction", e );
//         }
//     }
    
//     public void commitTransaction() {
//         if ( transaction == null ) {
//             throw new JbpmException("can't commit : no transaction started" );
//         }
//         try {
//             session.flush();
//             transaction.commit();
//         } catch (Exception e) {
//             log.error(e);
//             handleException();
//             throw new JbpmException( "couldn't commit transaction", e );
//         } finally {
//             transaction = null;
//         }
//     }
    
//     public void rollbackTransaction() {
//         if ( transaction == null ) {
//             throw new JbpmException("can't rollback : no transaction started" );
//         }
//         try {
//             transaction.rollback();
//         } catch (Exception e) {
//             log.error(e);
//             handleException();
//             throw new JbpmException( "couldn't rollback transaction", e );
//         } finally {
//             transaction = null;
//         }
//     }
    
//     public void commitTransactionAndClose() {
//         commitTransaction();
//         close();
//     }
//     public void rollbackTransactionAndClose() {
//         rollbackTransaction();
//         close();
//     }
    
    /**
     * @return GraphSession
     */
    public function getGraphSession() {
         return $this->graphSession;
    }
//     public ContextSession getContextSession() {
//         return contextSession;
//     }
//     public TaskMgmtSession getTaskMgmtSession() {
//         return taskMgmtSession;
//     }
//     public LoggingSession getLoggingSession() {
//         return loggingSession;
//     }
//     public SchedulerSession getSchedulerSession() {
//         return schedulerSession;
//     }
    
//     public void close() {
//         try {
//             if ( (session!=null)
//                             && (session.isOpen())
//             ) {
//                 session.close();
//             }
//         } catch (Exception e) {
//             log.error(e);
//             throw new JbpmException( "couldn't close the hibernate connection", e );
//         } finally {
//             popCurrentSession();
//             session = null;
//         }
//     }
    
    /**
     * handles an exception that is thrown by doctrine
     */
    public function handleException() {
//         // if hibernate throws an exception,
//         if (is_null($this->transaction)) {
//             try {
//                 // the transaction should be rolled back
//                 transaction.rollback();
//             } catch (HibernateException e) {
//                 log.error("couldn't rollback hibernate transaction", e);
//             }
//             // and the hibernate session should be closed.
//             close();
//         }
    }
    
//     public void pushCurrentSession() {
//         LinkedList stack = (LinkedList) currentJbpmSessionStack.get();
//         if (stack==null) {
//             stack = new LinkedList();
//             currentJbpmSessionStack.set(stack);
//         }
//         stack.addFirst(this);
//     }
    
//     /**
//      * @deprecated use {@link org.jbpm.tc.db.JbpmSessionContext} instead.
//      */
//     public static JbpmSession getCurrentJbpmSession() {
//         JbpmSession jbpmSession = null;
//         LinkedList stack = (LinkedList) currentJbpmSessionStack.get();
//         if ( (stack!=null)
//                         && (! stack.isEmpty())
//         ) {
//             jbpmSession = (JbpmSession) stack.getFirst();
//         }
//         return jbpmSession;
//     }
    
//     public void popCurrentSession() {
//         LinkedList stack = (LinkedList) currentJbpmSessionStack.get();
//         if ( (stack==null)
//                         || (stack.isEmpty())
//                         || (stack.getFirst()!=this)
//         ) {
//             log.warn("can't pop current session: are you calling JbpmSession.close() multiple times ?");
//         } else {
//             stack.removeFirst();
//         }
//     }
    
}