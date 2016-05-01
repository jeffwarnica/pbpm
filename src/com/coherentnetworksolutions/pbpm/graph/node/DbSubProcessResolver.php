<?php
namespace com\coherentnetworksolutions\pbpm\graph\node;

use com\coherentnetworksolutions\pbpm\pbpmContext;

class DbSubProcessResolver implements SubProcessResolver {


  public function findSubProcess(\DOMElement $subProcessElement) {
    // if subprocess resolution is done within an active context,
    // there is a database connection to look up the subprocess.
    // otherwise, the subprocess will be left null and
    // it is up to client code to set the subprocess as appropriate.
//     JbpmContext 
    $azBbpmContext = AzBbpmContext::getCurrentContext();
    if (!is_null($azBbpmContext)) {
      // within an active context it is possible to find the sub-process
      $subProcessName = $subProcessElement->getAttribute("name");
      if ($subProcessName != "") {
        throw new \Exception("missing sub-process name");
      }

      // if only the name is specified,
      $subProcessVersion = $subProcessElement->getAttribute("version");
      if ($subProcessVersion != "") {
        // select the latest version of the subprocess definition
        return $azBbpmContext->getGraphSession()->findLatestProcessDefinition($subProcessName);
      }

      // if the name and the version are specified
        // select the exact version of the subprocess definition
        return $azBbpmContext->getGraphSession()->findProcessDefinition($subProcessName, $subProcessVersion);
    }

    return null;
  }
}
